<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->enum('type', ['cash', 'credit'])->default('cash');
            $table->enum('order_type', ['takeaway', 'delivery'])->default('takeaway');
            $table->foreignId('customer_id')->nullable()->constrained()->onDelete('restrict');
            $table->foreignId('delivery_employee_id')->nullable()->constrained('employees')->onDelete('restrict');
            $table->enum('price_type', ['retail', 'wholesale', 'distributor'])->default('retail');
            
            // المبالغ
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('discount_value', 10, 2)->default(0);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->decimal('remaining_amount', 10, 2)->default(0);
            $table->decimal('profit', 10, 2)->default(0);

            // الحالة
            $table->enum('status', ['pending', 'completed', 'cancelled'])->default('pending');
            $table->enum('payment_status', ['paid', 'partially_paid', 'unpaid'])->default('unpaid');
            $table->enum('delivery_status', ['pending', 'out_for_delivery', 'delivered', 'cancelled'])->nullable();
            
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
}; 