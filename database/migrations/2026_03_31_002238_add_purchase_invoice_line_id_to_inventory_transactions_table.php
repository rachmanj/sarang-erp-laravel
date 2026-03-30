<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->foreignId('purchase_invoice_line_id')
                ->nullable()
                ->after('reference_id')
                ->constrained('purchase_invoice_lines')
                ->nullOnDelete();
        });

        $this->backfillPurchaseInvoiceLineIds();

        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->unique('purchase_invoice_line_id');
        });
    }

    private function backfillPurchaseInvoiceLineIds(): void
    {
        $lines = DB::table('purchase_invoice_lines')
            ->whereNotNull('inventory_item_id')
            ->orderBy('id')
            ->get(['id', 'invoice_id', 'inventory_item_id']);

        foreach ($lines as $line) {
            $txId = DB::table('inventory_transactions')
                ->where('reference_type', 'purchase_invoice')
                ->where('reference_id', $line->invoice_id)
                ->where('transaction_type', 'purchase')
                ->where('item_id', $line->inventory_item_id)
                ->whereNull('purchase_invoice_line_id')
                ->orderBy('id')
                ->value('id');

            if ($txId) {
                DB::table('inventory_transactions')
                    ->where('id', $txId)
                    ->update(['purchase_invoice_line_id' => $line->id]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->dropUnique(['purchase_invoice_line_id']);
        });

        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->dropForeign(['purchase_invoice_line_id']);
        });
    }
};
