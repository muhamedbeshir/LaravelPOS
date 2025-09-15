<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Shift;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // This migration doesn't modify the database schema
        // It ensures the returns_amount field is in the fillable array of the Shift model
        // The field is already defined in the create_shifts_table migration
        
        // No database changes needed as this is just ensuring the model property is set correctly
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No changes to reverse
    }
};
