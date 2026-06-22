<?php

namespace App\Services\Documents;

use App\Services\Documents\Contracts\DocumentDeletionHandler;
use App\Services\Documents\Exceptions\DocumentDeletionBlockedException;
use App\Services\Documents\Handlers\DeliveryOrderDeletionHandler;
use App\Services\Documents\Handlers\GoodsReceiptPoDeletionHandler;
use App\Services\Documents\Handlers\PurchaseInvoiceDeletionHandler;
use App\Services\Documents\Handlers\PurchaseOrderDeletionHandler;
use App\Services\Documents\Handlers\PurchasePaymentDeletionHandler;
use App\Services\Documents\Handlers\SalesCreditMemoDeletionHandler;
use App\Services\Documents\Handlers\SalesInvoiceDeletionHandler;
use App\Services\Documents\Handlers\SalesOrderDeletionHandler;
use App\Services\Documents\Handlers\SalesQuotationDeletionHandler;
use App\Services\Documents\Handlers\SalesReceiptDeletionHandler;
use App\Services\Documents\Support\DocumentDeletionSupport;
use Illuminate\Support\Facades\DB;

class DocumentDeletionService
{
    /** @var array<string, DocumentDeletionHandler> */
    private array $handlers;

    private DocumentDeletionGraph $graph;

    public function __construct(
        private DocumentDeletionSupport $support,
        SalesQuotationDeletionHandler $salesQuotationHandler,
        SalesOrderDeletionHandler $salesOrderHandler,
        DeliveryOrderDeletionHandler $deliveryOrderHandler,
        SalesInvoiceDeletionHandler $salesInvoiceHandler,
        SalesReceiptDeletionHandler $salesReceiptHandler,
        SalesCreditMemoDeletionHandler $salesCreditMemoHandler,
        PurchaseOrderDeletionHandler $purchaseOrderHandler,
        GoodsReceiptPoDeletionHandler $goodsReceiptPoHandler,
        PurchaseInvoiceDeletionHandler $purchaseInvoiceHandler,
        PurchasePaymentDeletionHandler $purchasePaymentHandler,
    ) {
        $this->handlers = [
            DocumentType::SALES_QUOTATION => $salesQuotationHandler,
            DocumentType::SALES_ORDER => $salesOrderHandler,
            DocumentType::DELIVERY_ORDER => $deliveryOrderHandler,
            DocumentType::SALES_INVOICE => $salesInvoiceHandler,
            DocumentType::SALES_RECEIPT => $salesReceiptHandler,
            DocumentType::SALES_CREDIT_MEMO => $salesCreditMemoHandler,
            DocumentType::PURCHASE_ORDER => $purchaseOrderHandler,
            DocumentType::GOODS_RECEIPT_PO => $goodsReceiptPoHandler,
            DocumentType::PURCHASE_INVOICE => $purchaseInvoiceHandler,
            DocumentType::PURCHASE_PAYMENT => $purchasePaymentHandler,
        ];

        $this->graph = new DocumentDeletionGraph($support, $this->handlers);
    }

    /**
     * @return list<array{type: string, id: int, number: string, label: string, status: string|null, date: string|null, depth: int}>
     */
    public function previewCascade(string $type, int $id): array
    {
        DocumentDescriptor::assertValid($type);

        return $this->graph->preview($type, $id);
    }

    /**
     * @return array{
     *     mode: string,
     *     root: array{type: string, id: int, number: string, label: string, status: string|null, date: string|null},
     *     blocked: bool,
     *     targets: list<array{type: string, id: int, number: string, label: string, status: string|null, date: string|null, depth: int}>,
     *     documents: list<array{type: string, id: int, number: string, label: string, status: string|null, date: string|null, depth: int}>
     * }
     */
    public function previewSingle(string $type, int $id): array
    {
        DocumentDescriptor::assertValid($type);

        $modelClass = DocumentDescriptor::modelClass($type);
        $model = $modelClass::query()->findOrFail($id);

        $dateColumn = DocumentDescriptor::dateColumn($type);
        $dateValue = $model->{$dateColumn} ?? null;

        $root = [
            'type' => $type,
            'id' => $id,
            'number' => $this->support->documentNumber($type, $model),
            'label' => DocumentDescriptor::label($type),
            'status' => $model->status ?? null,
            'date' => $dateValue ? (string) $dateValue : null,
        ];

        $targets = $this->graph->previewDescendants($type, $id);
        $documentRow = array_merge($root, ['depth' => 0]);

        return [
            'mode' => 'single',
            'root' => $root,
            'blocked' => $targets !== [],
            'targets' => $targets,
            'documents' => [$documentRow],
        ];
    }

    public function deleteSingle(string $type, int $id): void
    {
        DocumentDescriptor::assertValid($type);

        $targets = $this->graph->previewDescendants($type, $id);

        if ($targets !== []) {
            $listed = collect($targets)
                ->map(fn (array $target) => ($target['label'] ?? DocumentDescriptor::label($target['type'])).' '.$target['number'])
                ->implode(', ');

            throw new DocumentDeletionBlockedException(
                'Cannot delete this document only because it has downstream documents: '.$listed
                .'. Delete those first or use "Delete with related documents".'
            );
        }

        $node = ['type' => $type, 'id' => $id, 'depth' => 0];
        $this->support->assertDeletable([$node]);
        $this->support->assertNoSharedSettlementDocuments([$node]);

        DB::transaction(function () use ($type, $id) {
            $handler = $this->handlers[$type];
            $modelClass = DocumentDescriptor::modelClass($type);
            $model = $modelClass::query()->findOrFail($id);
            $handler->reverseAndDelete($model);
        });
    }

    public function delete(string $type, int $id): void
    {
        DocumentDescriptor::assertValid($type);

        $cascade = $this->graph->collectCascade($type, $id);
        $this->support->assertDeletable($cascade);

        DB::transaction(function () use ($cascade) {
            foreach ($cascade as $node) {
                $handler = $this->handlers[$node['type']];
                $modelClass = DocumentDescriptor::modelClass($node['type']);
                $model = $modelClass::query()->findOrFail($node['id']);
                $handler->reverseAndDelete($model);
            }
        });
    }

    public function redirectRoute(string $type): string
    {
        return DocumentDescriptor::routePrefix($type).'.index';
    }
}
