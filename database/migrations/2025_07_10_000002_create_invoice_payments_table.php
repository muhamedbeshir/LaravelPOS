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
        // 1) Create invoice_payments table if it does not exist
        Schema::create('invoice_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->onDelete('cascade');
            $table->enum('method', ['cash', 'visa', 'transfer'])->index();
            $table->decimal('amount', 15, 2);
            $table->string('reference')->nullable();
            $table->timestamps();
        });

        // 2) Add the new 'mixed' payment type to invoices.type enum
        DB::statement("ALTER TABLE `invoices` CHANGE `type` `type` ENUM('cash','credit','card','bank','wallet','visa','transfer','mixed') NOT NULL DEFAULT 'cash'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove invoice_payments table
        Schema::dropIfExists('invoice_payments');

        // Revert the enum alteration (remove 'mixed'). Adjust to previous set of values.
        DB::statement("ALTER TABLE `invoices` CHANGE `type` `type` ENUM('cash','credit','card','bank','wallet','visa','transfer') NOT NULL DEFAULT 'cash'");
    }
}; 