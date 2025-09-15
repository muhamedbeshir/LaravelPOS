<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('category_id')->constrained()->onDelete('restrict');
            $table->string('barcode')->unique()->nullable();
            $table->text('description')->nullable();
            $table->decimal('purchase_price', 10, 2)->default(0);
            $table->decimal('retail_price', 10, 2)->default(0);
            $table->decimal('wholesale_price', 10, 2)->default(0);
            $table->decimal('distributor_price', 10, 2)->default(0);
            $table->integer('stock_quantity')->default(0);
            $table->decimal('alert_quantity', 10, 2)->default(0);
            $table->integer('min_stock_quantity')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
}; 