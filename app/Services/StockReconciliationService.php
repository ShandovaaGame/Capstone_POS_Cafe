<?php

namespace App\Services;

use App\Models\Ingredient;
use App\Models\Menu;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StockReconciliationService
{
    public static function generateAdjustmentCode(): string
    {
        $dateKey = now()->format('dmy');
        $todayCount = \App\Models\StockAdjustment::whereDate('created_at', today())->count();
        return sprintf('ADJ-%s-%d', $dateKey, $todayCount + 1);
    }

    private function generateCode(): string
    {
        $dateKey = now()->format('dmy');
        $todayCount = \App\Models\StockAdjustment::whereDate('created_at', today())->count();
        return sprintf('ADJ-%s-%d', $dateKey, $todayCount + 1);
    }

    public function createManualAdjustment(
        string $adjustableType,
        ?int $ingredientId = null,
        ?int $menuId = null,
        float $quantity = 0,
        string $adjustmentType = '',
        ?string $category = null,
        ?string $reason = null,
        ?int $reportedBy = null,
        ?string $adjustedAt = null,
    ): StockAdjustment {
        if ($quantity <= 0) {
            throw new RuntimeException('Jumlah penyesuaian harus lebih dari 0.');
        }

        if (! in_array($adjustmentType, StockAdjustment::TYPES, true)) {
            throw new RuntimeException('Tipe penyesuaian tidak valid.');
        }

        if ($adjustableType === StockAdjustment::ADJUSTABLE_TYPE_MENU) {
            return $this->handleMenuAdjustment(
                menuId: $menuId,
                quantity: $quantity,
                adjustmentType: $adjustmentType,
                category: $category,
                reason: $reason,
                reportedBy: $reportedBy,
                adjustedAt: $adjustedAt,
            );
        }

        return $this->handleIngredientAdjustment(
            ingredientId: $ingredientId,
            quantity: $quantity,
            adjustmentType: $adjustmentType,
            category: $category,
            reason: $reason,
            reportedBy: $reportedBy,
            adjustedAt: $adjustedAt,
        );
    }

    private function handleIngredientAdjustment(
        int $ingredientId,
        float $quantity,
        string $adjustmentType,
        ?string $category = null,
        ?string $reason = null,
        ?int $reportedBy = null,
        ?string $adjustedAt = null,
    ): StockAdjustment {
        return DB::transaction(function () use (
            $ingredientId, $quantity, $adjustmentType, $category, $reason, $reportedBy, $adjustedAt,
        ) {
            $ingredient = Ingredient::with('batches')->findOrFail($ingredientId);
            $quantityBefore = (float) $ingredient->getTotalStock();

            if ($adjustmentType === StockAdjustment::TYPE_DECREASE) {
                $adjustment = $this->createDecreaseIngredient(
                    $ingredient, $ingredientId, $quantity, $category, $reason, $reportedBy, $adjustedAt, $quantityBefore,
                );

                $quantityAfter = (float) Ingredient::findOrFail($ingredientId)->getTotalStock();
                $adjustment->update(['quantity_after' => $quantityAfter]);

                return $adjustment;
            }

            $batch = $ingredient->batches()->orderByDesc('received_at')->first();

            if (! $batch) {
                throw new RuntimeException('Tidak ada batch untuk bahan ini. Tambahkan batch terlebih dahulu.');
            }

            $batchBefore = (float) $batch->quantity;
            $batch->quantity = $batchBefore + $quantity;
            $batch->save();

            $quantityAfter = (float) Ingredient::findOrFail($ingredientId)->getTotalStock();

            $adjustment = StockAdjustment::create([
                'code' => $this->generateCode(),
                'adjustable_type' => StockAdjustment::ADJUSTABLE_TYPE_INGREDIENT,
                'ingredient_id' => $ingredientId,
                'adjustment_type' => $adjustmentType,
                'category' => $category,
                'quantity' => $quantity,
                'quantity_before' => $quantityBefore,
                'quantity_after' => $quantityAfter,
                'reason' => $reason,
                'recorded_by' => $reportedBy,
                'adjusted_at' => $adjustedAt ?? now(),
            ]);

            StockMovement::create([
                'ingredient_id' => $ingredientId,
                'ingredient_batch_id' => $batch->id,
                'stock_adjustment_id' => $adjustment->id,
                'movement_type' => 'adjustment_increase',
                'source_type' => 'stock_adjustment',
                'source_id' => (string) $adjustment->id,
                'quantity_before' => $batchBefore,
                'quantity_change' => $quantity,
                'quantity_after' => (float) $batch->quantity,
                'unit_cost' => $batch->cost_per_unit,
                'notes' => $reason,
                'recorded_by' => $reportedBy,
            ]);

            return $adjustment;
        });
    }

    private function createDecreaseIngredient(
        $ingredient,
        int $ingredientId,
        float $quantity,
        ?string $category,
        ?string $reason,
        ?int $reportedBy,
        ?string $adjustedAt,
        float $quantityBefore,
    ): StockAdjustment {
        $adjustment = StockAdjustment::create([
            'code' => $this->generateCode(),
            'adjustable_type' => StockAdjustment::ADJUSTABLE_TYPE_INGREDIENT,
            'ingredient_id' => $ingredientId,
            'adjustment_type' => StockAdjustment::TYPE_DECREASE,
            'category' => $category,
            'quantity' => -$quantity,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $quantityBefore,
            'reason' => $reason,
            'recorded_by' => $reportedBy,
            'adjusted_at' => $adjustedAt ?? now(),
        ]);

        $movementType = $category ? 'waste' : 'adjustment_decrease';

        $service = app(InventoryService::class);
        $service->decreaseStockForIngredient(
            ingredientId: $ingredientId,
            quantity: $quantity,
            context: [
                'movement_type' => $movementType,
                'source_type' => 'stock_adjustment',
                'source_id' => (string) $adjustment->id,
                'stock_adjustment_id' => $adjustment->id,
                'recorded_by' => $reportedBy,
                'notes' => $category ? "[{$category}] {$reason}" : $reason,
            ]
        );

        return $adjustment;
    }

    private function handleMenuAdjustment(
        ?int $menuId = null,
        float $quantity = 0,
        string $adjustmentType = '',
        ?string $category = null,
        ?string $reason = null,
        ?int $reportedBy = null,
        ?string $adjustedAt = null,
    ): StockAdjustment {
        $menu = Menu::with(['menuIngredients.ingredient.batches'])->findOrFail($menuId);

        return DB::transaction(function () use (
            $menu, $quantity, $adjustmentType, $category, $reason, $reportedBy, $adjustedAt,
        ) {
            $notes = $category ? "[{$category}] {$reason}" : $reason;

            $adjustment = StockAdjustment::create([
                'code' => $this->generateCode(),
                'adjustable_type' => StockAdjustment::ADJUSTABLE_TYPE_MENU,
                'menu_id' => $menu->id,
                'adjustment_type' => $adjustmentType,
                'category' => $category,
                'quantity' => $adjustmentType === StockAdjustment::TYPE_DECREASE ? -$quantity : $quantity,
                'quantity_before' => 0,
                'quantity_after' => 0,
                'reason' => $reason,
                'recorded_by' => $reportedBy,
                'adjusted_at' => $adjustedAt ?? now(),
            ]);

            $service = app(InventoryService::class);

            if ($adjustmentType === StockAdjustment::TYPE_DECREASE) {
                foreach ($menu->menuIngredients as $mi) {
                    $deductQty = (float) $mi->quantity_used * $quantity;

                    $service->decreaseStockForIngredient(
                        ingredientId: $mi->ingredient_id,
                        quantity: $deductQty,
                        context: [
                            'movement_type' => 'waste',
                            'source_type' => 'stock_adjustment',
                            'source_id' => (string) $adjustment->id,
                            'stock_adjustment_id' => $adjustment->id,
                            'recorded_by' => $reportedBy,
                            'notes' => $notes,
                        ],
                    );
                }
            } else {
                foreach ($menu->menuIngredients as $mi) {
                    $ingredient = $mi->ingredient;
                    $addQty = (float) $mi->quantity_used * $quantity;

                    $batch = $ingredient->batches()->orderByDesc('received_at')->first();
                    if (! $batch) {
                        throw new RuntimeException("Tidak ada batch untuk bahan '{$ingredient->name}'. Tambahkan batch terlebih dahulu.");
                    }

                    $batchBefore = (float) $batch->quantity;
                    $batch->quantity = $batchBefore + $addQty;
                    $batch->save();

                    StockMovement::create([
                        'ingredient_id' => $ingredient->id,
                        'ingredient_batch_id' => $batch->id,
                        'stock_adjustment_id' => $adjustment->id,
                        'movement_type' => 'adjustment_increase',
                        'source_type' => 'stock_adjustment',
                        'source_id' => (string) $adjustment->id,
                        'quantity_before' => $batchBefore,
                        'quantity_change' => $addQty,
                        'quantity_after' => (float) $batch->quantity,
                        'unit_cost' => $batch->cost_per_unit,
                        'notes' => $notes,
                        'recorded_by' => $reportedBy,
                    ]);
                }
            }

            return $adjustment;
        });
    }
}
