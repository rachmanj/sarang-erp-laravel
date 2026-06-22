<?php

namespace App\Services\Documents\Handlers;

use App\Models\Accounting\PurchaseInvoice;
use App\Models\GoodsReceiptPO;
use App\Models\PurchaseOrder;
use App\Services\Documents\DocumentType;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderDeletionHandler extends AbstractDocumentDeletionHandler
{
    public function type(): string
    {
        return DocumentType::PURCHASE_ORDER;
    }

    public function children(Model $document): array
    {
        /** @var PurchaseOrder $document */
        $children = [];

        $grpoIds = GoodsReceiptPO::query()
            ->where(function ($query) use ($document) {
                $query->where('purchase_order_id', $document->id)
                    ->orWhere('source_po_id', $document->id);
            })
            ->pluck('id');

        foreach ($grpoIds as $grpoId) {
            $children[] = $this->support->childRef(DocumentType::GOODS_RECEIPT_PO, (int) $grpoId);
        }

        foreach (PurchaseInvoice::query()->where('purchase_order_id', $document->id)->pluck('id') as $invoiceId) {
            $children[] = $this->support->childRef(DocumentType::PURCHASE_INVOICE, (int) $invoiceId);
        }

        return $this->uniqueChildren($children);
    }

    public function reverseAndDelete(Model $document): void
    {
        /** @var PurchaseOrder $document */
        $document->approvals()->delete();
        $document->lines()->delete();
        $this->support->clearDocumentRelationships(PurchaseOrder::class, (int) $document->id);
        $document->delete();
    }
}
