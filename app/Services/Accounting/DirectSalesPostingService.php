<?php

namespace App\Services\Accounting;

use App\Models\Accounting\SalesInvoice;
use App\Models\Accounting\SalesReceipt;
use App\Models\Accounting\SalesReceiptLine;
use App\Models\InventoryTransaction;
use App\Services\Accounting\JournalBuilders\DirectSalesInvoiceJournalBuilder;
use App\Services\Accounting\JournalBuilders\SalesReceiptJournalBuilder;
use App\Services\DocumentClosureService;
use App\Services\DocumentNumberingService;
use App\Services\DocumentRelationshipService;
use App\Services\InventoryService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DirectSalesPostingService
{
    public function __construct(
        private PostingService $posting,
        private DirectSalesInvoiceJournalBuilder $directSalesInvoiceJournalBuilder,
        private SalesReceiptJournalBuilder $salesReceiptJournalBuilder,
        private InventoryService $inventoryService,
        private DocumentNumberingService $documentNumberingService,
        private DocumentClosureService $documentClosureService,
        private DocumentRelationshipService $documentRelationshipService,
    ) {}

    public function post(SalesInvoice $invoice): void
    {
        if (! $invoice->is_direct_sale) {
            throw new \InvalidArgumentException('Invoice is not a direct sale.');
        }

        $invoice->loadMissing(['lines.inventoryItem']);

        foreach ($invoice->lines as $line) {
            if ($line->inventory_item_id && $line->inventoryItem && $line->inventoryItem->item_type !== 'service') {
                $qty = (int) round((float) $line->qty);
                if ($qty <= 0) {
                    continue;
                }

                $unitCost = $this->inventoryService->calculateUnitCost($line->inventoryItem);
                $this->inventoryService->processSaleTransaction(
                    (int) $line->inventory_item_id,
                    $qty,
                    $unitCost,
                    'sales_invoice_line',
                    (int) $line->id,
                    'Direct sale SI '.($invoice->invoice_no ?? '#'.$invoice->id),
                );
            }
        }

        $draft = $this->directSalesInvoiceJournalBuilder->build($invoice);
        $this->posting->postJournal([
            'date' => $draft->date ?? $invoice->date->toDateString(),
            'description' => $draft->description,
            'source_type' => 'sales_invoice',
            'source_id' => $invoice->id,
            'lines' => $draft->lines,
        ]);

        if ($invoice->payment_method === 'cash') {
            $this->postAutoCashReceipt($invoice);
        }
    }

    public function reverseInventory(SalesInvoice $invoice): void
    {
        $invoice->loadMissing('lines');

        $lineIds = $invoice->lines->pluck('id')->all();
        if ($lineIds === []) {
            return;
        }

        $transactions = InventoryTransaction::query()
            ->where('reference_type', 'sales_invoice_line')
            ->whereIn('reference_id', $lineIds)
            ->where('transaction_type', 'sale')
            ->orderBy('id')
            ->get();

        foreach ($transactions as $transaction) {
            $qty = (int) round(abs((float) $transaction->quantity));
            if ($qty <= 0) {
                $transaction->delete();

                continue;
            }

            $this->inventoryService->processAdjustmentTransaction(
                (int) $transaction->item_id,
                $qty,
                (float) $transaction->unit_cost,
                'Restore stock - reverse direct sale SI '.($invoice->invoice_no ?? '#'.$invoice->id),
            );
            $transaction->delete();
        }
    }

    private function postAutoCashReceipt(SalesInvoice $invoice): void
    {
        $cashAccountId = (int) ($invoice->cash_account_id ?? 0);
        if ($cashAccountId <= 0) {
            $cashAccountId = (int) DB::table('accounts')->where('code', '1.1.1.01')->value('id');
        }

        if ($cashAccountId <= 0) {
            throw new \RuntimeException('Cash account is required for direct cash sale.');
        }

        $amountDue = SalesInvoicePostingMath::invoiceFooterTotals($invoice)['amount_due'];
        if ($amountDue <= 0) {
            return;
        }

        $baseCurrencyId = (int) (DB::table('currencies')->where('code', 'IDR')->value('id')
            ?: DB::table('currencies')->where('is_base_currency', 1)->value('id'));

        $receipt = SalesReceipt::create([
            'receipt_no' => null,
            'date' => $invoice->date,
            'business_partner_id' => $invoice->business_partner_id,
            'company_entity_id' => $invoice->company_entity_id,
            'created_by' => Auth::id(),
            'currency_id' => $baseCurrencyId,
            'description' => 'Auto receipt for Direct Sale '.$invoice->invoice_no,
            'status' => 'draft',
            'total_amount' => $amountDue,
        ]);

        $receiptNo = $this->documentNumberingService->generateNumber('sales_receipt', $invoice->date->toDateString(), [
            'company_entity_id' => $invoice->company_entity_id,
        ]);
        $receipt->update(['receipt_no' => $receiptNo]);

        SalesReceiptLine::create([
            'receipt_id' => $receipt->id,
            'account_id' => $cashAccountId,
            'description' => 'Direct sale payment',
            'amount' => $amountDue,
        ]);

        DB::table('sales_receipt_allocations')->insert([
            'receipt_id' => $receipt->id,
            'invoice_id' => $invoice->id,
            'amount' => $amountDue,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $receiptDraft = $this->salesReceiptJournalBuilder->build($receipt->fresh('lines'));
        $this->posting->postJournal([
            'date' => $receiptDraft->date ?? $receipt->date->toDateString(),
            'description' => $receiptDraft->description,
            'source_type' => 'sales_receipt',
            'source_id' => $receipt->id,
            'lines' => $receiptDraft->lines,
        ]);

        $receipt->update(['status' => 'posted', 'posted_at' => now()]);

        $this->documentRelationshipService->createBaseRelationship(
            $invoice,
            $receipt,
            'Auto Sales Receipt for Direct Cash Sale'
        );
        $this->documentRelationshipService->createTargetRelationship(
            $invoice,
            $receipt,
            'Auto Sales Receipt for Direct Cash Sale'
        );

        try {
            $this->documentClosureService->closeSalesInvoiceByReceipt($receipt->id, Auth::id());
        } catch (\Exception $e) {
            // Non-fatal: receipt still posted and allocated
        }
    }
}
