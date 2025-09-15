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
            // Add default price type for customer
            $table->foreignId('default_price_type_id')
                  ->nullable()
                  ->after('is_unlimited_credit')
                  ->constrained('price_types')
                  ->onDelete('set null')
                  ->comment('السعر الافتراضي للعميل - يتجاهل الإعدادات العامة');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['default_price_type_id']);
            $table->dropColumn('default_price_type_id');
        });
    }
};
