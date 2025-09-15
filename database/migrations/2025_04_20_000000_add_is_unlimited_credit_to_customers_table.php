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
        // First add the column
        Schema::table('customers', function (Blueprint $table) {
            $table->boolean('is_unlimited_credit')->default(false)->after('credit_limit');
        });
        
        // Then update existing records with very high credit_limit
        DB::statement('UPDATE customers SET is_unlimited_credit = true WHERE credit_limit > 999999');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('is_unlimited_credit');
        });
    }
}; 