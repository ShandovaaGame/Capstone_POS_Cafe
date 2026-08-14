<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromotionRule extends Model
{
    protected $fillable = [
        'promotion_id',
        'applicable_type',
        'applicable_id',
    ];

    public function promotion()
    {
        return $this->belongsTo(Promotion::class);
    }
}
