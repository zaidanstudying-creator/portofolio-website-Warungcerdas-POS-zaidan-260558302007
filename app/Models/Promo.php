<?php

namespace App\Models;

use Database\Factories\PromoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'code',
    'name',
    'type',
    'value',
    'scope',
    'product_id',
    'category_id',
    'min_subtotal',
    'max_discount',
    'starts_at',
    'ends_at',
    'is_active',
    'usage_limit',
    'usage_count',
])]
class Promo extends Model
{
    /** @use HasFactory<PromoFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'min_subtotal' => 'decimal:2',
            'max_discount' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
            'usage_limit' => 'integer',
            'usage_count' => 'integer',
        ];
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Promos that are currently active, within their period, and still have quota.
     */
    #[Scope]
    protected function valid(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->where(function (Builder $query): void {
                $query->whereNull('usage_limit')
                    ->orWhereColumn('usage_count', '<', 'usage_limit');
            });
    }

    /**
     * Determine whether a cart line is inside this promo's scope.
     */
    public function matchesItem(int $productId, ?int $categoryId): bool
    {
        return match ($this->scope) {
            'global' => true,
            'product' => $this->product_id !== null && (int) $this->product_id === $productId,
            'category' => $this->category_id !== null && $categoryId !== null && (int) $this->category_id === $categoryId,
            default => false,
        };
    }

    /**
     * Calculate the discount for the eligible subtotal.
     *
     * A fixed promo is deducted once from the total eligible subtotal, never
     * multiplied by the number of items. Percentage promos use the eligible
     * subtotal (already including toppings). The result is capped by the
     * promo's max_discount and by the eligible subtotal itself.
     */
    public function calculateDiscount(float $eligibleSubtotal): float
    {
        $discount = $this->type === 'percentage'
            ? round($eligibleSubtotal * (float) $this->value / 100, 2)
            : round((float) $this->value, 2);

        $discount = min($discount, $eligibleSubtotal);

        if ($this->max_discount !== null) {
            $discount = min($discount, round((float) $this->max_discount, 2));
        }

        return round(max(0, $discount), 2);
    }
}
