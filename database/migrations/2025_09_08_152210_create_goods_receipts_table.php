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
        Schema::create('goods_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('grn_no')->nullable()->unique();
            $table->date('date');
            $table->unsignedBigInteger('business_partner_id');
            $table->unsignedBigInteger('purchase_order_id')->nullable();
            $table->unsignedBigInteger('source_po_id')->nullable();
            $table->enum('source_type', ['copy', 'manual'])->default('manual');
            $table->string('description')->nullable();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->string('status')->default('draft');
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('source_po_id')->references('id')->on('purchase_orders')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goods_receipts');
    }
};
