<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IngredientBatch extends Model
{
    public $timestamps = false;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'batch_code',
        'ingredient_id',
        'quantity',
        'expiry_date',
        'received_at',
        'cost_per_unit',
        'custom_order',
        'status',
        'allow_expired_usage',
    ];

    protected function casts(): array
    {
        return [
            'expiry_date' => 'date',
            'received_at' => 'datetime',
            'quantity' => 'decimal:2',
            'cost_per_unit' => 'decimal:2',
            'custom_order' => 'integer',
            'status' => 'string',
            'allow_expired_usage' => 'boolean',
        ];
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isInactive(): bool
    {
        return $this->status === self::STATUS_INACTIVE;
    }

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function stockAdjustments()
    {
        return $this->hasMany(StockAdjustment::class);
    }
}
