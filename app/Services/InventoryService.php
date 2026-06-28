<?php

namespace App\Services;

use App\Models\DailyIngredientUsage;
use App\Models\Ingredient;
use App\Models\IngredientBatch;
use App\Models\Menu;
use App\Models\Order;
use App\Models\StockMovement;
use App\Models\WasteRecord;
use Exception;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function processSaleForOrder(Order $order, ?int $recordedBy = null): array
    {
        $alreadyProcessed = StockMovement::query()
            ->where('order_id', $order->id)
            ->where('movement_type', 'sale')
            ->exists();

        if ($alreadyProcessed) {
            return [
                'success' => true,
                'message' => 'Stok penjualan untuk order ini sudah diproses sebelumnya',
                'changes' => [],
                'skipped' => true,
            ];
        }

        $order->loadMissing('items');

        $items = $order->items
            ->map(function ($orderItem) use ($order, $recordedBy) {
                return [
                    'menu_id' => (int) $orderItem->menu_id,
                    'quantity' => (int) $orderItem->quantity,
                    'order_id' => $order->id,
                    'order_item_id' => $orderItem->id,
                    'recorded_by' => $recordedBy ?? $order->cashier_id,
                    'reference' => $order->order_code,
                    'usage_date' => $order->created_at?->toDateString(),
                ];
            })
            ->all();

        if (empty($items)) {
            return [
                'success' => true,
                'message' => 'Order tidak memiliki item untuk diproses',
                'changes' => [],
                'skipped' => true,
            ];
        }

        return $this->decreaseStockForOrder($items);
    }

    public function decreaseStockForOrder(array $items): array
    {
        return DB::transaction(function () use ($items) {
            $stockChanges = [];

            // Pre-load all menus in one query to avoid N+1
            $menuIds = array_unique(array_column($items, 'menu_id'));
            $menus   = Menu::with('menuIngredients.ingredient')
                ->whereIn('id', $menuIds)
                ->get()
                ->keyBy('id');

            foreach ($items as $item) {
                $menu     = $menus->get($item['menu_id']) ?? Menu::with('menuIngredients.ingredient')->findOrFail($item['menu_id']);
                $quantity = (int) ($item['quantity'] ?? 0);

                if ($quantity <= 0 || $menu->menuIngredients->isEmpty()) {
                    continue;
                }

                $itemContext = [
                    'movement_type' => 'sale',
                    'source_type' => 'order_item',
                    'source_id' => isset($item['order_item_id']) ? (string) $item['order_item_id'] : null,
                    'order_id' => $item['order_id'] ?? null,
                    'order_item_id' => $item['order_item_id'] ?? null,
                    'recorded_by' => $item['recorded_by'] ?? null,
                    'reference' => $item['reference'] ?? null,
                    'usage_date' => $item['usage_date'] ?? null,
                ];

                foreach ($menu->menuIngredients as $menuIngredient) {
                    $ingredient = $menuIngredient->ingredient;
                    $requiredQuantity = (float) $menuIngredient->quantity_used * $quantity;

                    $deduction = $this->deductIngredientStock(
                        ingredientId: (int) $ingredient->id,
                        requiredQuantity: $requiredQuantity,
                        context: array_merge($itemContext, [
                            'notes' => "Order usage for menu {$menu->name}",
                        ])
                    );

                    $stockChanges[] = [
                        'ingredient_id' => $ingredient->id,
                        'ingredient_name' => $ingredient->name,
                        'total_deducted' => $requiredQuantity,
                        'unit' => $ingredient->unit,
                        'batches' => $deduction['batch_changes'],
                    ];
                }
            }

            return [
                'success' => true,
                'message' => 'Stok berhasil dikurangi',
                'changes' => $stockChanges,
            ];
        });
    }

    public function decreaseStockForIngredient(int $ingredientId, float $quantity, array $context = []): array
    {
        return DB::transaction(function () use ($ingredientId, $quantity, $context) {
            return $this->deductIngredientStock($ingredientId, $quantity, $context);
        });
    }

    public function decreaseStockForWasteRecord(WasteRecord $wasteRecord): array
    {
        return DB::transaction(function () use ($wasteRecord) {
            return $this->deductIngredientStock(
                ingredientId: (int) $wasteRecord->ingredient_id,
                requiredQuantity: (float) $wasteRecord->quantity,
                context: [
                    'movement_type' => 'waste',
                    'source_type' => 'waste_record',
                    'source_id' => (string) $wasteRecord->id,
                    'waste_record_id' => $wasteRecord->id,
                    'recorded_by' => $wasteRecord->recorded_by,
                    'reference' => 'WR-' . $wasteRecord->id,
                    'notes' => $wasteRecord->reason,
                ]
            );
        });
    }

    public function canFulfillOrder(array $items): array
    {
        $insufficient = [];

        foreach ($items as $item) {
            $menu = Menu::with('menuIngredients.ingredient')->findOrFail($item['menu_id']);
            $quantity = (int) ($item['quantity'] ?? 0);

            if ($quantity <= 0 || $menu->menuIngredients->isEmpty()) {
                continue;
            }

            foreach ($menu->menuIngredients as $menuIngredient) {
                $ingredient = $menuIngredient->ingredient;
                $requiredQuantity = (float) $menuIngredient->quantity_used * $quantity;
                $availableQuantity = $ingredient->getTotalStock();

                if ($availableQuantity < $requiredQuantity) {
                    $insufficient[] = [
                        'ingredient_name' => $ingredient->name,
                        'required' => $requiredQuantity,
                        'available' => $availableQuantity,
                        'unit' => $ingredient->unit,
                    ];
                }
            }
        }

        return [
            'can_fulfill' => empty($insufficient),
            'insufficient_ingredients' => $insufficient,
        ];
    }

    private function deductIngredientStock(int $ingredientId, float $requiredQuantity, array $context = []): array
    {
        $ingredient = Ingredient::findOrFail($ingredientId);

        $batches = IngredientBatch::where('ingredient_id', $ingredientId)
            ->where('quantity', '>', 0)
            ->orderByRaw('CASE WHEN expiry_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('expiry_date', 'asc')
            ->orderBy('received_at', 'asc')
            ->get();

        $totalAvailable = (float) $batches->sum('quantity');

        if ($totalAvailable < $requiredQuantity) {
            throw new Exception(
                "Stok tidak mencukupi untuk bahan '{$ingredient->name}'. " .
                "Dibutuhkan: {$requiredQuantity} {$ingredient->unit}, " .
                "Tersedia: {$totalAvailable} {$ingredient->unit}"
            );
        }

        $remainingToDeduct = $requiredQuantity;
        $batchChanges = [];

        foreach ($batches as $batch) {
            if ($remainingToDeduct <= 0) {
                break;
            }

            $before = (float) $batch->quantity;
            $deductFromThisBatch = min($before, $remainingToDeduct);
            $after = $before - $deductFromThisBatch;

            $batch->quantity = $after;
            $batch->save();

            $remainingToDeduct -= $deductFromThisBatch;

            StockMovement::create([
                'ingredient_id' => $ingredientId,
                'ingredient_batch_id' => $batch->id,
                'order_id' => $context['order_id'] ?? null,
                'order_item_id' => $context['order_item_id'] ?? null,
                'waste_record_id' => $context['waste_record_id'] ?? null,
                'stock_adjustment_id' => $context['stock_adjustment_id'] ?? null,
                'movement_type' => $context['movement_type'] ?? 'sale',
                'source_type' => $context['source_type'] ?? null,
                'source_id' => isset($context['source_id']) ? (string) $context['source_id'] : null,
                'quantity_before' => $before,
                'quantity_change' => -$deductFromThisBatch,
                'quantity_after' => $after,
                'unit_cost' => $batch->cost_per_unit,
                'reference' => $context['reference'] ?? null,
                'notes' => $context['notes'] ?? null,
                'recorded_by' => $context['recorded_by'] ?? null,
            ]);

            $batchChanges[] = [
                'batch_id' => $batch->id,
                'deducted' => $deductFromThisBatch,
                'remaining' => $after,
            ];
        }

        if (($context['movement_type'] ?? 'sale') === 'sale') {
            $this->recordDailyIngredientUsage(
                ingredient: $ingredient,
                usedQuantity: $requiredQuantity,
                usageDate: $context['usage_date'] ?? null,
            );
        }

        return [
            'ingredient_id' => $ingredient->id,
            'ingredient_name' => $ingredient->name,
            'total_deducted' => $requiredQuantity,
            'unit' => $ingredient->unit,
            'batch_changes' => $batchChanges,
        ];
    }

    private function recordDailyIngredientUsage(Ingredient $ingredient, float $usedQuantity, ?string $usageDate = null): void
    {
        $resolvedUsageDate = $usageDate ?: now()->toDateString();

        $dailyUsage = DailyIngredientUsage::query()
            ->where('usage_date', $resolvedUsageDate)
            ->where('ingredient_id', $ingredient->id)
            ->lockForUpdate()
            ->first();

        if ($dailyUsage) {
            $dailyUsage->fill([
                'ingredient_name' => $ingredient->name,
                'unit' => $ingredient->unit,
                'jumlah_digunakan' => round(((float) $dailyUsage->jumlah_digunakan) + $usedQuantity, 2),
            ]);
            $dailyUsage->save();

            return;
        }

        DailyIngredientUsage::create([
            'usage_date' => $resolvedUsageDate,
            'ingredient_id' => $ingredient->id,
            'ingredient_name' => $ingredient->name,
            'unit' => $ingredient->unit,
            'jumlah_digunakan' => round($usedQuantity, 2),
        ]);
    }
}