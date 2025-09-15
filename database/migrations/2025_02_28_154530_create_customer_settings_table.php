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
        Schema::create('customer_settings', function (Blueprint $table) {
            $table->id();
            $table->integer('default_due_days')->default(3);
            $table->boolean('enable_whatsapp_notifications')->default(true);
            $table->boolean('send_invoice_notifications')->default(true);
            $table->boolean('send_due_date_reminders')->default(true);
            $table->integer('reminder_days_before')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_settings');
    }
};
