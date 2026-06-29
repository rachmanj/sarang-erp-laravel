<?php

namespace App\Services\Documents\Handlers;

use App\Models\Accounting\SalesCreditMemo;
use App\Models\Accounting\SalesInvoice;
use App\Models\DeliveryOrder;
use App\Services\Accounting\DirectSalesPostingService;
use App\Services\Documents\DocumentType;
use App\Services\Documents\Support\DocumentDeletionSupport;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SalesInvoiceDeletionHandler extends AbstractDocumentDeletionHandler
{
    public function __construct(
        DocumentDeletionSupport $support,
        private DirectSalesPostingService $directSalesPostingService,
    ) {
        parent::__construct($support);
    }

    public function type(): string
    {
        return DocumentType::SALES_INVOICE;
    }

    public function children(Model $document): array
    {
        /** @var SalesInvoice $document */
        $children = [];

        $memoId = SalesCreditMemo::query()->where('sales_invoice_id', $document->id)->value('id');
        if ($memoId) {
            $children[] = $this->support->childRef(DocumentType::SALES_CREDIT_MEMO, (int) $memoId);
        }

        $receiptIds = DB::table('sales_receipt_allocations')
            ->where('invoice_id', $document->id)
            ->pluck('receipt_id')
            ->unique();

        foreach ($receiptIds as $receiptId) {
            $children[] = $this->support->childRef(DocumentType::SALES_RECEIPT, (int) $receiptId);
        }

        return $this->uniqueChildren($children);
    }

    public function reverseAndDelete(Model $document): void
    {
        /** @var SalesInvoice $document */
        if ($document->status === 'posted') {
            if ($document->is_direct_sale) {
                $this->directSalesPostingService->reverseInventory($document);
            }

            $this->support->reverseJournalsFor(['sales_invoice'], (int) $document->id);
            $this->support->deleteTaxTransactions('sales_invoice', (int) $document->id);
        }

        $invoiceId = (int) $document->id;
        $linkedDoIds = DB::table('delivery_order_sales_invoice')
            ->where('sales_invoice_id', $invoiceId)
            ->pluck('delivery_order_id')
            ->all();

        DB::table('sales_invoice_grpo_combinations')->where('sales_invoice_id', $invoiceId)->delete();
        DB::table('delivery_order_sales_invoice')->where('sales_invoice_id', $invoiceId)->delete();
        $document->lines()->delete();
        $this->support->clearDocumentRelationships(SalesInvoice::class, $invoiceId);
        $document->delete();

        foreach ($linkedDoIds as $doId) {
            $do = DeliveryOrder::query()->find($doId);
            if ($do) {
                $this->support->reopenClosureIfClosedBy($do, 'sales_invoice', $invoiceId);
            }
        }
    }
}
