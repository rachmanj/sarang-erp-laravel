<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReportPurchaseInvoiceInventoryDuplicates extends Command
{
    protected $signature = 'inventory:report-purchase-invoice-duplicates';

    protected $description = 'List legacy duplicate purchase inventory rows (same PI + item) and purchase rows missing purchase_invoice_line_id';

    public function handle(): int
    {
        $legacyDupes = DB::table('inventory_transactions')
            ->select('reference_id', 'item_id', DB::raw('COUNT(*) as cnt'))
            ->where('reference_type', 'purchase_invoice')
            ->where('transaction_type', 'purchase')
            ->groupBy('reference_id', 'item_id')
            ->having('cnt', '>', 1)
            ->orderBy('reference_id')
            ->get();

        if ($legacyDupes->isEmpty()) {
            $this->info('No duplicate purchase_invoice + item groups found.');
        } else {
            $this->warn('Duplicate groups (same purchase invoice + item, multiple purchase rows):');
            foreach ($legacyDupes as $row) {
                $this->line("  PI #{$row->reference_id} item_id {$row->item_id}: {$row->cnt} rows");
            }
        }

        $missingLineId = DB::table('inventory_transactions')
            ->where('reference_type', 'purchase_invoice')
            ->where('transaction_type', 'purchase')
            ->whereNull('purchase_invoice_line_id')
            ->count();

        if ($missingLineId === 0) {
            $this->info('No purchase rows missing purchase_invoice_line_id.');
        } else {
            $this->warn("Purchase rows with NULL purchase_invoice_line_id: {$missingLineId} (run backfill or fix data).");
        }

        return self::SUCCESS;
    }
}
