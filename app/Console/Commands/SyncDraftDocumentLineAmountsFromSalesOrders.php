<?php

namespace App\Console\Commands;

use App\Models\Accounting\SalesInvoice;
use App\Models\Accounting\SalesInvoiceLine;
use App\Models\DeliveryOrderLine;
use App\Models\SalesOrderLine;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncDraftDocumentLineAmountsFromSalesOrders extends Command
{
    protected $signature = 'document:sync-draft-line-amounts-from-sales-orders {--dry-run : Hanya menampilkan perbedaan tanpa menyimpan} {--skip-delivery-orders : Lewati penyelarasan baris Delivery Order} {--skip-sales-invoices : Lewati penyelarasan Sales Invoice (draft)} {--do-statuses=draft : Status DO (pisahkan koma); diabaikan jika --all-non-cancelled-do} {--all-non-cancelled-do : Semua DO kecuali cancelled}';

    protected $description = 'Menyelaraskan nominal baris DO dan Sales Invoice (draft) dengan rumus PPN/PPh pemotongan pada Sales Order Line';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $rows = [];

        if (! $this->option('skip-delivery-orders')) {
            $rows = array_merge($rows, $this->syncDeliveryOrderLines($dryRun));
        }

        if (! $this->option('skip-sales-invoices')) {
            $rows = array_merge($rows, $this->syncDraftSalesInvoiceLines($dryRun));
        }

        if ($rows === []) {
            $this->info('Tidak ada baris yang cocok dengan filter, atau tidak ada perbedaan.');

            return Command::SUCCESS;
        }

        $this->table(
            ['Jenis', 'Dokumen', 'Baris', 'Sebelum', 'Sesudah'],
            $rows
        );

        $this->info($dryRun ? 'Dry-run: tidak ada perubahan yang disimpan.' : 'Penyelarasan selesai.');

        return Command::SUCCESS;
    }

    /**
     * @return list<array{0: string, 1: string, 2: string, 3: string, 4: string}>
     */
    private function syncDeliveryOrderLines(bool $dryRun): array
    {
        $statusFilter = $this->resolveDeliveryOrderStatuses();
        $query = DeliveryOrderLine::query()
            ->with(['salesOrderLine', 'deliveryOrder'])
            ->whereNotNull('sales_order_line_id')
            ->whereHas('deliveryOrder', function ($q) use ($statusFilter) {
                if ($statusFilter === null) {
                    $q->whereNotIn('status', ['cancelled', 'reversed']);
                } else {
                    $q->whereIn('status', $statusFilter);
                }
            });

        $report = [];
        $updates = [];

        foreach ($query->cursor() as $line) {
            $sol = $line->salesOrderLine;
            if (! $sol) {
                continue;
            }

            $orderedQty = (float) $line->ordered_qty;
            $newAmount = $sol->computeAmountForQuantity($orderedQty);

            if ($this->amountsEqual((float) $line->amount, $newAmount)) {
                continue;
            }

            $do = $line->deliveryOrder;
            $ref = $do ? ($do->do_number ?? '#'.$do->id) : 'DO#'.$line->delivery_order_id;

            $report[] = [
                'DO',
                $ref,
                'dol#'.$line->id,
                $this->formatMoney($line->amount),
                $this->formatMoney($newAmount),
            ];

            $updates[] = [
                'id' => $line->id,
                'amount' => $newAmount,
            ];
        }

        if (! $dryRun && $updates !== []) {
            DB::transaction(function () use ($updates) {
                foreach ($updates as $u) {
                    DeliveryOrderLine::whereKey($u['id'])->update(['amount' => $u['amount']]);
                }
            });
        }

        return $report;
    }

    /**
     * @return list<array{0: string, 1: string, 2: string, 3: string, 4: string}>
     */
    private function syncDraftSalesInvoiceLines(bool $dryRun): array
    {
        $query = SalesInvoiceLine::query()
            ->whereNotNull('delivery_order_line_id')
            ->whereHas('invoice', function ($q) {
                $q->where('status', 'draft');
            })
            ->with(['invoice', 'deliveryOrderLine.salesOrderLine']);

        $report = [];
        $lineUpdates = [];
        $invoiceIds = [];

        foreach ($query->cursor() as $sil) {
            $invoice = $sil->invoice;
            $dol = $sil->deliveryOrderLine;
            $sol = $dol?->salesOrderLine;

            if (! $invoice || ! $sol) {
                continue;
            }

            $newAmount = SalesOrderLine::computeAmountFromPricing(
                (float) $sil->qty,
                (float) $sil->unit_price,
                $sol->vat_rate,
                $sol->wtax_rate
            );

            if ($this->amountsEqual((float) $sil->amount, $newAmount)) {
                continue;
            }

            $ref = $invoice->invoice_no ?? 'SI#'.$invoice->id;

            $report[] = [
                'SI (draft)',
                $ref,
                'sil#'.$sil->id,
                $this->formatMoney($sil->amount),
                $this->formatMoney($newAmount),
            ];

            $lineUpdates[] = [
                'id' => $sil->id,
                'amount' => $newAmount,
            ];
            $invoiceIds[$sil->invoice_id] = true;
        }

        if (! $dryRun && $lineUpdates !== []) {
            DB::transaction(function () use ($lineUpdates, $invoiceIds) {
                foreach ($lineUpdates as $u) {
                    SalesInvoiceLine::whereKey($u['id'])->update(['amount' => $u['amount']]);
                }

                foreach (array_keys($invoiceIds) as $invoiceId) {
                    $total = (float) SalesInvoiceLine::where('invoice_id', $invoiceId)->sum('amount');
                    SalesInvoice::whereKey($invoiceId)->update(['total_amount' => $total]);
                }
            });
        }

        return $report;
    }

    /**
     * @return list<string>|null null = all non-cancelled
     */
    private function resolveDeliveryOrderStatuses(): ?array
    {
        if ($this->option('all-non-cancelled-do')) {
            return null;
        }

        $raw = (string) $this->option('do-statuses');
        $parts = array_filter(array_map('trim', explode(',', $raw)));

        return $parts !== [] ? array_values($parts) : ['draft'];
    }

    private function amountsEqual(float $a, float $b): bool
    {
        return round($a, 2) === round($b, 2);
    }

    private function formatMoney(mixed $value): string
    {
        return number_format((float) $value, 2, '.', '');
    }
}
