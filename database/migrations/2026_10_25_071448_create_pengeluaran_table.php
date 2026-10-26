<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pengeluaran', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->decimal('nominal', 12, 2);
            $table->foreignId('kategori_pengeluaran_id')->constrained('kategori_pengeluaran')->restrictOnDelete();
            $table->enum('metode_pembayaran', ['cash', 'qris', 'transfer', 'lain']);
            $table->text('keterangan')->nullable();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('bukti_path')->nullable();
            $table->timestamps();

            $table->index(['tanggal', 'kategori_pengeluaran_id']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengeluaran');
    }
};
