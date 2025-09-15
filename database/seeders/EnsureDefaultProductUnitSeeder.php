<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProductUnit;
use App\Models\Product;
use App\Models\Unit as GenericUnit; // Alias to avoid confusion

class EnsureDefaultProductUnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $targetProductUnitId = 1;
        $targetProductId = 3; // Assuming 'بيبسي'
        $targetGenericUnitId = 1; // Assuming generic 'قطعة' unit in 'units' table has id 1

        if (!ProductUnit::where('id', $targetProductUnitId)->exists()) {
            $product = Product::find($targetProductId);
            $genericUnit = GenericUnit::find($targetGenericUnitId);

            if ($product && $genericUnit) {
                ProductUnit::create([
                    'id' => $targetProductUnitId, // Explicitly set ID
                    'product_id' => $product->id,
                    'unit_id' => $genericUnit->id, // FK to the generic units table
                    'conversion_factor' => 1.00,
                    // Prices should ideally be set based on actual data or ProductUnitPrice seeder
                    // Using product's base price/cost as a fallback for this example
                    // 'cost' => $product->cost_price ?? 0.00, // Make sure 'cost' is fillable in ProductUnit
                    // Check ProductUnit fillable fields for price columns or rely on ProductUnitPrice table
                    'is_main_unit' => true, 
                    'barcode' => $product->barcode, // Or a specific barcode for this unit
                    'is_active' => true,
                ]);
                $this->command->info("ProductUnit ID {$targetProductUnitId} for Product ID {$targetProductId} linked to Generic Unit ID {$targetGenericUnitId} created.");
            } else {
                if (!$product) {
                    $this->command->warn("Product with ID {$targetProductId} not found. Cannot create ProductUnit ID {$targetProductUnitId}.");
                }
                if (!$genericUnit) {
                    $this->command->warn("Generic Unit with ID {$targetGenericUnitId} not found. Cannot create ProductUnit ID {$targetProductUnitId}.");
                }
            }
        } else {
            $this->command->info("ProductUnit with ID {$targetProductUnitId} already exists.");
        }
    }
} 