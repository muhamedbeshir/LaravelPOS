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
        Schema::table('invoices', function (Blueprint $table) {
            // إضافة حقل جديد لرقم الفاتورة في الوردية، والسماح بقيم null للفواتير القديمة
            $table->unsignedInteger('shift_invoice_number')->nullable()->after('invoice_number')
                ->comment('رقم الفاتورة التسلسلي في الوردية الحالية');
            
            // إضافة مؤشر على الحقل الجديد لتسريع البحث
            $table->index(['shift_id', 'shift_invoice_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // حذف المؤشر أولاً
            $table->dropIndex(['shift_id', 'shift_invoice_number']);
            
            // ثم حذف الحقل
            $table->dropColumn('shift_invoice_number');
        });
    }
};
