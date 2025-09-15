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
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->string('shift_number')->unique();
            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();
            $table->decimal('opening_balance', 10, 2)->default(0);
            $table->decimal('cash_sales', 10, 2)->default(0);
            $table->decimal('card_sales', 10, 2)->default(0);
            $table->decimal('bank_transfer_sales', 10, 2)->default(0);
            $table->decimal('wallet_sales', 10, 2)->default(0);
            $table->decimal('withdrawal_amount', 10, 2)->default(0);
            $table->decimal('returns_amount', 10, 2)->default(0);
            $table->decimal('expected_closing_balance', 10, 2)->default(0);
            $table->decimal('actual_closing_balance', 10, 2)->nullable();
            $table->decimal('difference', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_closed')->default(false);
            $table->foreignId('main_cashier_id')->constrained('users');
            $table->timestamps();
        });
        
        Schema::create('shift_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_id')->constrained('shifts')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('join_time')->nullable();
            $table->timestamp('leave_time')->nullable();
            $table->timestamps();
        });

        Schema::create('shift_withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_id')->constrained('shifts')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->text('reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shift_withdrawals');
        Schema::dropIfExists('shift_users');
        Schema::dropIfExists('shifts');
    }
};
