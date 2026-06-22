<?php

namespace App\Services\Documents\Handlers;

use App\Models\Accounting\PurchaseInvoice;
use App\Models\GoodsReceiptPO;
use App\Services\Accounting\PurchaseInvoiceUnpostService;
use App\Services\Documents\DocumentType;
use App\Services\Documents\Support\DocumentDeletionSupport;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PurchaseInvoiceDeletionHandler extends AbstractDocumentDeletionHandler
{
    public function __construct(
        DocumentDeletionSupport $support,
        private PurchaseInvoiceUnpostService $purchaseInvoiceUnpostService,
    ) {
        parent::__construct($support);
    }

    public function type(): string
    {
        return DocumentType::PURCHASE_INVOICE;
    }

    public function children(Model $document): array
    {
        /** @var PurchaseInvoice $document */
        $children = [];

        $paymentIds = DB::table('purchase_payment_allocations')
            ->where('invoice_id', $document->id)
            ->pluck('payment_id')
            ->unique();

        foreach ($paymentIds as $paymentId) {
            $children[] = $this->support->childRef(DocumentType::PURCHASE_PAYMENT, (int) $paymentId);
        }

        return $this->uniqueChildren($children);
    }

    public function reverseAndDelete(Model $document): void
    {
        /** @var PurchaseInvoice $document */
        $invoiceId = (int) $document->id;

        if ($document->status === 'posted') {
            $this->purchaseInvoiceUnpostService->unpost($document);
            $document->refresh();
        }

        $grpoIds = DB::table('goods_receipt_po_purchase_invoice')
            ->where('purchase_invoice_id', $invoiceId)
            ->pluck('grpo_id')
            ->all();

        if ($document->goods_receipt_id) {
            $grpoIds[] = (int) $document->goods_receipt_id;
        }

        DB::table('goods_receipt_po_purchase_invoice')->where('purchase_invoice_id', $invoiceId)->delete();
        $document->lines()->delete();
        $this->support->clearDocumentRelationships(PurchaseInvoice::class, $invoiceId);
        $document->delete();

        foreach (array_unique($grpoIds) as $grpoId) {
            $grpo = GoodsReceiptPO::query()->find($grpoId);
            if ($grpo) {
                $this->support->reopenClosureIfClosedBy($grpo, 'purchase_invoice', $invoiceId);
            }
        }
    }
}
