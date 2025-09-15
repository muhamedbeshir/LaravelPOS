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
        // التحقق مما إذا كان الجدول موجوداً بالفعل
        if (!Schema::hasTable('supplier_invoices')) {
        Schema::create('supplier_invoices', function (Blueprint $table) {
            $table->id();
                $table->foreignId('supplier_id')->constrained()->onDelete('cascade');
                $table->string('invoice_number');
                $table->decimal('amount', 10, 2)->default(0);
                $table->date('due_date')->nullable();
                $table->enum('status', ['pending', 'partially_paid', 'paid'])->default('pending');
                $table->decimal('paid_amount', 10, 2)->default(0);
                $table->decimal('remaining_amount', 10, 2)->default(0);
                $table->text('notes')->nullable();
            $table->timestamps();
                $table->softDeletes();
                
                // إنشاء فهرس على رقم الفاتورة
                $table->index('invoice_number');
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // لا نقوم بحذف الجدول إذا كان موجوداً قبل هذا الترحيل
        // Schema::dropIfExists('supplier_invoices');
    }
};
