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
        Schema::create('employee_advances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 15, 2)->comment('قيمة السلفة');
            $table->date('date')->comment('تاريخ السلفة');
            $table->date('repayment_date')->nullable()->comment('تاريخ السداد المتوقع');
            $table->boolean('is_deducted_from_salary')->default(false)->comment('هل تم خصمها من الراتب');
            $table->decimal('deducted_amount', 15, 2)->default(0)->comment('المبلغ الذي تم خصمه');
            $table->foreignId('salary_payment_id')->nullable()->constrained()->nullOnDelete()->comment('مرجع لدفعة الراتب التي تم فيها الخصم');
            $table->text('notes')->nullable()->comment('ملاحظات');
            $table->enum('status', ['pending', 'partially_paid', 'paid'])->default('pending')->comment('حالة السلفة');
            $table->foreignId('created_by')->constrained('users')->comment('المستخدم الذي سجل السلفة');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_advances');
    }
};
