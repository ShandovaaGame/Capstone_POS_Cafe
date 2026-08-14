<?php

namespace App\Models;

use App\Services\MenuImageService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class Menu extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'category_id',
        'name',
        'description',
        'price',
        'cashback',
        'image',
        'is_available',
        'is_student_discount',
        'student_price',
    ];

    protected $appends = ['image_url', 'stock'];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'cashback' => 'integer',
            'student_price' => 'integer',
            'is_available' => 'boolean',
            'is_student_discount' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (): void {
            Cache::forget('customer_menu_v3');
        });

        static::deleted(function (self $menu): void {
            app(MenuImageService::class)->delete($menu->image);
            Cache::forget('customer_menu_v3');
        });
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? url('storage/' . $this->image) : null;
    }

    public function getStockAttribute(): ?float
    {
        $ingredients = $this->menuIngredients()->with('ingredient')->get();
        if ($ingredients->isEmpty()) {
            return null;
        }
        $minServings = null;
        foreach ($ingredients as $mi) {
            if (! $mi->ingredient) continue;
            $totalStock = (float) $mi->ingredient->batches()
                ->where('quantity', '>', 0)
                ->where(function ($q) {
                    $q->whereNull('expiry_date')
                      ->orWhereDate('expiry_date', '>', now())
                      ->orWhere('allow_expired_usage', true);
                })
                ->sum('quantity') ?: 0;
            $needed = (float) $mi->quantity_used;
            $servings = $needed > 0 ? (int) ($totalStock / $needed) : 0;
            if ($minServings === null || $servings < $minServings) {
                $minServings = $servings;
            }
        }
        return $minServings ?? 0;
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function ingredients()
    {
        return $this->belongsToMany(Ingredient::class, 'menu_ingredients')
            ->withPivot('quantity_used');
    }

    public function menuIngredients()
    {
        return $this->hasMany(MenuIngredient::class);
    }

    public function calculateCost(): float
    {
        $this->loadMissing('menuIngredients.ingredient');

        $totalCost = 0.0;

        foreach ($this->menuIngredients as $menuIngredient) {
            $ingredient = $menuIngredient->ingredient;

            if (! $ingredient) {
                continue;
            }

            $averageCost = (float) ($ingredient->batches()
                ->where('quantity', '>', 0)
                ->avg('cost_per_unit') ?? 0);

            $totalCost += $averageCost * (float) $menuIngredient->quantity_used;
        }

        return round($totalCost, 2);
    }

    public function hasRecipe(): bool
    {
        return true;
    }

    public function getEffectivePriceAttribute(): string
    {
        return $this->student_price ?: $this->price;
    }
}
