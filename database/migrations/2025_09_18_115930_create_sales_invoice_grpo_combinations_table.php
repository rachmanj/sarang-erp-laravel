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
        Schema::create('sales_invoice_grpo_combinations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sales_invoice_id');
            $table->unsignedBigInteger('goods_receipt_id');
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('sales_invoice_id')->references('id')->on('sales_invoices')->onDelete('cascade');
            $table->foreign('goods_receipt_id')->references('id')->on('goods_receipts')->onDelete('cascade');

            // Unique constraint to prevent duplicate combinations
            $table->unique(['sales_invoice_id', 'goods_receipt_id'], 'si_grpo_unique');

            // Indexes for performance
            $table->index('sales_invoice_id');
            $table->index('goods_receipt_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_invoice_grpo_combinations');
    }
};
