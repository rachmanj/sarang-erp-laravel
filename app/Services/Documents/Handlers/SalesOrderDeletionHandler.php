<?php

namespace App\Services\Documents\Handlers;

use App\Models\Accounting\SalesInvoice;
use App\Models\DeliveryOrder;
use App\Models\SalesOrder;
use App\Services\Documents\DocumentType;
use Illuminate\Database\Eloquent\Model;

class SalesOrderDeletionHandler extends AbstractDocumentDeletionHandler
{
    public function type(): string
    {
        return DocumentType::SALES_ORDER;
    }

    public function children(Model $document): array
    {
        /** @var SalesOrder $document */
        $children = [];

        foreach (DeliveryOrder::query()->where('sales_order_id', $document->id)->pluck('id') as $doId) {
            $children[] = $this->support->childRef(DocumentType::DELIVERY_ORDER, (int) $doId);
        }

        foreach (SalesInvoice::query()->where('sales_order_id', $document->id)->pluck('id') as $invoiceId) {
            $children[] = $this->support->childRef(DocumentType::SALES_INVOICE, (int) $invoiceId);
        }

        return $this->uniqueChildren($children);
    }

    public function reverseAndDelete(Model $document): void
    {
        /** @var SalesOrder $document */
        $this->support->reopenClosureIfClosedBy($document, 'delivery_order', (int) ($document->closed_by_document_id ?? 0));

        $document->approvals()->delete();
        $document->lines()->delete();
        $this->support->clearDocumentRelationships(SalesOrder::class, (int) $document->id);
        $document->delete();
    }
}
