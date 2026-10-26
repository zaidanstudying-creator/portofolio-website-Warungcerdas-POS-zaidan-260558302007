<?php

namespace Database\Factories;

use App\Models\Promo;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Promo>
 */
class PromoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => Str::upper(fake()->unique()->bothify('PROMO###??')),
            'name' => fake()->words(3, true),
            'type' => 'percentage',
            'value' => 10,
            'scope' => 'global',
            'product_id' => null,
            'category_id' => null,
            'min_subtotal' => 0,
            'max_discount' => null,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'is_active' => true,
            'usage_limit' => null,
            'usage_count' => 0,
        ];
    }

    public function fixed(float $value): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => 'fixed',
            'value' => $value,
        ]);
    }

    public function forProduct(int $productId): static
    {
        return $this->state(fn (array $attributes): array => [
            'scope' => 'product',
            'product_id' => $productId,
            'category_id' => null,
        ]);
    }

    public function forCategory(int $categoryId): static
    {
        return $this->state(fn (array $attributes): array => [
            'scope' => 'category',
            'product_id' => null,
            'category_id' => $categoryId,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->subDay(),
        ]);
    }
}
