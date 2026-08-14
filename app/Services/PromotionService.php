<?php

namespace App\Services;

use App\Models\Menu;
use App\Models\Promotion;
use Illuminate\Support\Collection;

class PromotionService
{
    private ?Collection $activePromotions = null;

    private function loadActivePromotions(): Collection
    {
        if ($this->activePromotions === null) {
            $this->activePromotions = Promotion::query()
                ->with('rules')
                ->where('status', Promotion::STATUS_ACTIVE)
                ->whereDate('start_date', '<=', now())
                ->whereDate('end_date', '>=', now())
                ->get();
        }

        return $this->activePromotions;
    }

    public function getApplicablePromotionsForMenu(Menu $menu): Collection
    {
        return $this->loadActivePromotions()
            ->filter(fn (Promotion $promotion) => $promotion->canBeUsed() && $promotion->isApplicableTo((int) $menu->id, (int) $menu->category_id)
            )
            ->values();
    }

    public function calculateDiscountAmount(Promotion $promotion, float $unitPrice, int $quantity): float
    {
        $lineBase = $unitPrice * $quantity;

        if ($promotion->min_purchase !== null && $lineBase < (float) $promotion->min_purchase) {
            return 0;
        }

        return match ($promotion->type) {
            Promotion::TYPE_PERCENTAGE => min($lineBase, $lineBase * ((float) $promotion->discount_value / 100)),
            Promotion::TYPE_FIXED_AMOUNT => min($lineBase, (float) $promotion->discount_value),
            default => 0,
        };
    }
}
