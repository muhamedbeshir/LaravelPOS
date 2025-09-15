<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add 'credit' to the enum list for method column
        DB::statement("ALTER TABLE `invoice_payments` CHANGE `method` `method` ENUM('cash','credit','visa','transfer') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove 'credit' if rolling back
        DB::statement("ALTER TABLE `invoice_payments` CHANGE `method` `method` ENUM('cash','visa','transfer') NOT NULL");
    }
}; 