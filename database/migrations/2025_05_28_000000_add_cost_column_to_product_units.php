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
        // إضافة عمود التكلفة إذا لم يكن موجودًا
        if (!Schema::hasColumn('product_units', 'cost')) {
            Schema::table('product_units', function (Blueprint $table) {
                $table->decimal('cost', 10, 2)->nullable()->after('other_price');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // حذف عمود التكلفة إذا كان موجودًا
        if (Schema::hasColumn('product_units', 'cost')) {
            Schema::table('product_units', function (Blueprint $table) {
                $table->dropColumn('cost');
            });
        }
    }
}; 