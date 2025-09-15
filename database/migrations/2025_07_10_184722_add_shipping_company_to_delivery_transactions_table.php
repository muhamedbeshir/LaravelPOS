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
            $table->foreignId('shipping_company_id')->nullable()->after('status_id')->constrained('shipping_companies')->nullOnDelete();
            $table->decimal('shipping_cost', 10, 2)->nullable()->after('shipping_company_id');
            $table->string('tracking_number')->nullable()->after('shipping_cost');
            $table->dateTime('shipped_at')->nullable()->after('tracking_number');
            $table->dateTime('estimated_delivery_date')->nullable()->after('shipped_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_transactions', function (Blueprint $table) {
            $table->dropForeign(['shipping_company_id']);
            $table->dropColumn([
                'shipping_company_id',
                'shipping_cost',
                'tracking_number',
                'shipped_at',
                'estimated_delivery_date'
            ]);
        });
    }
};
