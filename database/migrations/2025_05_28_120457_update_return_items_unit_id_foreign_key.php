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
        // We want to keep the foreign key constraint referencing product_units.id
        // No changes needed in the up method
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No changes needed in the down method either
    }
};
