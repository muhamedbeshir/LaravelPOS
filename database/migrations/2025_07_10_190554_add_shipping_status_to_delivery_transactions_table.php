<?php

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
        Schema::table('delivery_transactions', function (Blueprint $table) {
            $table->foreignId('shipping_status_id')->nullable()->after('shipping_company_id')->constrained('shipping_statuses')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_transactions', function (Blueprint $table) {
            $table->dropForeign(['shipping_status_id']);
            $table->dropColumn('shipping_status_id');
        });
    }
};
