<?php

namespace App\Services;

use App\Models\Accounting\PurchaseInvoice;
use App\Models\Accounting\PurchasePayment;
use App\Models\Accounting\SalesInvoice;
use App\Models\Accounting\SalesReceipt;
use App\Models\DeliveryOrder;
use App\Models\GoodsReceiptPO;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DocumentCreationLogsService
{
    /**
     * @return array<string, string>
     */
    public function documentTypeLabels(): array
    {
        return [
            'purchase_orders' => 'Purchase Orders',
            'goods_receipts' => 'Goods Receipts',
            'purchase_invoices' => 'Purchase Invoices',
            'purchase_payments' => 'Purchase Payments',
            'sales_orders' => 'Sales Orders',
            'delivery_orders' => 'Delivery Orders',
            'sales_invoices' => 'Sales Invoices',
            'sales_receipts' => 'Sales Receipts',
        ];
    }

    /**
     * @param  array{date_from?: string, date_to?: string, supplier_id?: int|string|null, customer_id?: int|string|null, document_type?: string|null}  $filters
     */
    public function getMergedLogs(array $filters = []): Collection
    {
        $typeFilter = $filters['document_type'] ?? null;
        $labels = $this->documentTypeLabels();

        if ($typeFilter !== null && $typeFilter !== '' && ! array_key_exists($typeFilter, $labels)) {
            return collect();
        }

        $chunks = [];

        if ($typeFilter === null || $typeFilter === '' || $typeFilter === 'purchase_orders') {
            $chunks[] = $this->collectPurchaseOrders($filters, $labels['purchase_orders']);
        }
        if ($typeFilter === null || $typeFilter === '' || $typeFilter === 'goods_receipts') {
            $chunks[] = $this->collectGoodsReceipts($filters, $labels['goods_receipts']);
        }
        if ($typeFilter === null || $typeFilter === '' || $typeFilter === 'purchase_invoices') {
            $chunks[] = $this->collectPurchaseInvoices($filters, $labels['purchase_invoices']);
        }
        if ($typeFilter === null || $typeFilter === '' || $typeFilter === 'purchase_payments') {
            $chunks[] = $this->collectPurchasePayments($filters, $labels['purchase_payments']);
        }
        if ($typeFilter === null || $typeFilter === '' || $typeFilter === 'sales_orders') {
            $chunks[] = $this->collectSalesOrders($filters, $labels['sales_orders']);
        }
        if ($typeFilter === null || $typeFilter === '' || $typeFilter === 'delivery_orders') {
            $chunks[] = $this->collectDeliveryOrders($filters, $labels['delivery_orders']);
        }
        if ($typeFilter === null || $typeFilter === '' || $typeFilter === 'sales_invoices') {
            $chunks[] = $this->collectSalesInvoices($filters, $labels['sales_invoices']);
        }
        if ($typeFilter === null || $typeFilter === '' || $typeFilter === 'sales_receipts') {
            $chunks[] = $this->collectSalesReceipts($filters, $labels['sales_receipts']);
        }

        return collect($chunks)
            ->flatten(1)
            ->sortByDesc(fn (array $row) => $row['created_at']->timestamp)
            ->values();
    }

    /**
     * @param  array{date_from?: string, date_to?: string, supplier_id?: int|string|null, customer_id?: int|string|null}  $filters
     * @return Collection<int, array<string, mixed>>
     */
    private function collectPurchaseOrders(array $filters, string $typeLabel): Collection
    {
        $query = PurchaseOrder::query()
            ->with(['businessPartner', 'createdBy']);

        $this->applyCreatedAtRange($query, $filters);
        if (! empty($filters['supplier_id'])) {
            $query->where('business_partner_id', $filters['supplier_id']);
        }

        return $query->orderBy('created_at', 'desc')->get()->map(function (PurchaseOrder $po) use ($typeLabel) {
            return [
                'document_type' => 'purchase_orders',
                'document_type_label' => $typeLabel,
                'id' => $po->id,
                'document_number' => $po->order_no,
                'created_at' => $po->created_at ?? Carbon::now(),
                'closure_status' => $po->closure_status ?? null,
                'party_name' => $po->businessPartner?->name,
                'creator_name' => $po->createdBy?->name,
                'url' => route('purchase-orders.show', $po->id),
            ];
        });
    }

    /**
     * @param  array{date_from?: string, date_to?: string, supplier_id?: int|string|null, customer_id?: int|string|null}  $filters
     * @return Collection<int, array<string, mixed>>
     */
    private function collectGoodsReceipts(array $filters, string $typeLabel): Collection
    {
        $query = GoodsReceiptPO::query()
            ->with(['businessPartner', 'createdBy']);

        $this->applyCreatedAtRange($query, $filters);
        if (! empty($filters['supplier_id'])) {
            $query->where('business_partner_id', $filters['supplier_id']);
        }

        return $query->orderBy('created_at', 'desc')->get()->map(function (GoodsReceiptPO $gr) use ($typeLabel) {
            return [
                'document_type' => 'goods_receipts',
                'document_type_label' => $typeLabel,
                'id' => $gr->id,
                'document_number' => $gr->grn_no,
                'created_at' => $gr->created_at ?? Carbon::now(),
                'closure_status' => $gr->closure_status ?? null,
                'party_name' => $gr->businessPartner?->name,
                'creator_name' => $gr->createdBy?->name,
                'url' => route('goods-receipt-pos.show', $gr->id),
            ];
        });
    }

    /**
     * @param  array{date_from?: string, date_to?: string, supplier_id?: int|string|null, customer_id?: int|string|null}  $filters
     * @return Collection<int, array<string, mixed>>
     */
    private function collectPurchaseInvoices(array $filters, string $typeLabel): Collection
    {
        $query = PurchaseInvoice::query()
            ->with(['supplier', 'createdBy']);

        $this->applyCreatedAtRange($query, $filters);
        if (! empty($filters['supplier_id'])) {
            $query->where('business_partner_id', $filters['supplier_id']);
        }

        return $query->orderBy('created_at', 'desc')->get()->map(function (PurchaseInvoice $pi) use ($typeLabel) {
            return [
                'document_type' => 'purchase_invoices',
                'document_type_label' => $typeLabel,
                'id' => $pi->id,
                'document_number' => $pi->invoice_no,
                'created_at' => $pi->created_at ?? Carbon::now(),
                'closure_status' => $pi->closure_status ?? null,
                'party_name' => $pi->supplier?->name,
                'creator_name' => $pi->createdBy?->name,
                'url' => route('purchase-invoices.show', $pi->id),
            ];
        });
    }

    /**
     * @param  array{date_from?: string, date_to?: string, supplier_id?: int|string|null, customer_id?: int|string|null}  $filters
     * @return Collection<int, array<string, mixed>>
     */
    private function collectPurchasePayments(array $filters, string $typeLabel): Collection
    {
        $query = PurchasePayment::query()
            ->with(['businessPartner', 'createdBy']);

        $this->applyCreatedAtRange($query, $filters);
        if (! empty($filters['supplier_id'])) {
            $query->where('business_partner_id', $filters['supplier_id']);
        }

        return $query->orderBy('created_at', 'desc')->get()->map(function (PurchasePayment $pp) use ($typeLabel) {
            return [
                'document_type' => 'purchase_payments',
                'document_type_label' => $typeLabel,
                'id' => $pp->id,
                'document_number' => $pp->payment_no,
                'created_at' => $pp->created_at ?? Carbon::now(),
                'closure_status' => $pp->closure_status ?? null,
                'party_name' => $pp->businessPartner?->name,
                'creator_name' => $pp->createdBy?->name,
                'url' => route('purchase-payments.show', $pp->id),
            ];
        });
    }

    /**
     * @param  array{date_from?: string, date_to?: string, supplier_id?: int|string|null, customer_id?: int|string|null}  $filters
     * @return Collection<int, array<string, mixed>>
     */
    private function collectSalesOrders(array $filters, string $typeLabel): Collection
    {
        $query = SalesOrder::query()
            ->with(['customer', 'createdBy']);

        $this->applyCreatedAtRange($query, $filters);
        if (! empty($filters['customer_id'])) {
            $query->where('business_partner_id', $filters['customer_id']);
        }

        return $query->orderBy('created_at', 'desc')->get()->map(function (SalesOrder $so) use ($typeLabel) {
            return [
                'document_type' => 'sales_orders',
                'document_type_label' => $typeLabel,
                'id' => $so->id,
                'document_number' => $so->order_no,
                'created_at' => $so->created_at ?? Carbon::now(),
                'closure_status' => $so->closure_status ?? null,
                'party_name' => $so->customer?->name,
                'creator_name' => $so->createdBy?->name,
                'url' => route('sales-orders.show', $so->id),
            ];
        });
    }

    /**
     * @param  array{date_from?: string, date_to?: string, supplier_id?: int|string|null, customer_id?: int|string|null}  $filters
     * @return Collection<int, array<string, mixed>>
     */
    private function collectDeliveryOrders(array $filters, string $typeLabel): Collection
    {
        $query = DeliveryOrder::query()
            ->with(['customer', 'createdBy']);

        $this->applyCreatedAtRange($query, $filters);
        if (! empty($filters['customer_id'])) {
            $query->where('business_partner_id', $filters['customer_id']);
        }

        return $query->orderBy('created_at', 'desc')->get()->map(function (DeliveryOrder $do) use ($typeLabel) {
            return [
                'document_type' => 'delivery_orders',
                'document_type_label' => $typeLabel,
                'id' => $do->id,
                'document_number' => $do->do_number,
                'created_at' => $do->created_at ?? Carbon::now(),
                'closure_status' => $do->closure_status ?? null,
                'party_name' => $do->customer?->name,
                'creator_name' => $do->createdBy?->name,
                'url' => route('delivery-orders.show', $do->id),
            ];
        });
    }

    /**
     * @param  array{date_from?: string, date_to?: string, supplier_id?: int|string|null, customer_id?: int|string|null}  $filters
     * @return Collection<int, array<string, mixed>>
     */
    private function collectSalesInvoices(array $filters, string $typeLabel): Collection
    {
        $query = SalesInvoice::query()
            ->with(['customer', 'createdBy']);

        $this->applyCreatedAtRange($query, $filters);
        if (! empty($filters['customer_id'])) {
            $query->where('business_partner_id', $filters['customer_id']);
        }

        return $query->orderBy('created_at', 'desc')->get()->map(function (SalesInvoice $si) use ($typeLabel) {
            return [
                'document_type' => 'sales_invoices',
                'document_type_label' => $typeLabel,
                'id' => $si->id,
                'document_number' => $si->invoice_no,
                'created_at' => $si->created_at ?? Carbon::now(),
                'closure_status' => $si->closure_status ?? null,
                'party_name' => $si->customer?->name,
                'creator_name' => $si->createdBy?->name,
                'url' => route('sales-invoices.show', $si->id),
            ];
        });
    }

    /**
     * @param  array{date_from?: string, date_to?: string, supplier_id?: int|string|null, customer_id?: int|string|null}  $filters
     * @return Collection<int, array<string, mixed>>
     */
    private function collectSalesReceipts(array $filters, string $typeLabel): Collection
    {
        $query = SalesReceipt::query()
            ->with(['businessPartner', 'createdBy']);

        $this->applyCreatedAtRange($query, $filters);
        if (! empty($filters['customer_id'])) {
            $query->where('business_partner_id', $filters['customer_id']);
        }

        return $query->orderBy('created_at', 'desc')->get()->map(function (SalesReceipt $sr) use ($typeLabel) {
            return [
                'document_type' => 'sales_receipts',
                'document_type_label' => $typeLabel,
                'id' => $sr->id,
                'document_number' => $sr->receipt_no,
                'created_at' => $sr->created_at ?? Carbon::now(),
                'closure_status' => $sr->closure_status ?? null,
                'party_name' => $sr->businessPartner?->name,
                'creator_name' => $sr->createdBy?->name,
                'url' => route('sales-receipts.show', $sr->id),
            ];
        });
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @param  array{date_from?: string, date_to?: string}  $filters
     */
    private function applyCreatedAtRange($query, array $filters): void
    {
        if (! empty($filters['date_from'])) {
            $query->where('created_at', '>=', Carbon::parse($filters['date_from'])->startOfDay());
        }
        if (! empty($filters['date_to'])) {
            $query->where('created_at', '<=', Carbon::parse($filters['date_to'])->endOfDay());
        }
    }
}
