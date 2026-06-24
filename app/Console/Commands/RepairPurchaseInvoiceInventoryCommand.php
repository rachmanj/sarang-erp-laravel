<?php

namespace App\Console\Commands;

use App\Models\Accounting\PurchaseInvoice;
use App\Services\Accounting\PurchaseInvoiceUnpostService;
use Illuminate\Console\Command;

class RepairPurchaseInvoiceInventoryCommand extends Command
{
    protected $signature = 'inventory:repair-purchase-invoice-inventory
                            {invoice : Purchase invoice ID or invoice number}
                            {--dry-run : Preview repair actions without saving}';

    protected $description = 'Repair broken direct-purchase inventory reversals after a failed PI delete/unpost';

    public function handle(PurchaseInvoiceUnpostService $purchaseInvoiceUnpostService): int
    {
        $invoice = $this->resolveInvoice((string) $this->argument('invoice'));

        if (! $invoice) {
            $this->error('Purchase invoice not found.');

            return self::FAILURE;
        }

        if (! $invoice->is_direct_purchase || $invoice->is_opening_balance) {
            $this->error('This command only applies to direct purchase invoices that are not opening balance.');

            return self::FAILURE;
        }

        $this->info("Purchase Invoice: {$invoice->invoice_no} (ID {$invoice->id})");

        $brokenAdjustments = \App\Models\InventoryTransaction::query()
            ->where('reference_type', 'purchase_invoice')
            ->where('reference_id', $invoice->id)
            ->where('transaction_type', 'adjustment')
            ->where('quantity', '<', 0)
            ->get();

        if ($brokenAdjustments->isEmpty()) {
            $this->info('No broken reversal adjustments found for this invoice.');

            return self::SUCCESS;
        }

        $this->warn('Found '.$brokenAdjustments->count().' broken reversal adjustment(s):');
        $this->table(
            ['Tx ID', 'Item ID', 'Qty', 'Notes'],
            $brokenAdjustments->map(fn ($tx) => [
                $tx->id,
                $tx->item_id,
                $tx->quantity,
                $tx->notes,
            ])->all()
        );

        if ($this->option('dry-run')) {
            $this->comment('[DRY RUN] Would remove the adjustments above and recreate missing purchase inventory rows from PI lines.');

            return self::SUCCESS;
        }

        if (! $this->confirm('Apply inventory repair for this purchase invoice?', true)) {
            $this->comment('Cancelled.');

            return self::SUCCESS;
        }

        $messages = $purchaseInvoiceUnpostService->repairBrokenDirectPurchaseReversal($invoice);

        foreach ($messages as $message) {
            $this->line('- '.$message);
        }

        $this->info('Inventory repair completed.');

        return self::SUCCESS;
    }

    private function resolveInvoice(string $identifier): ?PurchaseInvoice
    {
        if (is_numeric($identifier)) {
            return PurchaseInvoice::query()->find((int) $identifier);
        }

        return PurchaseInvoice::query()->where('invoice_no', $identifier)->first();
    }
}
