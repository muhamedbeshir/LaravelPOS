<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            // Get the actual constraint name from the database
            $constraints = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'return_items' 
                AND COLUMN_NAME = 'unit_id' 
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ");
            
            if (!empty($constraints)) {
                $constraintName = $constraints[0]->CONSTRAINT_NAME;
                
                // Drop the constraint using raw SQL to avoid Laravel's naming conventions
                DB::statement("ALTER TABLE return_items DROP FOREIGN KEY `{$constraintName}`");
                Log::info("Dropped foreign key constraint {$constraintName} from return_items table");
                
                // Add the new foreign key constraint referencing product_units table
                Schema::table('return_items', function (Blueprint $table) {
                    $table->foreign('unit_id')->references('id')->on('product_units');
                });
                Log::info("Added foreign key constraint on return_items.unit_id referencing product_units.id");
            } else {
                Log::info("No foreign key constraint found on return_items.unit_id");
                
                // Add the foreign key constraint if it doesn't exist
                Schema::table('return_items', function (Blueprint $table) {
                    $table->foreign('unit_id')->references('id')->on('product_units');
                });
                Log::info("Added foreign key constraint on return_items.unit_id referencing product_units.id");
            }
        } catch (\Exception $e) {
            Log::error("Error fixing foreign key constraint on return_items.unit_id: " . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            // Get the actual constraint name from the database
            $constraints = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'return_items' 
                AND COLUMN_NAME = 'unit_id' 
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ");
            
            if (!empty($constraints)) {
                $constraintName = $constraints[0]->CONSTRAINT_NAME;
                
                // Drop the constraint using raw SQL
                DB::statement("ALTER TABLE return_items DROP FOREIGN KEY `{$constraintName}`");
                
                // Add back the foreign key constraint referencing units table
                Schema::table('return_items', function (Blueprint $table) {
                    $table->foreign('unit_id')->references('id')->on('units');
                });
            }
        } catch (\Exception $e) {
            Log::error("Error reverting foreign key constraint on return_items.unit_id: " . $e->getMessage());
        }
    }
};
