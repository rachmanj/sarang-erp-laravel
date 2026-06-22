<?php

namespace App\Services\Documents\Handlers;

use App\Models\DeliveryOrder;
use App\Services\DeliveryService;
use App\Services\Documents\DocumentType;
use App\Services\Documents\Support\DocumentDeletionSupport;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DeliveryOrderDeletionHandler extends AbstractDocumentDeletionHandler
{
    public function __construct(
        DocumentDeletionSupport $support,
        private DeliveryService $deliveryService,
    ) {
        parent::__construct($support);
    }

    public function type(): string
    {
        return DocumentType::DELIVERY_ORDER;
    }

    public function children(Model $document): array
    {
        /** @var DeliveryOrder $document */
        $children = [];

        $invoiceIds = DB::table('delivery_order_sales_invoice')
            ->where('delivery_order_id', $document->id)
            ->pluck('sales_invoice_id');

        foreach ($invoiceIds as $invoiceId) {
            $children[] = $this->support->childRef(DocumentType::SALES_INVOICE, (int) $invoiceId);
        }

        return $this->uniqueChildren($children);
    }

    public function reverseAndDelete(Model $document): void
    {
        /** @var DeliveryOrder $document */
        if (in_array($document->status, ['partial_delivered', 'delivered', 'completed'], true)) {
            if ($document->canBeReversed()) {
                $this->deliveryService->reverseDeliveryOrder(
                    (int) $document->id,
                    'Deleted via cascade delete',
                    Auth::id()
                );
                $document->refresh();
            }
        } elseif ($document->canBeCancelled()) {
            $this->deliveryService->cancelDeliveryOrder((int) $document->id, 'Deleted via cascade delete');
            $document->refresh();
        }

        if ($document->salesOrder) {
            $this->support->reopenClosureIfClosedBy(
                $document->salesOrder,
                'delivery_order',
                (int) $document->id
            );
        }

        DB::table('delivery_order_sales_invoice')->where('delivery_order_id', $document->id)->delete();
        $document->lines()->delete();
        $this->support->reverseJournalsFor([DeliveryOrder::class], (int) $document->id);
        $this->support->clearDocumentRelationships(DeliveryOrder::class, (int) $document->id);
        $document->delete();
    }
}
