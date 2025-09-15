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
        Schema::table('deposits', function (Blueprint $table) {
            // Add the new foreign key column. Make it nullable for now.
            $table->foreignId('deposit_source_id')->nullable()->after('amount')->constrained('deposit_sources')->restrictOnDelete();

            // Drop the old column
            $table->dropColumn('came_from');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deposits', function (Blueprint $table) {
            // Add the old column back
            $table->string('came_from')->after('amount'); 

            // Drop the foreign key constraint and the column
            $table->dropForeign(['deposit_source_id']);
            $table->dropColumn('deposit_source_id');
        });
    }
};
