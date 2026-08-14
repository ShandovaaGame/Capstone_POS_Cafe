<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Promotion extends Model
{
    use SoftDeletes;

    public const TYPE_PERCENTAGE = 'percentage';

    public const TYPE_FIXED_AMOUNT = 'fixed_amount';

    public const TYPE_BUY_X_GET_Y = 'buy_x_get_y';

    public const TYPE_BUNDLE = 'bundle';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'name',
        'type',
        'discount_value',
        'min_purchase',
        'start_date',
        'end_date',
        'status',
        'applicable_items',
        'description',
        'usage_limit',
        'usage_count',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'discount_value' => 'decimal:2',
            'min_purchase' => 'decimal:2',
            'applicable_items' => 'array',
        ];
    }

    public function rules()
    {
        return $this->hasMany(PromotionRule::class);
    }

    public function appliedPromotions()
    {
        return $this->hasMany(AppliedPromotion::class);
    }

    public function isActive(): bool
    {
        $today = now()->startOfDay();

        return $this->status === self::STATUS_ACTIVE
            && $this->start_date !== null
            && $this->end_date !== null
            && $this->start_date->lte($today)
            && $this->end_date->gte($today);
    }

    public function canBeUsed(): bool
    {
        return $this->isActive()
            && ($this->usage_limit === null || (int) $this->usage_count < (int) $this->usage_limit);
    }

    public function isApplicableTo(int $menuId, int $categoryId): bool
    {
        $rules = $this->rules;

        if ($rules->isEmpty()) {
            return true;
        }

        return $rules->contains(function (PromotionRule $rule) use ($menuId, $categoryId) {
            return ($rule->applicable_type === 'menu' && (int) $rule->applicable_id === $menuId)
                || ($rule->applicable_type === 'category' && (int) $rule->applicable_id === $categoryId);
        });
    }
}
