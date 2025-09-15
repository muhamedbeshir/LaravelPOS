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
        // Get all product units
        $productUnits = DB::table('product_units')->get();
        
        // Get the price type IDs
        $mainPriceTypeId = DB::table('price_types')->where('code', 'main_price')->value('id');
        $price2TypeId = DB::table('price_types')->where('code', 'price_2')->value('id');
        $price3TypeId = DB::table('price_types')->where('code', 'price_3')->value('id');
        
        if (!$mainPriceTypeId || !$price2TypeId || !$price3TypeId) {
            throw new \Exception('Price types not found. Run the create_default_price_types migration first.');
        }
        
        $productUnitPrices = [];
        
        foreach ($productUnits as $productUnit) {
            // Add main_price (required field)
            $productUnitPrices[] = [
                'product_unit_id' => $productUnit->id,
                'price_type_id' => $mainPriceTypeId,
                'value' => $productUnit->main_price,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ];
            
            // Add app_price (if exists)
            if (!is_null($productUnit->app_price)) {
                $productUnitPrices[] = [
                    'product_unit_id' => $productUnit->id,
                    'price_type_id' => $price2TypeId,
                    'value' => $productUnit->app_price,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }
            
            // Add other_price (if exists)
            if (!is_null($productUnit->other_price)) {
                $productUnitPrices[] = [
                    'product_unit_id' => $productUnit->id,
                    'price_type_id' => $price3TypeId,
                    'value' => $productUnit->other_price,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }
        }
        
        // Insert all prices in batch
        if (!empty($productUnitPrices)) {
            DB::table('product_unit_prices')->insert($productUnitPrices);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This is a one-way migration - we cannot easily restore the data
        // if we were to reverse it. The old columns will still exist,
        // but they won't be used anymore.
        DB::table('product_unit_prices')->truncate();
    }
};
