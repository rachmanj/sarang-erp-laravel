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
        Schema::create('delivery_order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_order_id')->constrained()->onDelete('cascade');
            $table->foreignId('sales_order_line_id')->constrained()->onDelete('cascade');
            $table->foreignId('inventory_item_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('account_id')->nullable()->constrained('accounts')->onDelete('set null');
            $table->string('item_code')->nullable();
            $table->string('item_name')->nullable();
            $table->text('description')->nullable();
            $table->decimal('ordered_qty', 15, 2);
            $table->decimal('reserved_qty', 15, 2)->default(0);
            $table->decimal('picked_qty', 15, 2)->default(0);
            $table->decimal('delivered_qty', 15, 2)->default(0);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('amount', 15, 2);
            $table->string('warehouse_location')->nullable();
            $table->json('serial_numbers')->nullable();
            $table->json('batch_codes')->nullable();
            $table->text('packing_details')->nullable();
            $table->enum('status', ['pending', 'partial_picked', 'picked', 'ready', 'partial_delivered', 'delivered'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['delivery_order_id', 'status']);
            $table->index(['inventory_item_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_order_lines');
    }
};
