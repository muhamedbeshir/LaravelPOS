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
        Schema::create('loyalty_settings', function (Blueprint $table) {
            $table->id();
            
            // طريقة احتساب النقاط
            $table->enum('earning_method', ['per_invoice', 'per_amount', 'per_product'])->default('per_amount');
            
            // نقاط لكل فاتورة (عندما تكون الطريقة per_invoice)
            $table->integer('points_per_invoice')->default(10);
            
            // نقاط لكل مبلغ (عندما تكون الطريقة per_amount)
            $table->decimal('points_per_amount', 8, 2)->default(1.00); // 1 نقطة لكل 1 جنيه
            
            // نقاط لكل منتج (عندما تكون الطريقة per_product)
            $table->integer('points_per_product')->default(5);
            
            // معدل تحويل النقاط إلى رصيد (كم نقطة = 1 جنيه)
            $table->integer('points_to_currency_rate')->default(10); // 10 نقاط = 1 جنيه
            
            // الحد الأقصى للاستبدال في كل عملية (null = غير محدود)
            $table->integer('max_redemption_per_transaction')->nullable();
            
            // الحد الأدنى للنقاط قبل الاستبدال
            $table->integer('min_points_for_redemption')->default(50);
            
            // السماح بالخصم الكامل على الفاتورة
            $table->boolean('allow_full_discount')->default(true);
            
            // تفعيل نظام نقاط الولاء
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loyalty_settings');
    }
};
