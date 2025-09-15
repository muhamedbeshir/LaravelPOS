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
        Schema::table('shifts', function (Blueprint $table) {
            // Add columns only if they don't already exist
            if (!Schema::hasColumn('shifts', 'total_sales')) {
                $table->decimal('total_sales', 15, 2)->default(0)->after('closing_notes');
            }
            if (!Schema::hasColumn('shifts', 'total_withdrawals')) {
                $table->decimal('total_withdrawals', 15, 2)->default(0)->after('total_sales');
            }
            if (!Schema::hasColumn('shifts', 'expected_closing_balance')) {
                $table->decimal('expected_closing_balance', 15, 2)->nullable()->after('total_withdrawals');
            }
            if (!Schema::hasColumn('shifts', 'cash_shortage_excess')) {
                $table->decimal('cash_shortage_excess', 15, 2)->nullable()->after('expected_closing_balance');
            }
            
            // Drop old columns if they exist 
            if (Schema::hasColumn('shifts', 'withdrawal_amount')) {
                 $table->dropColumn('withdrawal_amount');
            }
            if (Schema::hasColumn('shifts', 'difference')) {
                 $table->dropColumn('difference');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            // Only drop columns if they exist
            $columnsToDrop = [];
            if (Schema::hasColumn('shifts', 'total_sales')) $columnsToDrop[] = 'total_sales';
            if (Schema::hasColumn('shifts', 'total_withdrawals')) $columnsToDrop[] = 'total_withdrawals';
            if (Schema::hasColumn('shifts', 'expected_closing_balance')) $columnsToDrop[] = 'expected_closing_balance';
            if (Schema::hasColumn('shifts', 'cash_shortage_excess')) $columnsToDrop[] = 'cash_shortage_excess';
            
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }

            // Re-add old columns if needed for rollback and they dont exist
            // if (!Schema::hasColumn('shifts', 'withdrawal_amount')) {
            //     $table->decimal('withdrawal_amount', 15, 2)->default(0);
            // }
            // if (!Schema::hasColumn('shifts', 'difference')) {
            //     $table->decimal('difference', 15, 2)->nullable();
            // }
        });
    }
};
