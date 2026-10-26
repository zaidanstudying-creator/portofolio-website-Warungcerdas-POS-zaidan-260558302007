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
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->nullable()
                ->after('id')
                ->constrained('users')
                ->nullOnDelete();
        });

        // Backfill existing orders with the first admin user (if any)
        $firstAdminId = DB::table('users')
            ->where('role', 'admin')
            ->where('is_active', true)
            ->orderBy('id')
            ->value('id');

        if ($firstAdminId) {
            DB::table('orders')
                ->whereNull('user_id')
                ->update(['user_id' => $firstAdminId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
