<?php

namespace Database\Factories;

use App\Models\PemasukanLain;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PemasukanLain>
 */
class PemasukanLainFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tanggal' => $this->faker->date(),
            'nominal' => $this->faker->randomFloat(2, 50000, 20000000),
            'kategori' => $this->faker->randomElement(['modal', 'lain']),
            'metode_pembayaran' => $this->faker->randomElement(['cash', 'qris', 'transfer', 'lain']),
            'keterangan' => $this->faker->optional()->sentence(),
            'user_id' => User::inRandomOrder()->first()?->id ?? User::factory(),
        ];
    }
}
