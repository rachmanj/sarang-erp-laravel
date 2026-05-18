<?php

namespace App\Console\Commands;

use App\Models\Accounting\SalesInvoice;
use App\Models\Accounting\SalesInvoiceLine;
use App\Services\Accounting\SalesInvoicePostingMath;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillDraftSalesInvoiceLineAmountsCommand extends Command
{
    protected $signature = 'sales-invoices:backfill-draft-line-amounts-from-pricing
                            {--dry-run : Show changes without saving}
                            {--invoice= : Only process this sales_invoices.id}';

    protected $description = 'Recalculate draft sales invoice line `amount` and header `total_amount` from qty × unit_price and tax (same as store/update).';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $query = SalesInvoice::query()
            ->where('status', 'draft')
            ->with(['lines.taxCode', 'lines.deliveryOrderLine.salesOrderLine'])
            ->orderBy('id');

        if ($this->option('invoice') !== null && $this->option('invoice') !== '') {
            $query->where('id', (int) $this->option('invoice'));
        }

        /** @var list<array{0: string, 1: string, 2: string, 3: string, 4: string}> $rows */
        $rows = [];
        $invoiceIdsToRetotal = [];

        foreach ($query->cursor() as $invoice) {
            foreach ($invoice->lines as $line) {
                $newAmount = SalesInvoicePostingMath::computedGrossAmountForLine($line);
                if (round((float) $line->amount, 2) === round($newAmount, 2)) {
                    continue;
                }

                $ref = $invoice->invoice_no ?? 'SI#'.$invoice->id;
                $rows[] = [
                    $ref,
                    'line #'.$line->id,
                    number_format((float) $line->amount, 2, '.', ''),
                    number_format($newAmount, 2, '.', ''),
                    $dryRun ? 'dry-run' : 'updated',
                ];

                if (! $dryRun) {
                    SalesInvoiceLine::whereKey($line->id)->update(['amount' => $newAmount, 'net_amount' => $newAmount]);
                }
                $invoiceIdsToRetotal[$invoice->id] = true;
            }
        }

        if ($rows === []) {
            $this->info('No draft sales invoice lines needed changes.');

            return self::SUCCESS;
        }

        $this->table(['Invoice', 'Line', 'Before', 'After', 'Status'], $rows);

        if (! $dryRun && $invoiceIdsToRetotal !== []) {
            DB::transaction(function () use ($invoiceIdsToRetotal) {
                foreach (array_keys($invoiceIdsToRetotal) as $invoiceId) {
                    $invoice = SalesInvoice::find($invoiceId);
                    $lineSum = (float) SalesInvoiceLine::where('invoice_id', $invoiceId)->sum('amount');
                    $headerDisc = (float) ($invoice?->discount_amount ?? 0);
                    $net = round($lineSum - $headerDisc, 2);
                    SalesInvoice::whereKey($invoiceId)->update(['total_amount' => $net]);
                }
            });
        }

        $this->info($dryRun ? 'Dry-run complete; no rows saved.' : 'Backfill complete.');

        return self::SUCCESS;
    }
}
