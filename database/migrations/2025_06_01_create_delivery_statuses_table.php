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
        Schema::create('delivery_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('color')->default('#6c757d');
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // إضافة الحالات الافتراضية
        DB::table('delivery_statuses')->insert([
            [
                'name' => 'الطلبية جاهزة في انتظار الخروج',
                'code' => 'ready',
                'color' => '#ffc107',
                'description' => 'الطلبية جاهزة في انتظار الخروج مع موظف التوصيل',
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'خرجت في انتظار الرجوع',
                'code' => 'dispatched',
                'color' => '#007bff',
                'description' => 'الطلبية خرجت مع الموظف وفي انتظار تأكيد التسليم أو الإرجاع',
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'وصلت بانتظار الدفع',
                'code' => 'delivered_pending_payment',
                'color' => '#17a2b8',
                'description' => 'الطلبية وصلت للعميل والموظف بانتظار تحصيل المبلغ',
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'وصلت وتم الدفع',
                'code' => 'paid',
                'color' => '#28a745',
                'description' => 'تم تحصيل مبلغ الطلبية بالكامل وتسليمها بنجاح',
                'sort_order' => 4,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'وصلت لكن تم الارجاع',
                'code' => 'returned',
                'color' => '#dc3545',
                'description' => 'تم إرجاع الطلبية مع موظف التوصيل بعد محاولة التسليم',
                'sort_order' => 5,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_statuses');
    }
}; 