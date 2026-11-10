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
        Schema::table('users', function (Blueprint $table) {
            $table->string('super_admin_key', 16)->nullable()->unique()->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('users')
            ->where('super_admin_key', 'primary')
            ->update(['role' => 'admin']);

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['super_admin_key']);
            $table->dropColumn('super_admin_key');
        });
    }
};
