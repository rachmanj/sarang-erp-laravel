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
        Schema::create('inventory_item_units', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inventory_item_id');
            $table->unsignedBigInteger('unit_id');
            $table->boolean('is_base_unit')->default(false);
            $table->decimal('purchase_price', 15, 2)->default(0);
            $table->decimal('selling_price', 15, 2)->default(0);
            $table->decimal('selling_price_level_2', 15, 2)->nullable();
            $table->decimal('selling_price_level_3', 15, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('inventory_item_id')->references('id')->on('inventory_items')->onDelete('cascade');
            $table->foreign('unit_id')->references('id')->on('units_of_measure')->onDelete('cascade');

            // Unique constraint for item-unit combination
            $table->unique(['inventory_item_id', 'unit_id'], 'unique_item_unit');

            // Indexes
            $table->index('inventory_item_id');
            $table->index('unit_id');
            $table->index('is_base_unit');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_item_units');
    }
};
