<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('goods_receipt_po_purchase_invoice', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_invoice_id')->constrained('purchase_invoices')->cascadeOnDelete();
            $table->foreignId('grpo_id')->constrained('goods_receipt_po')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['purchase_invoice_id', 'grpo_id'], 'purchase_invoice_grpo_unique');
        });

        $now = now();
        foreach (DB::table('purchase_invoices')->whereNotNull('goods_receipt_id')->cursor() as $row) {
            DB::table('goods_receipt_po_purchase_invoice')->insertOrIgnore([
                'purchase_invoice_id' => $row->id,
                'grpo_id' => $row->goods_receipt_id,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_po_purchase_invoice');
    }
};
