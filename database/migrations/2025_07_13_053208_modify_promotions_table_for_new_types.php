<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // It's safer to use a raw statement to modify an ENUM column to avoid issues with Doctrine
        DB::statement("ALTER TABLE promotions CHANGE COLUMN promotion_type promotion_type ENUM('simple_discount', 'buy_x_get_y', 'spend_x_save_y', 'coupon_code') NOT NULL");

        Schema::table('promotions', function (Blueprint $table) {
            // Add a column to specify what the discount applies to
            $table->enum('applies_to', ['product', 'category', 'all'])->default('product')->after('promotion_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("ALTER TABLE promotions CHANGE COLUMN promotion_type promotion_type ENUM('percentage', 'fixed', 'buy_x_get_y', 'bundle') NOT NULL");

        Schema::table('promotions', function (Blueprint $table) {
            $table->dropColumn('applies_to');
        });
    }
};
