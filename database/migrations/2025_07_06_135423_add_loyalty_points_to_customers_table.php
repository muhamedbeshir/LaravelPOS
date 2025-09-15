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
        Schema::table('customers', function (Blueprint $table) {
            // إضافة عمود نقاط الولاء الإجمالية
            $table->integer('total_loyalty_points')->default(0)->after('is_unlimited_credit');
            
            // فهرس لتحسين الأداء
            $table->index('total_loyalty_points');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['total_loyalty_points']);
            $table->dropColumn('total_loyalty_points');
        });
    }
};
