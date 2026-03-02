<?php

namespace App\Console\Commands;

use App\Models\Accounting\SalesInvoice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DeleteSalesInvoices extends Command
{
    protected $signature = 'sales-invoices:delete
                            {invoice_nos : Comma-separated invoice numbers (e.g. 71250800153,71250800065,71250800156)}
                            {--dry-run : Show what would be deleted without actually deleting}
                            {--force : Skip confirmation prompt}';

    protected $description = 'Delete sales invoices and all related records (allocations, journals, document relationships, etc.)';

    public function handle(): int
    {
        $invoiceNos = array_map('trim', explode(',', $this->argument('invoice_nos')));
        $invoiceNos = array_filter($invoiceNos);

        if (empty($invoiceNos)) {
            $this->error('Please provide at least one invoice number.');
            return 1;
        }

        $invoices = SalesInvoice::whereIn('invoice_no', $invoiceNos)->get();

        if ($invoices->isEmpty()) {
            $this->error('No sales invoices found with the given numbers: ' . implode(', ', $invoiceNos));
            return 1;
        }

        $found = $invoices->pluck('invoice_no')->toArray();
        $notFound = array_diff($invoiceNos, $found);
        if (!empty($notFound)) {
            $this->warn('Not found: ' . implode(', ', $notFound));
        }

        $this->table(
            ['Invoice No', 'Date', 'Customer', 'Status', 'Total'],
            $invoices->map(fn ($inv) => [
                $inv->invoice_no,
                $inv->date->format('Y-m-d'),
                $inv->businessPartner?->name ?? '—',
                $inv->status,
                number_format($inv->total_amount, 2),
            ])->toArray()
        );

        $postedCount = $invoices->where('status', 'posted')->count();
        if ($postedCount > 0) {
            $this->warn("{$postedCount} invoice(s) are POSTED. Deleting will remove journals and accounting entries.");
        }

        $dryRun = $this->option('dry-run');
        if ($dryRun) {
            $this->info('[DRY RUN] No changes will be made.');
        }

        if (!$this->option('force') && !$dryRun) {
            if (!$this->confirm('Are you sure you want to permanently delete these sales invoices? This cannot be undone.', false)) {
                $this->info('Cancelled.');
                return 0;
            }
        }

        $deleted = 0;
        $failed = [];

        foreach ($invoices as $invoice) {
            try {
                if (!$dryRun) {
                    $this->deleteSalesInvoice($invoice);
                }
                $this->info('✓ ' . ($dryRun ? 'Would delete' : 'Deleted') . ': ' . $invoice->invoice_no);
                $deleted++;
            } catch (\Throwable $e) {
                $failed[] = ['no' => $invoice->invoice_no, 'error' => $e->getMessage()];
                $this->error('✗ Failed ' . $invoice->invoice_no . ': ' . $e->getMessage());
            }
        }

        $this->newLine();
        $this->info('Summary: ' . $deleted . ' invoice(s) ' . ($dryRun ? 'would be deleted' : 'deleted') . '.');
        if (!empty($failed)) {
            $this->error('Failed: ' . count($failed));
            return 1;
        }

        return 0;
    }

    protected function deleteSalesInvoice(SalesInvoice $invoice): void
    {
        $id = $invoice->id;

        DB::transaction(function () use ($invoice, $id) {
            DB::table('sales_receipt_allocations')->where('invoice_id', $id)->delete();

            DB::table('document_relationships')
                ->where(function ($q) use ($id) {
                    $q->where('source_document_type', 'sales_invoice')->where('source_document_id', $id);
                })
                ->orWhere(function ($q) use ($id) {
                    $q->where('target_document_type', 'sales_invoice')->where('target_document_id', $id);
                })
                ->delete();

            DB::table('account_statement_lines')
                ->where('reference_type', 'sales_invoice')
                ->where('reference_id', $id)
                ->delete();

            DB::table('tax_transactions')
                ->where('reference_type', 'sales_invoice')
                ->where('reference_id', $id)
                ->delete();

            DB::table('currency_revaluation_lines')
                ->where('document_type', 'sales_invoice')
                ->where('document_id', $id)
                ->delete();

            $journalIds = DB::table('journals')
                ->where('source_type', 'sales_invoice')
                ->where('source_id', $id)
                ->pluck('id');

            foreach ($journalIds as $jid) {
                DB::table('journal_lines')->where('journal_id', $jid)->delete();
                DB::table('journals')->where('id', $jid)->delete();
            }

            DB::table('delivery_orders')
                ->where('closed_by_document_type', 'sales_invoice')
                ->where('closed_by_document_id', $id)
                ->update([
                    'closure_status' => 'open',
                    'closed_by_document_type' => null,
                    'closed_by_document_id' => null,
                    'closed_at' => null,
                    'closed_by_user_id' => null,
                ]);

            DB::table('sales_invoice_lines')->where('invoice_id', $id)->delete();
            DB::table('delivery_order_sales_invoice')->where('sales_invoice_id', $id)->delete();
            DB::table('sales_invoice_grpo_combinations')->where('sales_invoice_id', $id)->delete();

            $invoice->delete();
        });
    }
}
