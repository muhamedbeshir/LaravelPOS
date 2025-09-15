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
        Schema::create('delivery_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('employee_id')->constrained('employees');
            $table->foreignId('status_id')->constrained('delivery_statuses');
            $table->decimal('amount', 10, 2);
            $table->decimal('collected_amount', 10, 2)->default(0);
            $table->decimal('remaining_amount', 10, 2)->default(0);
            $table->boolean('is_paid')->default(false);
            $table->boolean('is_returned')->default(false);
            $table->timestamp('delivery_date')->nullable();
            $table->timestamp('payment_date')->nullable();
            $table->timestamp('return_date')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_transactions');
    }
}; 