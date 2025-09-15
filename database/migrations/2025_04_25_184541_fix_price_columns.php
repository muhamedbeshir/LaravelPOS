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
        // Add back these columns if they don't exist
        if (!Schema::hasColumn('product_units', 'main_price')) {
            Schema::table('product_units', function (Blueprint $table) {
                $table->decimal('main_price', 10, 2)->default(0)->after('barcode');
                $table->decimal('app_price', 10, 2)->nullable()->after('main_price');
                $table->decimal('other_price', 10, 2)->nullable()->after('app_price');
            });
            
            // Populate the columns with data from the new price system
            $productUnits = DB::table('product_units')->get();
            
            foreach ($productUnits as $productUnit) {
                // Get the main price (main_price) from product_unit_prices
                $mainPrice = DB::table('product_unit_prices')
                    ->join('price_types', 'product_unit_prices.price_type_id', '=', 'price_types.id')
                    ->where('product_unit_prices.product_unit_id', $productUnit->id)
                    ->where('price_types.code', 'main_price')
                    ->value('product_unit_prices.value') ?? 0;
                
                // Get the second price (app_price)
                $appPrice = DB::table('product_unit_prices')
                    ->join('price_types', 'product_unit_prices.price_type_id', '=', 'price_types.id')
                    ->where('product_unit_prices.product_unit_id', $productUnit->id)
                    ->where(function($query) {
                        $query->where('price_types.code', 'price_2')
                              ->orWhere('price_types.code', 'app_price');
                    })
                    ->value('product_unit_prices.value');
                
                // Get the third price (other_price)
                $otherPrice = DB::table('product_unit_prices')
                    ->join('price_types', 'product_unit_prices.price_type_id', '=', 'price_types.id')
                    ->where('product_unit_prices.product_unit_id', $productUnit->id)
                    ->where(function($query) {
                        $query->where('price_types.code', 'price_3')
                              ->orWhere('price_types.code', 'other_price');
                    })
                    ->value('product_unit_prices.value');
                
                // Update the product unit with the prices
                DB::table('product_units')
                    ->where('id', $productUnit->id)
                    ->update([
                        'main_price' => $mainPrice,
                        'app_price' => $appPrice,
                        'other_price' => $otherPrice
                    ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove the columns if they exist
        if (Schema::hasColumn('product_units', 'main_price')) {
            Schema::table('product_units', function (Blueprint $table) {
                $table->dropColumn(['main_price', 'app_price', 'other_price']);
            });
        }
    }
};
