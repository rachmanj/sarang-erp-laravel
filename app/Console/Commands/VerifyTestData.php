<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BusinessPartner;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;

class VerifyTestData extends Command
{
    protected $signature = 'test:verify-data';
    protected $description = 'Verify test data for GRPO testing';

    public function handle()
    {
        $this->info('Verifying test data...');

        // Check suppliers
        $suppliers = BusinessPartner::where('partner_type', 'supplier')->get();
        $this->info('Suppliers found: ' . $suppliers->count());
        foreach ($suppliers as $supplier) {
            $this->line('- ' . $supplier->name . ' (ID: ' . $supplier->id . ')');
        }

        // Check Purchase Orders
        $pos = PurchaseOrder::with('lines')->get();
        $this->info('Purchase Orders found: ' . $pos->count());
        foreach ($pos as $po) {
            $this->line('- ' . $po->order_no . ' (ID: ' . $po->id . ') - Status: ' . $po->status);
            $pendingLines = $po->lines->where('pending_qty', '>', 0);
            $this->line('  Lines with pending qty: ' . $pendingLines->count());
            foreach ($pendingLines as $line) {
                $this->line('    * ' . $line->item_name . ' - Qty: ' . $line->pending_qty . ' - Price: ' . number_format($line->unit_price));
            }
        }

        $this->info('Test data verification complete!');
        $this->info('Ready for manual testing at: http://localhost:8000/goods-receipt-pos/create');
    }
}
