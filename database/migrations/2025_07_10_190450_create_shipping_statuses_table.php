<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // Added missing import for DB facade

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('shipping_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('color')->default('#6c757d');
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        
        // إدخال البيانات الأولية لحالات الشحن
        $statuses = [
            [
                'name' => 'قيد المعالجة',
                'code' => 'processing',
                'color' => '#ffc107',
                'sort_order' => 10,
                'description' => 'الطلب قيد المعالجة في المخزن'
            ],
            [
                'name' => 'تم الشحن',
                'code' => 'shipped',
                'color' => '#17a2b8',
                'sort_order' => 20,
                'description' => 'تم شحن الطلب مع شركة الشحن'
            ],
            [
                'name' => 'قيد التوصيل',
                'code' => 'in_transit',
                'color' => '#6f42c1',
                'sort_order' => 30,
                'description' => 'الطلب في الطريق للعميل'
            ],
            [
                'name' => 'وصل للفرع',
                'code' => 'arrived_at_branch',
                'color' => '#fd7e14',
                'sort_order' => 40,
                'description' => 'وصل الطلب لفرع التوزيع'
            ],
            [
                'name' => 'خرج للتوصيل',
                'code' => 'out_for_delivery',
                'color' => '#20c997',
                'sort_order' => 50,
                'description' => 'الطلب خرج للتوصيل للعميل'
            ],
            [
                'name' => 'تم التسليم',
                'code' => 'delivered',
                'color' => '#28a745',
                'sort_order' => 60,
                'description' => 'تم تسليم الطلب للعميل'
            ],
            [
                'name' => 'تم الدفع',
                'code' => 'paid',
                'color' => '#198754',
                'sort_order' => 70,
                'description' => 'تم تسليم الطلب وتحصيل المبلغ'
            ],
            [
                'name' => 'فشل التوصيل',
                'code' => 'delivery_failed',
                'color' => '#dc3545',
                'sort_order' => 80,
                'description' => 'فشل توصيل الطلب للعميل'
            ],
            [
                'name' => 'تم الإرجاع',
                'code' => 'returned',
                'color' => '#dc3545',
                'sort_order' => 90,
                'description' => 'تم إرجاع الطلب'
            ],
            [
                'name' => 'ملغي',
                'code' => 'cancelled',
                'color' => '#6c757d',
                'sort_order' => 100,
                'description' => 'تم إلغاء الطلب'
            ]
        ];
        
        foreach ($statuses as $status) {
            DB::table('shipping_statuses')->insert($status);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_statuses');
    }
};
