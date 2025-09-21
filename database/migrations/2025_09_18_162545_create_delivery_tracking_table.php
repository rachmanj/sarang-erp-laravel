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
        Schema::create('delivery_trackings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_order_id')->constrained()->onDelete('cascade');
            $table->timestamp('pickup_time')->nullable();
            $table->timestamp('departure_time')->nullable();
            $table->timestamp('estimated_arrival_time')->nullable();
            $table->timestamp('actual_arrival_time')->nullable();
            $table->timestamp('delivery_completion_time')->nullable();
            $table->integer('delivery_duration_minutes')->nullable();
            $table->decimal('distance_km', 8, 2)->nullable();
            $table->decimal('fuel_cost', 15, 2)->default(0);
            $table->decimal('driver_cost', 15, 2)->default(0);
            $table->decimal('vehicle_cost', 15, 2)->default(0);
            $table->decimal('total_logistics_cost', 15, 2)->default(0);
            $table->integer('customer_satisfaction_score')->nullable();
            $table->text('delivery_notes')->nullable();
            $table->string('weather_conditions')->nullable();
            $table->string('traffic_conditions')->nullable();
            $table->integer('delivery_attempts')->default(0);
            $table->text('return_reason')->nullable();
            $table->text('rescheduled_reason')->nullable();
            $table->timestamps();

            $table->index(['delivery_order_id', 'pickup_time']);
            $table->index(['delivery_completion_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_trackings');
    }
};
