<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('restrict');
            $table->foreignId('unit_id')->constrained()->onDelete('restrict');
            $table->decimal('quantity', 10, 2);
            $table->decimal('before_quantity', 10, 2); // الكمية قبل العملية
            $table->decimal('after_quantity', 10, 2);  // الكمية بعد العملية
            $table->string('movement_type'); // وارد، منصرف
            $table->string('reference_type'); // نوع المستند: فاتورة شراء، فاتورة بيع، تسوية مخزن
            $table->unsignedBigInteger('reference_id'); // رقم المستند
            $table->foreignId('employee_id')->constrained()->onDelete('restrict');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('stock_movements');
    }
}; 