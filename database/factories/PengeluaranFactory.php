<?php

namespace Database\Factories;

use App\Models\KategoriPengeluaran;
use App\Models\Pengeluaran;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pengeluaran>
 */
class PengeluaranFactory extends Factory
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
            'nominal' => $this->faker->randomFloat(2, 10000, 5000000),
            'kategori_pengeluaran_id' => KategoriPengeluaran::inRandomOrder()->first()?->id ?? KategoriPengeluaran::factory(),
            'metode_pembayaran' => $this->faker->randomElement(['cash', 'qris', 'transfer', 'lain']),
            'keterangan' => $this->faker->optional()->sentence(),
            'user_id' => User::inRandomOrder()->first()?->id ?? User::factory(),
            'bukti_path' => null,
        ];
    }
}
