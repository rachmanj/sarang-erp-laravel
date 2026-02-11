<?php

namespace App\Console\Commands;

use App\Models\Accounting\SalesInvoice;
use App\Models\DeliveryOrderLine;
use Illuminate\Console\Command;

class BackfillSalesInvoiceItemCodes extends Command
{
    protected $signature = 'sales-invoices:backfill-item-codes';

    protected $description = 'Backfill item_code and item_name on Sales Invoice lines from their source Delivery Order lines';

    public function handle(): int
    {
        $invoices = SalesInvoice::whereNotNull('delivery_order_id')->with('lines')->get();
        $updated = 0;

        foreach ($invoices as $si) {
            $doLines = DeliveryOrderLine::where('delivery_order_id', $si->delivery_order_id)->orderBy('id')->get();

            foreach ($si->lines as $i => $sil) {
                if (isset($doLines[$i]) && empty($sil->item_code)) {
                    $sil->update([
                        'item_code' => $doLines[$i]->item_code,
                        'item_name' => $doLines[$i]->item_name ?? $sil->description,
                    ]);
                    $updated++;
                    $this->line("  SI #{$si->invoice_no} line " . ($i + 1) . ": {$doLines[$i]->item_code}");
                }
            }
        }

        $this->info("Backfill done. Updated {$updated} lines.");
        return Command::SUCCESS;
    }
}
