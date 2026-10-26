<?php

namespace Database\Factories;

use App\Models\KategoriPengeluaran;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KategoriPengeluaran>
 */
class KategoriPengeluaranFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama' => $this->faker->unique()->word(),
            'is_active' => true,
            'sort_order' => $this->faker->numberBetween(1, 100),
        ];
    }
}
