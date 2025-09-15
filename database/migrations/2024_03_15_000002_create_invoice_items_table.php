<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('restrict');
            $table->foreignId('unit_id')->constrained()->onDelete('restrict');
            
            // الكميات والأسعار
            $table->decimal('quantity', 10, 2);
            $table->decimal('unit_price', 10, 2); // سعر الوحدة قبل الخصم
            $table->decimal('discount_value', 10, 2)->default(0); // قيمة الخصم
            $table->decimal('discount_percentage', 5, 2)->default(0); // نسبة الخصم
            $table->decimal('price_after_discount', 10, 2); // سعر الوحدة بعد الخصم
            $table->decimal('total_price', 10, 2); // السعر الإجمالي
            $table->decimal('unit_cost', 10, 2); // تكلفة الوحدة
            $table->decimal('profit', 10, 2); // الربح

            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
}; 