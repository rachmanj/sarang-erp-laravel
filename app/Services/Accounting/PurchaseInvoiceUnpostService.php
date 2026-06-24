<?php

namespace App\Services\Accounting;

use App\Models\Accounting\PurchaseInvoice;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Services\Documents\Support\DocumentDeletionSupport;
use App\Services\InventoryService;
use App\Services\PurchaseInvoiceService;

class PurchaseInvoiceUnpostService
{
    public function __construct(
        private PostingService $postingService,
        private DocumentDeletionSupport $deletionSupport,
        private InventoryService $inventoryService,
        private PurchaseInvoiceService $purchaseInvoiceService,
    ) {}

    public function unpost(PurchaseInvoice $invoice, bool $deleteTax = true): void
    {
        if ($invoice->status !== 'posted') {
            return;
        }

        $this->deletionSupport->reverseJournalsFor(['purchase_invoice'], (int) $invoice->id);

        if ($invoice->is_direct_purchase && ! $invoice->is_opening_balance) {
            $this->reverseDirectPurchaseInventory($invoice);
        }

        if ($deleteTax) {
            $this->deletionSupport->deleteTaxTransactions('purchase_invoice', (int) $invoice->id);
        }

        $invoice->update([
            'status' => 'draft',
            'posted_at' => null,
        ]);
    }

    /**
     * @return array<int, string>
     */
    public function repairBrokenDirectPurchaseReversal(PurchaseInvoice $invoice): array
    {
        $messages = [];

        $brokenAdjustments = InventoryTransaction::query()
            ->where('reference_type', 'purchase_invoice')
            ->where('reference_id', $invoice->id)
            ->where('transaction_type', 'adjustment')
            ->where('quantity', '<', 0)
            ->get();

        foreach ($brokenAdjustments as $adjustment) {
            $adjustment->delete();
            $messages[] = "Removed broken reversal adjustment #{$adjustment->id}.";
        }

        $invoice->loadMissing(['lines.inventoryItem', 'lines.warehouse']);

        foreach ($invoice->lines as $line) {
            if (! $line->inventory_item_id) {
                continue;
            }

            $existingPurchase = InventoryTransaction::query()
                ->where('purchase_invoice_line_id', $line->id)
                ->where('transaction_type', 'purchase')
                ->first();

            if ($existingPurchase) {
                continue;
            }

            $existingByReference = InventoryTransaction::query()
                ->where('reference_type', 'purchase_invoice')
                ->where('reference_id', $invoice->id)
                ->where('item_id', $line->inventory_item_id)
                ->where('transaction_type', 'purchase')
                ->first();

            if ($existingByReference) {
                if (! $existingByReference->purchase_invoice_line_id) {
                    $existingByReference->update(['purchase_invoice_line_id' => $line->id]);
                    $messages[] = "Linked restored purchase transaction #{$existingByReference->id} to PI line #{$line->id}.";
                }

                continue;
            }

            $this->purchaseInvoiceService->createInventoryTransaction($line, $invoice);
            $messages[] = "Recreated purchase inventory for {$line->inventoryItem?->code} (qty {$line->qty}).";
        }

        $itemIds = $invoice->lines
            ->pluck('inventory_item_id')
            ->filter()
            ->unique()
            ->all();

        foreach ($itemIds as $itemId) {
            $item = InventoryItem::find($itemId);
            if ($item) {
                $this->inventoryService->updateItemValuation($item);
            }
        }

        return $messages;
    }

    private function reverseDirectPurchaseInventory(PurchaseInvoice $invoice): void
    {
        $transactions = InventoryTransaction::query()
            ->where('reference_type', 'purchase_invoice')
            ->where('reference_id', $invoice->id)
            ->where('transaction_type', 'purchase')
            ->orderBy('id')
            ->get();

        foreach ($transactions as $transaction) {
            $this->inventoryService->removePurchaseInventoryTransaction($transaction);
        }
    }
}
