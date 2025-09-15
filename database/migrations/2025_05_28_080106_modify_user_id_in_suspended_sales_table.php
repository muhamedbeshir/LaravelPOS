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
        Schema::table('suspended_sales', function (Blueprint $table) {
            // Drop foreign key if exists (name might vary)
            // Assuming the default naming convention: tablename_columnname_foreign
            if (Schema::hasColumn('suspended_sales', 'user_id')) {
                try {
                    // Check if foreign key exists before trying to drop it
                    // This requires a more complex check depending on DB driver or by querying information_schema
                    // For simplicity, we'll try to drop and catch exception if it doesn't exist or name is different
                    $table->dropForeign('suspended_sales_user_id_foreign');
                } catch (\Illuminate\Database\QueryException $e) {
                    // Log or ignore if the constraint does not exist with this exact name
                    // \Log::info('Could not drop foreign key suspended_sales_user_id_foreign: ' . $e->getMessage());
                }
            }

            // Modify the column to be nullable
            $table->unsignedBigInteger('user_id')->nullable()->change();

            // Re-add the foreign key constraint
            $table->foreign('user_id')
                  ->references('id')->on('users')
                  ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('suspended_sales', function (Blueprint $table) {
            // Drop foreign key
            if (Schema::hasColumn('suspended_sales', 'user_id')) {
                 try {
                    $table->dropForeign('suspended_sales_user_id_foreign');
                } catch (\Illuminate\Database\QueryException $e) {
                    // \Log::info('Could not drop foreign key suspended_sales_user_id_foreign for down: ' . $e->getMessage());
                }
            }

            // Revert the column to not nullable
            $table->unsignedBigInteger('user_id')->nullable(false)->change();

            // Re-add the foreign key constraint
            $table->foreign('user_id')
                  ->references('id')->on('users')
                  ->cascadeOnDelete();
        });
    }
};
