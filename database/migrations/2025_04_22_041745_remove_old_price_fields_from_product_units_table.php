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
        // This migration should only be run after all prices have been 
        // successfully migrated to the new price types system
        Schema::table('product_units', function (Blueprint $table) {
            $table->dropColumn(['main_price', 'app_price', 'other_price']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_units', function (Blueprint $table) {
            $table->decimal('main_price', 10, 2)->default(0)->after('barcode');
            $table->decimal('app_price', 10, 2)->nullable()->after('main_price');
            $table->decimal('other_price', 10, 2)->nullable()->after('app_price');
        });
    }
};
