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
        if (!Schema::hasTable('product_unit_prices')) {
            Schema::create('product_unit_prices', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('product_id');
                $table->unsignedBigInteger('unit_id');
                $table->unsignedBigInteger('price_type_id');
                $table->decimal('price', 15, 2)->default(0);
                $table->timestamps();
                $table->softDeletes();
                $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
                $table->foreign('unit_id')->references('id')->on('units')->onDelete('cascade');
                $table->foreign('price_type_id')->references('id')->on('price_types')->onDelete('cascade');
                $table->unique(['product_id', 'unit_id', 'price_type_id'], 'product_unit_price_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_unit_prices');
    }
};
