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
        Schema::create('inventory_warehouse_stock', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('warehouse_id');
            $table->integer('quantity_on_hand')->default(0);
            $table->integer('reserved_quantity')->default(0);
            $table->integer('available_quantity')->default(0);
            $table->integer('min_stock_level')->default(0);
            $table->integer('max_stock_level')->default(0);
            $table->integer('reorder_point')->default(0);
            $table->timestamps();

            // Unique constraint for item-warehouse combination
            $table->unique(['item_id', 'warehouse_id'], 'unique_item_warehouse');

            // Foreign key constraints
            $table->foreign('item_id')->references('id')->on('inventory_items')->onDelete('cascade');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');

            // Indexes
            $table->index('item_id');
            $table->index('warehouse_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_warehouse_stock');
    }
};
