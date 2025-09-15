<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Setting;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // إضافة إعداد جديد للتحكم في حساب رواتب الموظفين كمصروفات
        Setting::updateOrCreate(
            ['key' => 'count_salaries_as_expenses'],
            [
                'value' => '1', // افتراضيًا يتم حساب الرواتب كمصروفات
                'group' => 'employees',
                'type' => 'boolean',
                'is_public' => true
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // حذف الإعداد عند التراجع عن الترحيل
        Setting::where('key', 'count_salaries_as_expenses')->delete();
    }
};
