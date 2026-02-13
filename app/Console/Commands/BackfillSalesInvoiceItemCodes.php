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
        $invoices = SalesInvoice::with(['lines', 'deliveryOrders'])->get();
        $updated = 0;

        foreach ($invoices as $si) {
            $doIds = $si->deliveryOrders->pluck('id')->all();
            if (empty($doIds)) {
                continue;
            }

            foreach ($si->lines as $i => $sil) {
                if (!empty($sil->item_code)) {
                    continue;
                }

                $doLine = null;
                if ($sil->delivery_order_line_id) {
                    $doLine = DeliveryOrderLine::find($sil->delivery_order_line_id);
                }
                if (!$doLine && isset($doIds[0])) {
                    $doLines = DeliveryOrderLine::where('delivery_order_id', $doIds[0])->orderBy('id')->get();
                    $doLine = $doLines[$i] ?? null;
                }

                if ($doLine) {
                    $sil->update([
                        'item_code' => $doLine->item_code ?? optional($doLine->inventoryItem)->code,
                        'item_name' => $doLine->item_name ?? optional($doLine->inventoryItem)->name ?? $sil->description,
                    ]);
                    $updated++;
                    $this->line("  SI #{$si->invoice_no} line " . ($i + 1) . ": " . ($doLine->item_code ?? optional($doLine->inventoryItem)->code ?? ''));
                }
            }
        }

        $this->info("Backfill done. Updated {$updated} lines.");
        return Command::SUCCESS;
    }
}
