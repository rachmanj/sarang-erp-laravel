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
        Schema::create('inventory_valuations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('item_id');
            $table->date('valuation_date');
            $table->integer('quantity_on_hand');
            $table->decimal('unit_cost', 15, 2);
            $table->decimal('total_value', 15, 2);
            $table->enum('valuation_method', ['fifo', 'lifo', 'weighted_average']);
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('item_id')->references('id')->on('inventory_items')->onDelete('cascade');

            // Unique constraint and indexes
            $table->unique(['item_id', 'valuation_date']);
            $table->index('valuation_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_valuations');
    }
};
