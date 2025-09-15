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
        Schema::create('loyalty_transactions', function (Blueprint $table) {
            $table->id();
            
            // العميل
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            
            // نوع المعاملة (كسب أو استبدال)
            $table->enum('type', ['earned', 'redeemed', 'manual_add', 'manual_subtract']);
            
            // عدد النقاط (موجب للكسب، سالب للاستبدال)
            $table->integer('points');
            
            // نوع المصدر (فاتورة، يدوي، استبدال)
            $table->enum('source_type', ['invoice', 'manual', 'redemption_balance', 'redemption_discount']);
            
            // معرف المرجع (رقم الفاتورة مثلاً)
            $table->string('reference_id')->nullable();
            
            // وصف العملية
            $table->text('description')->nullable();
            
            // المبلغ المستبدل (في حالة التحويل إلى رصيد)
            $table->decimal('redeemed_amount', 10, 2)->nullable();
            
            // الرصيد بعد العملية
            $table->integer('balance_after');
            
            // المستخدم الذي قام بالعملية (للعمليات اليدوية)
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            
            $table->timestamps();
            
            // فهارس لتحسين الأداء
            $table->index(['customer_id', 'type']);
            $table->index(['source_type', 'reference_id']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loyalty_transactions');
    }
};
