<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

class StockMovement extends Model
{
    protected $fillable = [
        'ingredient_id',
        'ingredient_batch_id',
        'order_id',
        'order_item_id',
        'stock_adjustment_id',
        'movement_type',
        'source_type',
        'source_id',
        'quantity_before',
        'quantity_change',
        'quantity_after',
        'unit_cost',
        'reference',
        'notes',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity_before' => 'decimal:2',
            'quantity_change' => 'decimal:2',
            'quantity_after' => 'decimal:2',
            'unit_cost' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function () {
            throw new LogicException('Stock movements are immutable. Create a stock adjustment instead.');
        });

        static::deleting(function () {
            throw new LogicException('Stock movements are immutable. Create a stock adjustment instead.');
        });
    }

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function ingredientBatch()
    {
        return $this->belongsTo(IngredientBatch::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function stockAdjustment()
    {
        return $this->belongsTo(StockAdjustment::class);
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
