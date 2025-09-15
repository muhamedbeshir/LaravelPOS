<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FixInvoiceItemsUnitId extends Migration
{
    /**
     * Run the migration.
     *
     * @return void
     */
    public function up()
    {
        // First, drop the foreign key constraint on invoice_items.unit_id
        try {
            // Get the actual constraint name from the database
            $constraints = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'invoice_items' 
                AND COLUMN_NAME = 'unit_id' 
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ");
            
            if (!empty($constraints)) {
                $constraintName = $constraints[0]->CONSTRAINT_NAME;
                
                // Drop the constraint using raw SQL to avoid Laravel's naming conventions
                DB::statement("ALTER TABLE invoice_items DROP FOREIGN KEY `{$constraintName}`");
                Log::info("Dropped foreign key constraint {$constraintName} from invoice_items table");
            } else {
                Log::info("No foreign key constraint found on invoice_items.unit_id");
            }
        } catch (\Exception $e) {
            Log::error("Error dropping foreign key constraint: " . $e->getMessage());
        }
        
        // Log the current state of problematic invoice_items
        $problematicItems = DB::table('invoice_items as ii')
            ->leftJoin('product_units as pu', function($join) {
                $join->on('ii.unit_id', '=', 'pu.id')
                    ->on('ii.product_id', '=', 'pu.product_id');
            })
            ->whereNull('pu.id') // This identifies invoice_items where unit_id doesn't match a product_units.id for that product
            ->select('ii.id', 'ii.invoice_id', 'ii.product_id', 'ii.unit_id')
            ->get();

        Log::info('Found ' . count($problematicItems) . ' invoice items with incorrect unit_id values');

        // Process each problematic invoice item
        foreach ($problematicItems as $item) {
            Log::info("Processing problematic invoice item: ", [
                'invoice_item_id' => $item->id,
                'invoice_id' => $item->invoice_id,
                'product_id' => $item->product_id,
                'current_unit_id' => $item->unit_id
            ]);

            // Assumption: The current unit_id in invoice_items is actually a reference to units.id (generic unit)
            // We need to find the product_units.id that corresponds to this product_id and generic unit_id
            $correctProductUnit = DB::table('product_units')
                ->where('product_id', $item->product_id)
                ->where('unit_id', $item->unit_id) // This is now interpreted as the generic unit_id
                ->first();

            if ($correctProductUnit) {
                // Found the correct product_unit, update the invoice_item
                DB::table('invoice_items')
                    ->where('id', $item->id)
                    ->update(['unit_id' => $correctProductUnit->id]);

                Log::info("Fixed invoice item unit_id: ", [
                    'invoice_item_id' => $item->id,
                    'old_unit_id' => $item->unit_id,
                    'new_unit_id' => $correctProductUnit->id
                ]);
            } else {
                // Could not find a matching product_unit - this is a more serious issue
                // Try to find any product_unit for this product and use the main unit
                $fallbackProductUnit = DB::table('product_units')
                    ->where('product_id', $item->product_id)
                    ->where('is_main_unit', 1)
                    ->first();

                if (!$fallbackProductUnit) {
                    $fallbackProductUnit = DB::table('product_units')
                        ->where('product_id', $item->product_id)
                        ->first();
                }

                if ($fallbackProductUnit) {
                    // Use the fallback product_unit
                    DB::table('invoice_items')
                        ->where('id', $item->id)
                        ->update(['unit_id' => $fallbackProductUnit->id]);

                    Log::warning("Could not find exact product_unit match, using fallback: ", [
                        'invoice_item_id' => $item->id,
                        'product_id' => $item->product_id,
                        'old_unit_id' => $item->unit_id,
                        'fallback_unit_id' => $fallbackProductUnit->id
                    ]);
                } else {
                    // Critical error - no product_unit found for this product
                    Log::error("Critical: No product_unit found for product: ", [
                        'invoice_item_id' => $item->id,
                        'product_id' => $item->product_id
                    ]);
                }
            }
        }

        // Log summary
        $fixedCount = count($problematicItems) - DB::table('invoice_items as ii')
            ->leftJoin('product_units as pu', function($join) {
                $join->on('ii.unit_id', '=', 'pu.id')
                    ->on('ii.product_id', '=', 'pu.product_id');
            })
            ->whereNull('pu.id')
            ->count();

        Log::info("Fixed $fixedCount out of " . count($problematicItems) . " problematic invoice items");
        
        // Re-add the foreign key constraint but now to product_units.id
        try {
            Schema::table('invoice_items', function (Blueprint $table) {
                $table->foreign('unit_id')->references('id')->on('product_units');
            });
            Log::info("Added foreign key constraint on invoice_items.unit_id referencing product_units.id");
        } catch (\Exception $e) {
            Log::error("Error adding foreign key constraint: " . $e->getMessage());
        }
    }

    /**
     * Reverse the migration.
     *
     * @return void
     */
    public function down()
    {
        // This migration cannot be safely reversed as it would require knowing the original unit_id values
        Log::warning("Attempted to reverse FixInvoiceItemsUnitId migration - this is not supported");
    }
} 