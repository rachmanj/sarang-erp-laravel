<?php

namespace App\Services\Accounting;

use App\Models\Accounting\PurchaseInvoice;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Services\Documents\Support\DocumentDeletionSupport;
use App\Services\InventoryService;
use Illuminate\Support\Facades\Auth;

class PurchaseInvoiceUnpostService
{
    public function __construct(
        private PostingService $postingService,
        private DocumentDeletionSupport $deletionSupport,
        private InventoryService $inventoryService,
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

    private function reverseDirectPurchaseInventory(PurchaseInvoice $invoice): void
    {
        $transactions = InventoryTransaction::query()
            ->where('reference_type', 'purchase_invoice')
            ->where('reference_id', $invoice->id)
            ->get();

        $itemsToUpdate = [];

        foreach ($transactions as $transaction) {
            if (! in_array($transaction->item_id, $itemsToUpdate, true)) {
                $itemsToUpdate[] = (int) $transaction->item_id;
            }

            InventoryTransaction::create([
                'item_id' => $transaction->item_id,
                'transaction_type' => 'adjustment',
                'quantity' => -((float) $transaction->quantity),
                'unit_cost' => $transaction->unit_cost,
                'total_cost' => -((float) $transaction->total_cost),
                'reference_type' => 'purchase_invoice',
                'reference_id' => $invoice->id,
                'transaction_date' => now()->toDateString(),
                'notes' => 'Reversal of purchase invoice #'.$invoice->invoice_no,
                'warehouse_id' => $transaction->warehouse_id,
                'created_by' => Auth::id(),
            ]);

            $transaction->delete();
        }

        foreach ($itemsToUpdate as $itemId) {
            $item = InventoryItem::find($itemId);
            if ($item) {
                $this->inventoryService->updateItemValuation($item);
            }
        }
    }
}
