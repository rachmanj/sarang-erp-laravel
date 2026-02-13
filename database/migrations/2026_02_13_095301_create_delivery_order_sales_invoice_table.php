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
        Schema::create('delivery_order_sales_invoice', function (Blueprint $table) {
            $table->unsignedBigInteger('delivery_order_id');
            $table->unsignedBigInteger('sales_invoice_id');
            $table->timestamps();

            $table->primary(['delivery_order_id', 'sales_invoice_id']);
            $table->foreign('delivery_order_id')->references('id')->on('delivery_orders')->onDelete('cascade');
            $table->foreign('sales_invoice_id')->references('id')->on('sales_invoices')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_order_sales_invoice');
    }
};
