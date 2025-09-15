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
        Schema::create('suspended_sales', function (Blueprint $table) {
            $table->id();
            $table->string('reference_no')->unique()->nullable(); // رقم مرجعي للفاتورة المعلقة
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // المستخدم الذي علق الفاتورة
            $table->string('invoice_type')->default('cash'); // cash, credit
            $table->string('order_type')->default('takeaway'); // takeaway, delivery
            $table->string('price_type_code')->nullable()->comment('Code of the price type used');
            $table->foreign('price_type_code')->references('code')->on('price_types')->nullOnDelete();
            $table->decimal('discount_value', 15, 2)->default(0);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->decimal('total_amount', 15, 2); // الإجمالي النهائي بعد الخصومات
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('delivery_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suspended_sales');
    }
};
