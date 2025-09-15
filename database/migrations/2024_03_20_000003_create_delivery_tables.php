<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_zones', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('base_cost', 10, 2);
            $table->decimal('additional_cost_per_km', 10, 2)->default(0);
            $table->decimal('minimum_order_value', 10, 2)->default(0);
            $table->integer('estimated_delivery_time')->comment('بالدقائق');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('delivery_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->onDelete('restrict');
            $table->foreignId('delivery_zone_id')->constrained()->onDelete('restrict');
            $table->foreignId('employee_id')->constrained()->onDelete('restrict');
            $table->date('scheduled_date');
            $table->string('scheduled_time_slot');
            $table->timestamp('actual_delivery_time')->nullable();
            $table->enum('status', [
                'pending',
                'assigned',
                'out_for_delivery',
                'delivered',
                'failed',
                'cancelled'
            ])->default('pending');
            $table->text('delivery_notes')->nullable();
            $table->string('customer_signature')->nullable();
            $table->string('delivery_proof_image')->nullable();
            $table->decimal('delivery_cost', 10, 2);
            $table->decimal('actual_distance', 10, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('delivery_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_schedule_id')->constrained()->onDelete('cascade');
            $table->string('status');
            $table->text('notes')->nullable();
            $table->decimal('location_lat', 10, 8)->nullable();
            $table->decimal('location_lng', 10, 8)->nullable();
            $table->foreignId('employee_id')->constrained()->onDelete('restrict');
            $table->timestamps();
        });

        Schema::create('delivery_time_slots', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('max_deliveries')->default(10);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_status_history');
        Schema::dropIfExists('delivery_schedules');
        Schema::dropIfExists('delivery_time_slots');
        Schema::dropIfExists('delivery_zones');
    }
}; 