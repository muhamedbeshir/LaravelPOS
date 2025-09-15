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
        Schema::table('product_price_history', function (Blueprint $table) {
            // Add a nullable column for the price_type_id to link to the new price_types table
            $table->foreignId('price_type_id')->nullable()->after('product_unit_id')
                ->constrained('price_types')->onDelete('set null');
                
            // We'll keep the price_type column for backward compatibility
            // but new records will use price_type_id instead
            $table->string('price_type')
                ->comment('For new records: corresponds to price_type.code. For old records: main_price, app_price, other_price')
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_price_history', function (Blueprint $table) {
            $table->dropForeign(['price_type_id']);
            $table->dropColumn('price_type_id');
            // Note: We can't easily revert the change to the price_type column comment
        });
    }
};
