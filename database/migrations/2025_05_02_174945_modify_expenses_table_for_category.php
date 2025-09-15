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
        Schema::table('expenses', function (Blueprint $table) {
            // Add the new foreign key column. Make it nullable for now, or handle existing data.
            // Ensure it's added after a relevant column, like 'amount'.
            $table->foreignId('expense_category_id')->nullable()->after('amount')->constrained('expense_categories')->restrictOnDelete();

            // Drop the old column AFTER adding the new one and potentially migrating data.
            $table->dropColumn('spent_on');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            // Add the old column back first
            $table->string('spent_on')->after('amount'); // Adjust position as needed

            // Drop the foreign key constraint and the column
            $table->dropForeign(['expense_category_id']);
            $table->dropColumn('expense_category_id');
        });
    }
};
