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
        if (Schema::hasTable('price_types')) {
            // Add default price types
            $defaultPriceTypes = [
                [
                    'name' => 'سعر رئيسي',
                    'code' => 'main_price',
                    'sort_order' => 1,
                    'is_default' => true,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'name' => 'سعر 2',
                    'code' => 'price_2',
                    'sort_order' => 2,
                    'is_default' => false,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'name' => 'سعر 3',
                    'code' => 'price_3',
                    'sort_order' => 3,
                    'is_default' => false,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            ];

            DB::table('price_types')->insert($defaultPriceTypes);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('price_types')) {
            // Delete default price types
            DB::table('price_types')->whereIn('code', ['main_price', 'price_2', 'price_3'])->delete();
        }
    }
};
