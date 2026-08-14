<?php

namespace App\Services;

use App\Models\DailyIngredientUsage;
use App\Models\Ingredient;
use App\Models\IngredientBatch;
use App\Models\Menu;
use App\Models\Order;
use App\Models\StockMovement;
use App\Models\StockAdjustment;
use App\Models\Unit;
use App\Services\UnitConversionService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
            $menus = Menu::with(['menuIngredients.ingredient'])
                ->whereIn('id', $menuIds)
                ->get()
                ->keyBy('id');

            foreach ($items as $item) {
                $menu = $menus->get($item['menu_id']) ?? Menu::with('menuIngredients.ingredient')->findOrFail($item['menu_id']);
                $quantity = (int) ($item['quantity'] ?? 0);

                if ($quantity <= 0) {
                    throw new \InvalidArgumentException(
                        "Jumlah pesanan tidak valid untuk menu '{$menu->name}': quantity harus lebih dari 0"
                    );
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

                if ($menu->menuIngredients->isNotEmpty()) {
                    foreach ($menu->menuIngredients as $menuIngredient) {
                        $ingredient = $menuIngredient->ingredient;
                        $requiredQuantity = (float) $menuIngredient->quantity_used * $quantity;

                        $deduction = $this->deductIngredientStock(
                            ingredientId: (int) $ingredient->id,
                            requiredQuantity: $requiredQuantity,
                            context: array_merge($itemContext, [
                                'notes' => "Order usage for menu {$menu->name}",
                                'recipeUnitId' => $menuIngredient->unit_id,
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

    public function canFulfillOrder(array $items): array
    {
        $insufficient = [];

        foreach ($items as $item) {
            $menu = Menu::with(['menuIngredients.ingredient'])->findOrFail($item['menu_id']);
            $quantity = (int) ($item['quantity'] ?? 0);

            if ($quantity <= 0) {
                throw new \InvalidArgumentException(
                    "Jumlah pesanan tidak valid untuk menu '{$menu->name}': quantity harus lebih dari 0"
                );
            }

            if ($menu->menuIngredients->isNotEmpty()) {
                foreach ($menu->menuIngredients as $menuIngredient) {
                    $ingredient = $menuIngredient->ingredient;
                    $requiredQuantity = (float) $menuIngredient->quantity_used * $quantity;

                    // Unit conversion: if recipe unit differs from ingredient storage unit
                    if ($menuIngredient->unit_id && $ingredient->unit_id && $menuIngredient->unit_id != $ingredient->unit_id) {
                        $fromUnit = Unit::find($menuIngredient->unit_id);
                        $toUnit = Unit::find($ingredient->unit_id);
                        if ($fromUnit && $toUnit) {
                            $requiredQuantity = app(UnitConversionService::class)->convert($requiredQuantity, $fromUnit, $toUnit);
                        }
                    }

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
        }

        return [
            'can_fulfill' => empty($insufficient),
            'insufficient_ingredients' => $insufficient,
        ];
    }

    public function wasteMenu(int $menuId, float $quantity, string $wasteCategory, string $reason, ?int $recordedBy = null): array
    {
        $menu = Menu::with(['menuIngredients.ingredient'])->findOrFail($menuId);

        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Jumlah waste harus lebih dari 0.');
        }

        // Validasi waste category
        if (! array_key_exists($wasteCategory, StockAdjustment::DECREASE_CATEGORIES)) {
            throw new \InvalidArgumentException('Kategori waste tidak valid.');
        }

        return DB::transaction(function () use ($menu, $quantity, $wasteCategory, $reason, $recordedBy) {
            $context = [
                'movement_type' => 'waste',
                'source_type' => 'stock_adjustment',
                'recorded_by' => $recordedBy,
                'notes' => "[Menu: {$menu->name} ({$quantity}x)] {$reason}",
            ];

            // Branch: Recipe menu → ingredient deduction
            if ($menu->hasRecipe()) {
                return $this->wasteRecipeMenu($menu, $quantity, $wasteCategory, $reason, $context);
            }

            throw new \RuntimeException(
                "Menu '{$menu->name}' tidak memiliki resep bahan baku."
            );
        });
    }

    private function wasteRecipeMenu(Menu $menu, float $quantity, string $wasteCategory, string $reason, array $context): array
    {
        // Cek stok mencukupi sebelum deduction
        $stockCheck = $this->canFulfillOrder([['menu_id' => $menu->id, 'quantity' => $quantity]]);
        if (! $stockCheck['can_fulfill']) {
            $insufficient = collect($stockCheck['insufficient_ingredients'])
                ->map(fn ($i) => "{$i['ingredient_name']}: butuh {$i['required']} {$i['unit']}, tersedia {$i['available']} {$i['unit']}")
                ->implode('; ');
            throw new \RuntimeException("Stok tidak mencukupi untuk '{$menu->name}': {$insufficient}");
        }

        // Hanya 1 StockAdjustment untuk mencatat event waste
        $adjustment = StockAdjustment::create([
            'adjustable_type' => StockAdjustment::ADJUSTABLE_TYPE_INGREDIENT,
            'ingredient_id' => $menu->menuIngredients->first()?->ingredient_id,
            'adjustment_type' => StockAdjustment::TYPE_DECREASE,
            'category' => $wasteCategory,
            'quantity' => -$quantity,
            'quantity_before' => 0,
            'quantity_after' => 0,
            'reason' => $reason,
            'reported_by' => $context['recorded_by'] ?? null,
            'adjusted_at' => now(),
        ]);

        $ingredientCount = 0;
        foreach ($menu->menuIngredients as $menuIngredient) {
            $ingredient = $menuIngredient->ingredient;
            $requiredQty = (float) $menuIngredient->quantity_used * $quantity;

            $deductionContext = array_merge($context, [
                'stock_adjustment_id' => $adjustment->id,
                'source_id' => (string) $adjustment->id,
                'recipeUnitId' => $menuIngredient->unit_id,
            ]);

            $this->deductIngredientStock(
                ingredientId: (int) $ingredient->id,
                requiredQuantity: $requiredQty,
                context: $deductionContext,
            );
            $ingredientCount++;
        }

        return [
            'success' => true,
            'message' => "Waste untuk menu '{$menu->name}' ({$quantity}x) berhasil dicatat.",
            'adjustments' => [$adjustment],
            'total_ingredients_deducted' => $ingredientCount,
        ];
    }

    private function deductIngredientStock(int $ingredientId, float $requiredQuantity, array $context = []): array
    {
        $ingredient = Ingredient::findOrFail($ingredientId);

        // Unit conversion: if recipe unit differs from ingredient storage unit
        if (isset($context['recipeUnitId'])) {
            if ($ingredient->unit_id && $context['recipeUnitId'] != $ingredient->unit_id) {
                $fromUnit = Unit::find($context['recipeUnitId']);
                $toUnit = Unit::find($ingredient->unit_id);
                if ($fromUnit && $toUnit) {
                    $converted = app(UnitConversionService::class)->convert($requiredQuantity, $fromUnit, $toUnit);
                    Log::info("Unit conversion: {$requiredQuantity} {$fromUnit->name} → {$converted} {$toUnit->name} for ingredient {$ingredient->name}");
                    $requiredQuantity = $converted;
                }
            }
        }

        $query = IngredientBatch::where('ingredient_id', $ingredientId)
            ->where('quantity', '>', 0)
            ->where(function ($q) {
                $q->whereNull('expiry_date')
                  ->orWhereDate('expiry_date', '>', now())
                  ->orWhere('allow_expired_usage', true);
            })
            ->lockForUpdate();

        match ($ingredient->batch_mode) {
            Ingredient::BATCH_MODE_FIFO => $query
                ->orderByRaw('CASE WHEN received_at IS NULL THEN 1 ELSE 0 END')
                ->orderBy('received_at', 'asc')
                ->orderBy('expiry_date', 'asc')
                ->orderBy('id', 'asc'),
            Ingredient::BATCH_MODE_CUSTOM => $query
                ->orderByRaw('CASE WHEN custom_order IS NULL THEN 1 ELSE 0 END')
                ->orderBy('custom_order', 'asc')
                ->orderBy('received_at', 'asc')
                ->orderBy('id', 'asc'),
            default => $query  // FEFO (default & null fallback)
                ->orderByRaw('CASE WHEN expiry_date IS NULL THEN 1 ELSE 0 END')
                ->orderBy('expiry_date', 'asc')
                ->orderBy('received_at', 'asc')
                ->orderBy('id', 'asc'),
        };

        $batches = $query->get();

        $totalAvailable = (float) $batches->sum('quantity');

        if ($totalAvailable < $requiredQuantity) {
            throw new Exception(
                "Stok tidak mencukupi untuk bahan '{$ingredient->name}'. ".
                "Dibutuhkan: {$requiredQuantity} {$ingredient->unit}, ".
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
