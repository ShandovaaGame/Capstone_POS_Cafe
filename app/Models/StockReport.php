<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockReport extends Model
{
    public const TYPE_INCREASE = 'increase';

    public const TYPE_DECREASE = 'decrease';

    public const TYPES = [
        self::TYPE_INCREASE,
        self::TYPE_DECREASE,
    ];

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
    ];

    protected $fillable = [
        'ingredient_id',
        'reported_by',
        'report_type',
        'quantity',
        'quantity_before',
        'quantity_after',
        'reason',
        'status',
        'reviewed_by',
        'rejection_note',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'quantity_before' => 'decimal:2',
            'quantity_after' => 'decimal:2',
            'reviewed_at' => 'datetime',
        ];
    }

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function reportedBy()
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
