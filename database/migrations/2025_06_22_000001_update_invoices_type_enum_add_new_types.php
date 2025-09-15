<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Expand the 'type' enum column to include new payment types
        DB::statement("ALTER TABLE `invoices` CHANGE `type` `type` ENUM('cash','credit','card','bank','wallet','visa','transfer') NOT NULL DEFAULT 'cash'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to original enum values
        DB::statement("ALTER TABLE `invoices` CHANGE `type` `type` ENUM('cash','credit') NOT NULL DEFAULT 'cash'");
    }
}; 