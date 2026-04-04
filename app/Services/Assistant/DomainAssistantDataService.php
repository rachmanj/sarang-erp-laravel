<?php

namespace App\Services\Assistant;

use App\Models\Accounting\SalesInvoice;
use App\Models\Accounting\SalesInvoiceLine;
use App\Models\BusinessPartner;
use App\Models\CompanyEntity;
use App\Models\DeliveryOrder;
use App\Models\GoodsReceiptPO;
use App\Models\InventoryItem;
use App\Models\InventoryWarehouseStock;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Services\CompanyEntityService;
use Illuminate\Database\Eloquent\Builder;

class DomainAssistantDataService
{
    private const MAX_LIMIT = 20;

    private const MAX_RANGE_DAYS = 90;

    public function __construct(
        private CompanyEntityService $companyEntityService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function execute(string $toolName, array $arguments, bool $showAllRecords): array
    {
        $method = \Illuminate\Support\Str::camel($toolName);
        if (! method_exists($this, $method)) {
            return ['error' => 'Unknown tool: '.$toolName];
        }

        return $this->{$method}($arguments, $showAllRecords);
    }

    /**
     * @return array<string, mixed>
     */
    public function getErpSummary(array $arguments, bool $showAllRecords): array
    {
        unset($arguments);

        $from = now()->subDays(self::MAX_RANGE_DAYS)->toDateString();
        $to = now()->toDateString();

        $out = [
            'date_range' => ['from' => $from, 'to' => $to],
            'entity_scope' => $showAllRecords ? 'all_active_entities' : 'default_entity_only',
        ];

        $so = SalesOrder::query();
        $this->scopeCompanyEntity($so, $showAllRecords);
        $so->whereBetween('date', [$from, $to]);
        $out['sales_orders_by_status'] = $so->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status')->all();

        $po = PurchaseOrder::query();
        $this->scopeCompanyEntity($po, $showAllRecords);
        $po->whereBetween('date', [$from, $to]);
        $out['purchase_orders_by_status'] = $po->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status')->all();

        $do = DeliveryOrder::query();
        $this->scopeCompanyEntity($do, $showAllRecords);
        $do->whereBetween('planned_delivery_date', [$from, $to]);
        $out['delivery_orders_by_status'] = $do->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status')->all();

        $gr = GoodsReceiptPO::query();
        $this->scopeCompanyEntity($gr, $showAllRecords);
        $gr->whereBetween('date', [$from, $to]);
        $out['goods_receipt_po_by_status'] = $gr->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status')->all();

        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    public function searchSalesOrders(array $arguments, bool $showAllRecords): array
    {
        $limit = $this->capLimit($arguments['limit'] ?? null);
        [$from, $to] = $this->parseDateRange($arguments['date_from'] ?? null, $arguments['date_to'] ?? null);

        $q = SalesOrder::query()->with(['businessPartner:id,name,code']);
        $this->scopeCompanyEntity($q, $showAllRecords);
        $q->whereBetween('date', [$from, $to]);

        $status = $arguments['status'] ?? null;
        if (is_string($status) && $status !== '') {
            $this->applyStatusFilter($q, $status, 'sales_order');
        }

        if (! empty($arguments['customer_query'])) {
            $needle = $this->escapeLike((string) $arguments['customer_query']);
            $q->whereHas('businessPartner', function (Builder $bp) use ($needle) {
                $bp->where('name', 'like', '%'.$needle.'%')
                    ->orWhere('code', 'like', '%'.$needle.'%');
            });
        }

        $q->orderByDesc('date')->orderByDesc('id')->limit($limit);

        return [
            'rows' => $q->get()->map(fn (SalesOrder $row) => [
                'id' => $row->id,
                'order_no' => $row->order_no,
                'date' => $row->date?->toDateString(),
                'customer' => $row->businessPartner?->name,
                'status' => $row->status,
                'total_amount' => (string) $row->total_amount,
            ])->all(),
        ];
    }

    /**
     * AR Sales Invoices (faktur penjualan). When the user gives an invoice / faktur number, pass it as invoice_query — not as customer_query on sales orders.
     *
     * @return array<string, mixed>
     */
    public function searchSalesInvoices(array $arguments, bool $showAllRecords): array
    {
        $limit = $this->capLimit($arguments['limit'] ?? null);

        $q = SalesInvoice::query()->with(['businessPartner:id,name,code', 'companyEntity:id,code,name']);

        $hasInvoiceQuery = ! empty($arguments['invoice_query']);
        if ($hasInvoiceQuery) {
            // Document numbers are unique; default entity (e.g. PT vs CV) would hide valid rows (e.g. invoice on entity 1 while default is 2).
            $this->scopeActiveCompanyEntities($q);
            $needle = $this->escapeLike((string) $arguments['invoice_query']);
            $q->where(function (Builder $w) use ($needle) {
                $w->where('invoice_no', 'like', '%'.$needle.'%')
                    ->orWhere('reference_no', 'like', '%'.$needle.'%');
            });
        } else {
            $this->scopeCompanyEntity($q, $showAllRecords);
            [$from, $to] = $this->parseDateRange($arguments['date_from'] ?? null, $arguments['date_to'] ?? null);
            $q->whereBetween('date', [$from, $to]);
        }

        $status = $arguments['status'] ?? null;
        if (is_string($status) && $status !== '') {
            $q->where('status', $status);
        }

        if (! empty($arguments['customer_query'])) {
            $needle = $this->escapeLike((string) $arguments['customer_query']);
            $q->whereHas('businessPartner', function (Builder $bp) use ($needle) {
                $bp->where('name', 'like', '%'.$needle.'%')
                    ->orWhere('code', 'like', '%'.$needle.'%');
            });
        }

        $q->orderByDesc('date')->orderByDesc('id')->limit($limit);

        return [
            'rows' => $q->get()->map(fn (SalesInvoice $row) => [
                'id' => $row->id,
                'invoice_no' => $row->invoice_no,
                'reference_no' => $row->reference_no,
                'date' => $row->date?->toDateString(),
                'due_date' => $row->due_date?->toDateString(),
                'customer' => $row->businessPartner?->name,
                'total_amount' => (string) $row->total_amount,
                'status' => $row->status,
                'posted_at' => $row->posted_at?->toAtomString(),
                'company_entity' => $row->companyEntity?->name,
            ])->all(),
        ];
    }

    /**
     * Full header + line items for one AR Sales Invoice. Use when the user asks for detail / baris / line items / isi faktur.
     * Resolves invoice number across all active company entities (not only the default entity).
     *
     * @return array<string, mixed>
     */
    public function getSalesInvoiceDetail(array $arguments, bool $showAllRecords): array
    {
        unset($showAllRecords);

        $id = isset($arguments['invoice_id']) ? (int) $arguments['invoice_id'] : null;
        $no = isset($arguments['invoice_no']) ? trim((string) $arguments['invoice_no']) : '';

        if ($id === null && $no === '') {
            return ['error' => 'Provide invoice_no or invoice_id'];
        }

        $q = SalesInvoice::query()
            ->with([
                'businessPartner:id,name,code',
                'companyEntity:id,code,name',
                'lines' => fn ($lines) => $lines->orderBy('id')->limit(100),
            ]);

        $this->scopeActiveCompanyEntities($q);

        if ($id !== null) {
            $inv = $q->whereKey($id)->first();
        } else {
            $needle = $this->escapeLike($no);
            $inv = $q->where(function (Builder $w) use ($needle) {
                $w->where('invoice_no', 'like', '%'.$needle.'%')
                    ->orWhere('reference_no', 'like', '%'.$needle.'%');
            })->orderByDesc('id')->first();
        }

        if ($inv === null) {
            return ['error' => 'Sales invoice not found'];
        }

        $lines = $inv->lines->map(fn (SalesInvoiceLine $line) => [
            'line_id' => $line->id,
            'item_code' => $line->item_code,
            'item_name' => $line->item_name,
            'description' => $line->description,
            'qty' => (string) $line->qty,
            'unit_price' => (string) $line->unit_price,
            'amount' => (string) $line->amount,
        ])->values()->all();

        return [
            'header' => [
                'id' => $inv->id,
                'invoice_no' => $inv->invoice_no,
                'reference_no' => $inv->reference_no,
                'date' => $inv->date?->toDateString(),
                'due_date' => $inv->due_date?->toDateString(),
                'customer' => $inv->businessPartner?->name,
                'company_entity' => $inv->companyEntity?->name,
                'total_amount' => (string) $inv->total_amount,
                'status' => $inv->status,
                'posted_at' => $inv->posted_at?->toAtomString(),
                'description' => $inv->description,
            ],
            'lines' => $lines,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function searchPurchaseOrders(array $arguments, bool $showAllRecords): array
    {
        $limit = $this->capLimit($arguments['limit'] ?? null);
        [$from, $to] = $this->parseDateRange($arguments['date_from'] ?? null, $arguments['date_to'] ?? null);

        $q = PurchaseOrder::query()->with(['businessPartner:id,name,code']);
        $this->scopeCompanyEntity($q, $showAllRecords);
        $q->whereBetween('date', [$from, $to]);

        $status = $arguments['status'] ?? null;
        if (is_string($status) && $status !== '') {
            $this->applyStatusFilter($q, $status, 'purchase_order');
        }

        if (! empty($arguments['supplier_query'])) {
            $needle = $this->escapeLike((string) $arguments['supplier_query']);
            $q->whereHas('businessPartner', function (Builder $bp) use ($needle) {
                $bp->where('name', 'like', '%'.$needle.'%')
                    ->orWhere('code', 'like', '%'.$needle.'%');
            });
        }

        $q->orderByDesc('date')->orderByDesc('id')->limit($limit);

        return [
            'rows' => $q->get()->map(fn (PurchaseOrder $row) => [
                'id' => $row->id,
                'order_no' => $row->order_no,
                'date' => $row->date?->toDateString(),
                'supplier' => $row->businessPartner?->name,
                'status' => $row->status,
                'total_amount' => (string) $row->total_amount,
            ])->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function searchDeliveryOrders(array $arguments, bool $showAllRecords): array
    {
        $limit = $this->capLimit($arguments['limit'] ?? null);
        [$from, $to] = $this->parseDateRange($arguments['date_from'] ?? null, $arguments['date_to'] ?? null);

        $q = DeliveryOrder::query()->with(['customer:id,name,code']);
        $this->scopeCompanyEntity($q, $showAllRecords);
        $q->whereBetween('planned_delivery_date', [$from, $to]);

        $status = $arguments['status'] ?? null;
        if (is_string($status) && $status !== '') {
            $q->where('status', $status);
        }

        if (! empty($arguments['customer_query'])) {
            $needle = $this->escapeLike((string) $arguments['customer_query']);
            $q->whereHas('customer', function (Builder $bp) use ($needle) {
                $bp->where('name', 'like', '%'.$needle.'%')
                    ->orWhere('code', 'like', '%'.$needle.'%');
            });
        }

        $q->orderByDesc('planned_delivery_date')->orderByDesc('id')->limit($limit);

        return [
            'rows' => $q->get()->map(fn (DeliveryOrder $row) => [
                'id' => $row->id,
                'do_number' => $row->do_number,
                'planned_delivery_date' => $row->planned_delivery_date?->toDateString(),
                'customer' => $row->customer?->name,
                'status' => $row->status,
            ])->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function searchGoodsReceiptPo(array $arguments, bool $showAllRecords): array
    {
        $limit = $this->capLimit($arguments['limit'] ?? null);
        [$from, $to] = $this->parseDateRange($arguments['date_from'] ?? null, $arguments['date_to'] ?? null);

        $q = GoodsReceiptPO::query()->with(['businessPartner:id,name,code']);
        $this->scopeCompanyEntity($q, $showAllRecords);
        $q->whereBetween('date', [$from, $to]);

        $status = $arguments['status'] ?? null;
        if (is_string($status) && $status !== '') {
            $q->where('status', $status);
        }

        if (! empty($arguments['supplier_query'])) {
            $needle = $this->escapeLike((string) $arguments['supplier_query']);
            $q->whereHas('businessPartner', function (Builder $bp) use ($needle) {
                $bp->where('name', 'like', '%'.$needle.'%')
                    ->orWhere('code', 'like', '%'.$needle.'%');
            });
        }

        $q->orderByDesc('date')->orderByDesc('id')->limit($limit);

        return [
            'rows' => $q->get()->map(fn (GoodsReceiptPO $row) => [
                'id' => $row->id,
                'grn_no' => $row->grn_no,
                'date' => $row->date?->toDateString(),
                'supplier' => $row->businessPartner?->name,
                'status' => $row->status,
                'total_amount' => (string) $row->total_amount,
            ])->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function searchInventoryItems(array $arguments, bool $showAllRecords): array
    {
        unset($showAllRecords);
        $limit = $this->capLimit($arguments['limit'] ?? null);

        $q = InventoryItem::query()->where('is_active', true)->with(['category:id,name', 'defaultWarehouse:id,name']);

        if (! empty($arguments['name_query'])) {
            $needle = $this->escapeLike((string) $arguments['name_query']);
            $q->where(function (Builder $inner) use ($needle) {
                $inner->where('name', 'like', '%'.$needle.'%')
                    ->orWhere('code', 'like', '%'.$needle.'%');
            });
        }

        if (! empty($arguments['category'])) {
            $cat = $this->escapeLike((string) $arguments['category']);
            $q->whereHas('category', function (Builder $c) use ($cat) {
                $c->where('name', 'like', '%'.$cat.'%');
            });
        }

        if (! empty($arguments['warehouse_id'])) {
            $wid = (int) $arguments['warehouse_id'];
            $q->where('default_warehouse_id', $wid);
        }

        if (! empty($arguments['low_stock_only']) && filter_var($arguments['low_stock_only'], FILTER_VALIDATE_BOOLEAN)) {
            $q->whereHas('warehouseStock', function (Builder $ws) {
                $ws->whereColumn('available_quantity', '<=', 'reorder_point')
                    ->orWhereColumn('available_quantity', '<=', 'min_stock_level');
            });
        }

        $q->orderBy('code')->limit($limit);

        return [
            'rows' => $q->get()->map(function (InventoryItem $row) {
                $stock = InventoryWarehouseStock::where('item_id', $row->id)->sum('available_quantity');

                return [
                    'id' => $row->id,
                    'code' => $row->code,
                    'name' => $row->name,
                    'category' => $row->category?->name,
                    'default_warehouse' => $row->defaultWarehouse?->name,
                    'available_quantity_total' => (int) $stock,
                    'reorder_point' => $row->reorder_point,
                ];
            })->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function searchBusinessPartners(array $arguments, bool $showAllRecords): array
    {
        unset($showAllRecords);
        $limit = $this->capLimit($arguments['limit'] ?? null);

        $q = BusinessPartner::query();

        $type = $arguments['type'] ?? 'both';
        if ($type === 'customer') {
            $q->where('partner_type', 'customer');
        } elseif ($type === 'supplier') {
            $q->where('partner_type', 'supplier');
        }

        if (! empty($arguments['name_query'])) {
            $needle = $this->escapeLike((string) $arguments['name_query']);
            $q->where(function (Builder $inner) use ($needle) {
                $inner->where('name', 'like', '%'.$needle.'%')
                    ->orWhere('code', 'like', '%'.$needle.'%');
            });
        }

        $q->orderBy('name')->limit($limit);

        return [
            'rows' => $q->get()->map(fn (BusinessPartner $row) => [
                'id' => $row->id,
                'code' => $row->code,
                'name' => $row->name,
                'partner_type' => $row->partner_type,
                'status' => $row->status,
            ])->all(),
        ];
    }

    private function scopeCompanyEntity(Builder $query, bool $showAllRecords): void
    {
        if ($showAllRecords) {
            return;
        }

        try {
            $id = $this->companyEntityService->getDefaultEntity()->id;
            $query->where('company_entity_id', $id);
        } catch (\Throwable) {
        }
    }

    /**
     * Limit to active legal entities in the system (still tenant-scoped by DB). Used for invoice document lookup by number.
     */
    private function scopeActiveCompanyEntities(Builder $query): void
    {
        $ids = CompanyEntity::query()->where('is_active', true)->pluck('id');
        if ($ids->isNotEmpty()) {
            $query->whereIn('company_entity_id', $ids);
        }
    }

    private function capLimit(mixed $limit): int
    {
        $n = (int) ($limit ?? self::MAX_LIMIT);

        return max(1, min(self::MAX_LIMIT, $n));
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function parseDateRange(mixed $from, mixed $to): array
    {
        $end = $to ? (string) $to : now()->toDateString();
        $start = $from ? (string) $from : now()->subDays(self::MAX_RANGE_DAYS)->toDateString();

        $endDt = \Carbon\Carbon::parse($end)->startOfDay();
        $startDt = \Carbon\Carbon::parse($start)->startOfDay();

        if ($endDt->lt($startDt)) {
            [$startDt, $endDt] = [$endDt, $startDt];
        }

        if ($startDt->diffInDays($endDt) > self::MAX_RANGE_DAYS) {
            $startDt = $endDt->copy()->subDays(self::MAX_RANGE_DAYS);
        }

        return [$startDt->toDateString(), $endDt->toDateString()];
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    private function applyStatusFilter(Builder $q, string $status, string $kind): void
    {
        $s = strtolower(trim($status));
        if ($s === 'open') {
            if ($kind === 'sales_order') {
                $q->whereNotIn('status', ['closed', 'cancelled']);
            } elseif ($kind === 'purchase_order') {
                $q->whereNotIn('status', ['closed', 'cancelled', 'received']);
            }

            return;
        }

        $q->where('status', $status);
    }
}
