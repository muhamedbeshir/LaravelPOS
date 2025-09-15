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
        // First create a table for price types
        Schema::create('price_types', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "سعر رئيسي", "سعر 2", "سعر 3", etc.
            $table->string('code')->unique(); // e.g., "main_price", "price_2", "price_3"
            $table->integer('sort_order')->default(0);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Then create the relation table between product units and price types
        Schema::create('product_unit_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_unit_id')->constrained('product_units')->onDelete('cascade');
            $table->foreignId('price_type_id')->constrained('price_types')->onDelete('cascade');
            $table->decimal('value', 10, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            // ensure each product unit has each price type only once
            $table->unique(['product_unit_id', 'price_type_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_unit_prices');
        Schema::dropIfExists('price_types');
    }
};
