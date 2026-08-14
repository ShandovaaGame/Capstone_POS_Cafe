<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockAdjustment extends Model
{
    public const TYPE_INCREASE = 'increase';

    public const TYPE_DECREASE = 'decrease';

    public const TYPES = [
        self::TYPE_INCREASE,
        self::TYPE_DECREASE,
    ];

    public const CAT_EXPIRED = 'expired';
    public const CAT_DAMAGED = 'damaged';
    public const CAT_SPILLED = 'spilled';
    public const CAT_COMPLAINT = 'complaint';
    public const CAT_CORRECTION = 'koreksi_stok';
    public const CAT_OTHER = 'lainnya';

    public const DECREASE_CATEGORIES = [
        self::CAT_EXPIRED => 'Kedaluwarsa',
        self::CAT_DAMAGED => 'Rusak',
        self::CAT_SPILLED => 'Tumpah',
        self::CAT_COMPLAINT => 'Komplain Pelanggan',
        self::CAT_CORRECTION => 'Koreksi Stok',
        self::CAT_OTHER => 'Lainnya',
    ];

    public const INCREASE_CATEGORIES = [
        self::CAT_CORRECTION => 'Koreksi Stok',
        self::CAT_OTHER => 'Lainnya',
    ];

    public const ADJUSTABLE_TYPE_INGREDIENT = 'ingredient';
    public const ADJUSTABLE_TYPE_MENU = 'menu';

    public const ADJUSTABLE_TYPES = [
        self::ADJUSTABLE_TYPE_INGREDIENT => 'Bahan Baku',
        self::ADJUSTABLE_TYPE_MENU => 'Menu',
    ];

    public const STATUS_ACTIVE = 'active';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'code',
        'adjustable_type',
        'ingredient_id',
        'ingredient_batch_id',
        'menu_id',
        'adjustment_type',
        'category',
        'quantity',
        'quantity_before',
        'quantity_after',
        'reason',
        'reference',
        'recorded_by',
        'approved_by',
        'adjusted_at',
        'status',
        'cancel_reason',
    ];

    protected function casts(): array
    {
        return [
            'quantity'        => 'decimal:2',
            'quantity_before' => 'decimal:2',
            'quantity_after'  => 'decimal:2',
            'adjusted_at'     => 'datetime',
            'category'        => 'string',
            'status'          => 'string',
        ];
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public static function getCategoryOptions(?string $type): array
    {
        return match ($type) {
            self::TYPE_INCREASE => self::INCREASE_CATEGORIES,
            self::TYPE_DECREASE => self::DECREASE_CATEGORIES,
            default => [],
        };
    }

    public function isIngredientAdjustment(): bool
    {
        return $this->adjustable_type === self::ADJUSTABLE_TYPE_INGREDIENT;
    }

    public function isMenuAdjustment(): bool
    {
        return $this->adjustable_type === self::ADJUSTABLE_TYPE_MENU;
    }

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function menu()
    {
        return $this->belongsTo(Menu::class);
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    // Alias so GitHub code referencing reportedBy still works
    public function reportedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }
}
