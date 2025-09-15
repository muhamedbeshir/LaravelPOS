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
        // تحديث القيم الحالية في عمود status
        DB::table('purchases')->where('status', 'completed')->update([
            'status' => DB::raw("CASE 
                WHEN remaining_amount <= 0 THEN 'paid' 
                WHEN paid_amount > 0 THEN 'partially_paid' 
                ELSE 'pending' 
                END")
        ]);

        // تغيير القيمة الافتراضية لعمود status
        Schema::table('purchases', function (Blueprint $table) {
            $table->string('status')->default('pending')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // إعادة القيم إلى 'completed'
        DB::table('purchases')->whereIn('status', ['paid', 'partially_paid', 'pending'])->update([
            'status' => 'completed'
        ]);

        // إعادة القيمة الافتراضية إلى 'completed'
        Schema::table('purchases', function (Blueprint $table) {
            $table->string('status')->default('completed')->change();
        });
    }
};
