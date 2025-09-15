<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // الخطوة 1: إزالة قيد المفتاح الأجنبي الحالي
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
        });

        // الخطوة 2: تعديل العمود ليقبل القيمة null
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreignId('employee_id')->nullable()->change();
        });

        // الخطوة 3: إعادة تعريف قيد المفتاح الأجنبي بشكل صحيح
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreign('employee_id')
                ->references('id')
                ->on('employees')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // إزالة قيد المفتاح الأجنبي أولاً
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
        });

        // إعادة تعريف العمود والقيد إلى الحالة السابقة
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreignId('employee_id')->nullable(false)->change();
            $table->foreign('employee_id')
                ->references('id')
                ->on('employees')
                ->onDelete('restrict');
        });
    }
};
