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
        Schema::create('sales_quotation_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quotation_id');
            $table->unsignedBigInteger('account_id')->nullable();
            $table->unsignedBigInteger('inventory_item_id')->nullable();
            $table->string('item_code', 100)->nullable();
            $table->string('item_name', 255)->nullable();
            $table->string('unit_of_measure', 50)->nullable();
            $table->unsignedBigInteger('order_unit_id')->nullable();
            $table->text('description')->nullable();
            $table->decimal('qty', 15, 2)->default(0);
            $table->decimal('base_quantity', 15, 2)->default(0);
            $table->decimal('unit_conversion_factor', 10, 4)->default(1.0000);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('unit_price_foreign', 15, 2)->default(0);
            $table->decimal('amount', 15, 2)->default(0);
            $table->decimal('amount_foreign', 15, 2)->default(0);
            $table->decimal('freight_cost', 15, 2)->default(0);
            $table->decimal('handling_cost', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->decimal('net_amount', 15, 2)->default(0);
            $table->unsignedBigInteger('tax_code_id')->nullable();
            $table->decimal('vat_rate', 5, 2)->default(0);
            $table->decimal('wtax_rate', 5, 2)->default(0);
            $table->text('notes')->nullable();
            $table->integer('line_order')->default(0);
            $table->timestamps();

            $table->foreign('quotation_id')->references('id')->on('sales_quotations')->onDelete('cascade');
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('set null');
            $table->foreign('inventory_item_id')->references('id')->on('inventory_items')->onDelete('set null');
            $table->foreign('order_unit_id')->references('id')->on('units_of_measure')->onDelete('set null');
            $table->foreign('tax_code_id')->references('id')->on('tax_codes')->onDelete('set null');

            $table->index('quotation_id');
            $table->index('inventory_item_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_quotation_lines');
    }
};
