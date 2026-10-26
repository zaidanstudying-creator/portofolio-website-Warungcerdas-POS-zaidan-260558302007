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
        Schema::create('arsip_transaksi', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal_mulai');
            $table->date('tanggal_akhir');
            $table->string('status_filter')->default('all');
            $table->string('format')->default('zip'); // zip, csv, pdf
            $table->unsignedInteger('record_count')->default(0);
            $table->decimal('total_nominal', 15, 2)->default(0);
            $table->string('checksum')->nullable(); // SHA256 of archive file
            $table->string('file_path')->nullable(); // storage path
            $table->foreignId('user_id')->constrained()->restrictOnDelete(); // creator
            $table->timestamps();

            $table->index(['tanggal_mulai', 'tanggal_akhir']);
            $table->index('user_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('arsip_transaksi');
    }
};
