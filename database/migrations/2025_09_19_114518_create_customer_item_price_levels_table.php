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
        Schema::create('customer_item_price_levels', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_partner_id');
            $table->unsignedBigInteger('inventory_item_id');
            $table->enum('price_level', ['1', '2', '3']);
            $table->decimal('custom_price', 15, 2)->nullable(); // Override price if needed
            $table->timestamps();

            // Unique constraint for customer-item combination
            $table->unique(['business_partner_id', 'inventory_item_id'], 'unique_customer_item');

            // Foreign key constraints
            $table->foreign('business_partner_id')->references('id')->on('business_partners')->onDelete('cascade');
            $table->foreign('inventory_item_id')->references('id')->on('inventory_items')->onDelete('cascade');

            // Indexes
            $table->index('business_partner_id');
            $table->index('inventory_item_id');
            $table->index('price_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_item_price_levels');
    }
};
