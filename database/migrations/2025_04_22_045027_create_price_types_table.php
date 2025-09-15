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
        // Schema::create('price_types', function (Blueprint $table) {
        //     $table->id();
        //     $table->string('name');
        //     $table->string('code')->unique()->comment('e.g., "main_price", "price_2", "price_3"');
        //     $table->integer('sort_order')->default(1);
        //     $table->boolean('is_default')->default(false);
        //     $table->boolean('is_active')->default(true);
        //     $table->timestamps();
        //     $table->softDeletes();
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('price_types');
    }
};
