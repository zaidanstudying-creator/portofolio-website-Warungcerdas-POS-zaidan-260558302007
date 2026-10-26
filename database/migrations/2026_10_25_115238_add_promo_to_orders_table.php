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
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('promo_id')->nullable()->constrained('promos')->nullOnDelete()->after('platform_commission');
            $table->decimal('promo_amount', 12, 2)->default(0)->after('promo_id');
            $table->string('promo_code')->nullable()->after('promo_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('promo_id');
            $table->dropColumn(['promo_amount', 'promo_code']);
        });
    }
};
