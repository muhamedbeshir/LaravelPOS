<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Change the price_type column to string (varchar)
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('price_type')->default('main_price')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // If you want to revert, change back to enum (adjust values as needed)
        Schema::table('invoices', function (Blueprint $table) {
            $table->enum('price_type', ['retail', 'wholesale'])->default('retail')->change();
        });
    }
}; 