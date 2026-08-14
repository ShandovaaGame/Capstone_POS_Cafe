<?php

namespace App\Services;

use App\Models\Menu;
use App\Models\Order;
use App\Models\Promotion;

class OrderPromotionService
{
    public function __construct(
        protected PromotionService $promotionService,
    ) {}

    public function calculateLine(
        Menu $menu,
        int $quantity,
        bool $isMahasiswa = false,
        array $selectedPromotionIds = [],
    ): array {
        $cashback = ($isMahasiswa && (float) $menu->cashback > 0)
            ? (float) $menu->cashback
            : 0.0;

        $unitPrice = max(0.0, (float) $menu->price - $cashback);

        $bestPromotion = null;
        $bestDiscountAmount = 0.0;

        $applicablePromotions = $this->promotionService->getApplicablePromotionsForMenu($menu);

        $selectedPromotionMap = collect($selectedPromotionIds)
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->flip();

        if ($selectedPromotionMap->isNotEmpty()) {
            $applicablePromotions = $applicablePromotions
                ->filter(fn (Promotion $promotion) => $selectedPromotionMap->has((int) $promotion->id))
                ->values();
        }

        foreach ($applicablePromotions as $promotion) {
            $discountAmount = $this->promotionService
                ->calculateDiscountAmount($promotion, $unitPrice, $quantity);

            if ($discountAmount > $bestDiscountAmount) {
                $bestDiscountAmount = $discountAmount;
                $bestPromotion = $promotion;
            }
        }

        $lineBase = $unitPrice * $quantity;
        $subtotal = max(0.0, $lineBase - $bestDiscountAmount);

        return [
            'unit_price' => round($unitPrice, 2),
            'subtotal' => round($subtotal, 2),
            'applied_promotion' => $bestPromotion ? [
                'promotion_id' => (int) $bestPromotion->id,
                'discount_type' => $bestPromotion->type,
                'discount_value' => (float) $bestPromotion->discount_value,
                'discount_amount' => round($bestDiscountAmount, 2),
            ] : null,
        ];
    }

    public function persistOrderPromotions(Order $order, array $appliedPromotions): void
    {
        if (empty($appliedPromotions)) {
            return;
        }

        $aggregatedPromotions = [];

        foreach ($appliedPromotions as $appliedPromotion) {
            $promotionId = (int) ($appliedPromotion['promotion_id'] ?? 0);

            if ($promotionId <= 0) {
                continue;
            }

            if (! isset($aggregatedPromotions[$promotionId])) {
                $aggregatedPromotions[$promotionId] = [
                    'promotion_id' => $promotionId,
                    'discount_type' => $appliedPromotion['discount_type'],
                    'discount_value' => $appliedPromotion['discount_value'],
                    'discount_amount' => 0,
                ];
            }

            $aggregatedPromotions[$promotionId]['discount_amount'] += (float) $appliedPromotion['discount_amount'];
        }

        if (empty($aggregatedPromotions)) {
            return;
        }

        $rows = collect($aggregatedPromotions)
            ->map(fn (array $promotion) => [
                'promotion_id' => $promotion['promotion_id'],
                'discount_type' => $promotion['discount_type'],
                'discount_value' => round((float) $promotion['discount_value'], 2),
                'discount_amount' => round((float) $promotion['discount_amount'], 2),
            ])
            ->values()
            ->all();

        $order->appliedPromotions()->createMany($rows);

        Promotion::query()
            ->whereIn('id', array_keys($aggregatedPromotions))
            ->increment('usage_count');
    }
}
