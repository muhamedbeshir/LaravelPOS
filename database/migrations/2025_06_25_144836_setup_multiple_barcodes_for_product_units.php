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
        // 1. Create the new table for multiple barcodes first, if it doesn't exist.
        if (!Schema::hasTable('product_unit_barcodes')) {
            Schema::create('product_unit_barcodes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_unit_id')->constrained()->onDelete('cascade');
                $table->string('barcode')->unique();
                $table->timestamps();
            });
        }

        // 2. Migrate existing data if the old column exists
        if (Schema::hasColumn('product_units', 'barcode')) {
            $productUnits = DB::table('product_units')->whereNotNull('barcode')->where('barcode', '!=', '')->get();

            foreach ($productUnits as $unit) {
                // Avoid inserting duplicates if a barcode already exists
                DB::table('product_unit_barcodes')->updateOrInsert(
                    ['product_unit_id' => $unit->id, 'barcode' => $unit->barcode],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }

            // 3. Now it's safe to drop the old column
            Schema::table('product_units', function (Blueprint $table) {
                $table->dropColumn('barcode');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Add the old column back
        if (!Schema::hasColumn('product_units', 'barcode')) {
            Schema::table('product_units', function (Blueprint $table) {
                $table->string('barcode')->nullable()->unique()->after('id');
            });
        }

        // 2. Try to migrate data back (first barcode for each unit)
        if (Schema::hasTable('product_unit_barcodes')) {
            $barcodes = DB::table('product_unit_barcodes')
                ->orderBy('created_at', 'asc')
                ->get()
                ->groupBy('product_unit_id');

            foreach ($barcodes as $unitId => $unitBarcodes) {
                if ($unitBarcodes->isNotEmpty()) {
                    DB::table('product_units')
                        ->where('id', $unitId)
                        ->update(['barcode' => $unitBarcodes->first()->barcode]);
                }
            }
        }

        // 3. Drop the new table
        Schema::dropIfExists('product_unit_barcodes');
    }
};
