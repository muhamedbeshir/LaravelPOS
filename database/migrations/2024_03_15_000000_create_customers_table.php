<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('shop_name')->nullable();
            $table->enum('type', ['retail', 'wholesale', 'distributor'])->default('retail');
            $table->enum('payment_type', ['cash', 'credit'])->default('cash');
            $table->decimal('credit_limit', 10, 2)->default(0);
            $table->decimal('credit_balance', 10, 2)->default(0);
            $table->integer('due_days')->default(3);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
}; 