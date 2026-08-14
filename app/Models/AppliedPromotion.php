<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppliedPromotion extends Model
{
    protected $fillable = [
        'order_id',
        'promotion_id',
        'discount_type',
        'discount_value',
        'discount_amount',
    ];

    protected function casts(): array
    {
        return [
            'discount_value' => 'decimal:2',
            'discount_amount' => 'decimal:2',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function promotion()
    {
        return $this->belongsTo(Promotion::class);
    }
}
