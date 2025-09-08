<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sales_receipt_allocations')) {
            Schema::create('sales_receipt_allocations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('receipt_id');
                $table->unsignedBigInteger('invoice_id');
                $table->decimal('amount', 18, 2);
                $table->timestamps();
                $table->index(['receipt_id']);
                $table->index(['invoice_id']);
            });
        }
        if (!Schema::hasTable('purchase_payment_allocations')) {
            Schema::create('purchase_payment_allocations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('payment_id');
                $table->unsignedBigInteger('invoice_id');
                $table->decimal('amount', 18, 2);
                $table->timestamps();
                $table->index(['payment_id']);
                $table->index(['invoice_id']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sales_receipt_allocations')) {
            Schema::drop('sales_receipt_allocations');
        }
        if (Schema::hasTable('purchase_payment_allocations')) {
            Schema::drop('purchase_payment_allocations');
        }
    }
};
