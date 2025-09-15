<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Add indexes to frequently searched columns in the products table
     * to improve query performance for search operations.
     */
    public function up(): void
    {
        $existingIndexes = $this->getExistingIndexes('products');
        
        Schema::table('products', function (Blueprint $table) use ($existingIndexes) {
            // Change the alert_quantity column type
            if (Schema::hasColumn('products', 'alert_quantity')) {
                $table->integer('alert_quantity')->default(0)->change();
            }

            // Add index to the name column for faster text searches
            if (!in_array('products_name_index', $existingIndexes)) {
                $table->index('name');
            }
            
            // Add index to barcode for faster lookups
            if (!in_array('products_barcode_index', $existingIndexes)) {
                $table->index('barcode');
            }
            
            // Add index for stock_quantity which is frequently used in filtering
            if (!in_array('products_stock_quantity_index', $existingIndexes)) {
                $table->index('stock_quantity');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Revert the alert_quantity column type
            if (Schema::hasColumn('products', 'alert_quantity')) {
                $table->decimal('alert_quantity', 10, 2)->default(0)->change();
            }

            // We'll attempt to drop indexes but catch exceptions
            // in case they don't exist
            try {
                $table->dropIndex(['name']);
            } catch (\Exception $e) {
                // Index doesn't exist, continue
            }
            
            try {
                $table->dropIndex(['barcode']);
            } catch (\Exception $e) {
                // Index doesn't exist, continue
            }
            
            try {
                $table->dropIndex(['stock_quantity']);
            } catch (\Exception $e) {
                // Index doesn't exist, continue
            }
        });
    }
    
    /**
     * Get existing indexes for a table
     */
    private function getExistingIndexes($table)
    {
        $indexes = [];
        
        // Get all indexes from the table
        $indexList = DB::select("SHOW INDEXES FROM {$table}");
        
        // Extract unique index names
        foreach ($indexList as $index) {
            $indexes[] = $index->Key_name;
        }
        
        return array_unique($indexes);
    }
};
