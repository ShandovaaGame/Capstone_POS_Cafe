<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ingredient extends Model
{
    use SoftDeletes;

    const UNITS = [
        'gram' => 'Gram (g)',
        'kg' => 'Kilogram (kg)',
        'ml' => 'Mililiter (ml)',
        'liter' => 'Liter (L)',
        'pcs' => 'Buah / Pcs',
        'sachet' => 'Sachet',
        'sdm' => 'Sendok Makan (sdm)',
        'sdt' => 'Sendok Teh (sdt)',
    ];

    const BATCH_MODE_FIFO = 'fifo';
    const BATCH_MODE_FEFO = 'fefo';
    const BATCH_MODE_CUSTOM = 'custom';

    protected $fillable = [
        'name',
        'unit',
        'low_stock_threshold',
        'batch_mode',
    ];

    protected function casts(): array
    {
        return [
            'low_stock_threshold' => 'decimal:2',
        ];
    }

    public static function batchModes(): array
    {
        return [
            self::BATCH_MODE_FEFO => 'FEFO (First Expired First Out)',
            self::BATCH_MODE_FIFO => 'FIFO (First In First Out)',
            self::BATCH_MODE_CUSTOM => 'Custom (Manual Order)',
        ];
    }

    public function batches()
    {
        return $this->hasMany(IngredientBatch::class);
    }

    public function menus()
    {
        return $this->belongsToMany(Menu::class, 'menu_ingredients')
            ->withPivot('quantity_used');
    }

    public function menuIngredients()
    {
        return $this->hasMany(MenuIngredient::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function stockAdjustments()
    {
        return $this->hasMany(StockAdjustment::class);
    }

    public function getTotalStock(): float
    {
        return (float) $this->batches()->sum('quantity');
    }
}
