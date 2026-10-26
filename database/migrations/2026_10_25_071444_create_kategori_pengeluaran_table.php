<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('kategori_pengeluaran', function (Blueprint $table) {
            $table->id();
            $table->string('nama')->unique();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Seed default categories
        $categories = [
            ['nama' => 'Bahan Baku', 'sort_order' => 1],
            ['nama' => 'Gas, Listrik, Air & Utilitas', 'sort_order' => 2],
            ['nama' => 'Kemasan', 'sort_order' => 3],
            ['nama' => 'Gaji', 'sort_order' => 4],
            ['nama' => 'Sewa', 'sort_order' => 5],
            ['nama' => 'Transportasi / Biaya Antar', 'sort_order' => 6],
            ['nama' => 'Pemeliharaan', 'sort_order' => 7],
            ['nama' => 'Lain-lain', 'sort_order' => 8],
        ];

        foreach ($categories as $cat) {
            DB::table('kategori_pengeluaran')->insert($cat);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kategori_pengeluaran');
    }
};
