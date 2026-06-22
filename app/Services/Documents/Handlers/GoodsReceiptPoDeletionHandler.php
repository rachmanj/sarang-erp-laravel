<?php

namespace App\Services\Documents\Handlers;

use App\Models\Accounting\PurchaseInvoice;
use App\Models\GoodsReceiptPO;
use App\Models\PurchaseOrder;
use App\Services\Documents\DocumentType;
use App\Services\Documents\Support\DocumentDeletionSupport;
use App\Services\GRPOJournalService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class GoodsReceiptPoDeletionHandler extends AbstractDocumentDeletionHandler
{
    public function __construct(
        DocumentDeletionSupport $support,
        private GRPOJournalService $grpoJournalService,
    ) {
        parent::__construct($support);
    }

    public function type(): string
    {
        return DocumentType::GOODS_RECEIPT_PO;
    }

    public function children(Model $document): array
    {
        /** @var GoodsReceiptPO $document */
        $children = [];

        $invoiceIds = DB::table('goods_receipt_po_purchase_invoice')
            ->where('grpo_id', $document->id)
            ->pluck('purchase_invoice_id');

        foreach ($invoiceIds as $invoiceId) {
            $children[] = $this->support->childRef(DocumentType::PURCHASE_INVOICE, (int) $invoiceId);
        }

        $legacyInvoiceId = PurchaseInvoice::query()->where('goods_receipt_id', $document->id)->value('id');
        if ($legacyInvoiceId) {
            $children[] = $this->support->childRef(DocumentType::PURCHASE_INVOICE, (int) $legacyInvoiceId);
        }

        return $this->uniqueChildren($children);
    }

    public function reverseAndDelete(Model $document): void
    {
        /** @var GoodsReceiptPO $document */
        if ($document->isJournalized()) {
            $this->grpoJournalService->reverseJournalEntries($document);
            $document->refresh();
        } else {
            $this->support->reverseJournalsFor(['goods_receipt_po'], (int) $document->id);
        }

        $this->support->reverseInventoryByReference(
            'goods_receipt_po',
            (int) $document->id,
            'GRPO '.$document->grn_no.' deleted — inventory reversed'
        );

        if ($document->purchase_order_id) {
            $po = PurchaseOrder::query()->find($document->purchase_order_id);
            if ($po) {
                $this->support->reopenClosureIfClosedBy($po, 'goods_receipt', (int) $document->id);
            }
        }

        DB::table('sales_invoice_grpo_combinations')->where('goods_receipt_id', $document->id)->delete();
        DB::table('goods_receipt_po_purchase_invoice')->where('grpo_id', $document->id)->delete();
        $document->lines()->delete();
        $this->support->clearDocumentRelationships(GoodsReceiptPO::class, (int) $document->id);
        $document->delete();
    }
}
