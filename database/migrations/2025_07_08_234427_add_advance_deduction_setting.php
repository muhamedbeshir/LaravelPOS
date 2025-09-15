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
        // إضافة إعداد للتحكم في خصم السلف من الرواتب
        Setting::updateOrCreate(
            ['key' => 'auto_deduct_advances'],
            [
                'value' => '1', // افتراضيًا مفعل
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
        Setting::where('key', 'auto_deduct_advances')->delete();
    }
};
