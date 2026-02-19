<?php

namespace App\Services;

use App\Models\GRGIHeader;
use App\Models\InventoryTransaction;
use App\Models\InventoryValuation;
use App\Models\InventoryWarehouseStock;
use App\Models\ProductCategory;
use App\Models\Warehouse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class InventoryDashboardDataService
{
    private const CACHE_KEY = 'dashboard:data:inventory';

    private const CACHE_TTL = 300;

    public function getInventoryDashboardData(array $filters = [], bool $refresh = false): array
    {
        $hasFilters = !empty(array_filter($filters, fn ($v) => $v !== null && $v !== ''));

        if ($hasFilters) {
            return $this->buildDashboardPayload($filters);
        }

        if ($refresh) {
            Cache::forget(self::CACHE_KEY);
        }

        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () use ($filters) {
            return $this->buildDashboardPayload($filters);
        });
    }

    private function buildDashboardPayload(array $filters): array
    {
        return [
            'meta' => [
                'generated_at' => now()->toIso8601String(),
                'cache_ttl_seconds' => self::CACHE_TTL,
                'filters' => $filters,
            ],
            'kpis' => $this->buildKpis($filters),
            'valuation_by_category' => $this->buildValuationByCategory($filters),
            'stock_by_warehouse' => $this->buildStockByWarehouse($filters),
            'low_stock_alerts' => $this->buildLowStockAlerts($filters),
            'recent_movements' => $this->buildRecentMovements($filters),
            'movement_summary' => $this->buildMovementSummary($filters),
        ];
    }

    private function buildKpis(array $filters): array
    {
        $totalValuation = (float) InventoryValuation::sum('total_value');

        $lowStockQuery = InventoryWarehouseStock::lowStock();
        $stockQuery = InventoryWarehouseStock::query();

        if (!empty($filters['warehouse_id'])) {
            $lowStockQuery->where('warehouse_id', $filters['warehouse_id']);
            $stockQuery->where('warehouse_id', $filters['warehouse_id']);
        }

        if (!empty($filters['category_id'])) {
            $lowStockQuery->whereHas('item', fn ($q) => $q->where('category_id', $filters['category_id']));
            $stockQuery->whereHas('item', fn ($q) => $q->where('category_id', $filters['category_id']));
        }

        $lowStockItemsCount = (clone $lowStockQuery)->select('item_id')->groupBy('item_id')->get()->count();

        $outOfStockQuery = DB::table('inventory_warehouse_stock as iws')
            ->select('iws.item_id')
            ->groupBy('iws.item_id')
            ->havingRaw('SUM(iws.available_quantity) = 0');
        if (!empty($filters['warehouse_id'])) {
            $outOfStockQuery->where('iws.warehouse_id', $filters['warehouse_id']);
        }
        if (!empty($filters['category_id'])) {
            $outOfStockQuery->join('inventory_items as ii', 'ii.id', '=', 'iws.item_id')
                ->where('ii.category_id', $filters['category_id']);
        }
        $outOfStockCount = $outOfStockQuery->get()->count();

        $grgiPending = GRGIHeader::whereIn('status', ['draft', 'pending_approval'])->count();

        return [
            'total_valuation' => $totalValuation,
            'low_stock_items' => $lowStockItemsCount,
            'out_of_stock' => $outOfStockCount,
            'gr_gi_pending' => $grgiPending,
        ];
    }

    private function buildValuationByCategory(array $filters): array
    {
        $query = DB::table('inventory_valuations as iv')
            ->join('inventory_items as ii', 'ii.id', '=', 'iv.item_id')
            ->leftJoin('product_categories as pc', 'pc.id', '=', 'ii.category_id')
            ->select('ii.category_id', 'pc.name as category_name', DB::raw('SUM(iv.total_value) as total_value'))
            ->groupBy('ii.category_id', 'pc.name')
            ->orderByDesc(DB::raw('SUM(iv.total_value)'))
            ->limit(10);

        if (!empty($filters['category_id'])) {
            $query->where('ii.category_id', $filters['category_id']);
        }

        return $query->get()->map(fn ($row) => [
            'category_id' => $row->category_id,
            'category_name' => $row->category_name ?? 'Uncategorized',
            'total_value' => (float) $row->total_value,
        ])->toArray();
    }

    private function buildStockByWarehouse(array $filters): array
    {
        $query = DB::table('inventory_warehouse_stock as iws')
            ->join('warehouses as w', 'w.id', '=', 'iws.warehouse_id')
            ->select('iws.warehouse_id', 'w.code', 'w.name', DB::raw('SUM(iws.available_quantity) as available_quantity'))
            ->groupBy('iws.warehouse_id', 'w.code', 'w.name')
            ->orderByDesc(DB::raw('SUM(iws.available_quantity)'));

        if (!empty($filters['warehouse_id'])) {
            $query->where('iws.warehouse_id', $filters['warehouse_id']);
        }

        if (!empty($filters['category_id'])) {
            $query->join('inventory_items as ii', 'ii.id', '=', 'iws.item_id')
                ->where('ii.category_id', $filters['category_id']);
        }

        return $query->get()->map(fn ($row) => [
            'warehouse_id' => $row->warehouse_id,
            'warehouse_code' => $row->code,
            'warehouse_name' => $row->name,
            'available_quantity' => (int) $row->available_quantity,
        ])->toArray();
    }

    private function buildLowStockAlerts(array $filters): array
    {
        $query = InventoryWarehouseStock::lowStock()
            ->with(['item:id,code,name,unit_of_measure', 'warehouse:id,code,name'])
            ->orderBy('available_quantity')
            ->limit(15);

        if (!empty($filters['warehouse_id'])) {
            $query->where('warehouse_id', $filters['warehouse_id']);
        }

        if (!empty($filters['category_id'])) {
            $query->whereHas('item', fn ($q) => $q->where('category_id', $filters['category_id']));
        }

        return $query->get()->map(function ($stock) {
            $status = 'critical';
            if ($stock->available_quantity > 0) {
                $status = $stock->available_quantity <= $stock->reorder_point ? 'warning' : 'ok';
            }

            return [
                'item_id' => $stock->item_id,
                'item_code' => optional($stock->item)->code,
                'item_name' => optional($stock->item)->name,
                'unit_of_measure' => optional($stock->item)->unit_of_measure ?? 'EA',
                'available_quantity' => (int) $stock->available_quantity,
                'reorder_point' => (int) $stock->reorder_point,
                'warehouse_code' => optional($stock->warehouse)->code,
                'status' => $status,
            ];
        })->toArray();
    }

    private function buildRecentMovements(array $filters): array
    {
        $query = InventoryTransaction::with(['item:id,code,name', 'warehouse:id,code,name'])
            ->orderByDesc('transaction_date')
            ->orderByDesc('created_at')
            ->limit(15);

        if (!empty($filters['date_from'])) {
            $query->whereDate('transaction_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('transaction_date', '<=', $filters['date_to']);
        }

        if (!empty($filters['warehouse_id'])) {
            $query->where('warehouse_id', $filters['warehouse_id']);
        }

        if (!empty($filters['category_id'])) {
            $query->whereHas('item', fn ($q) => $q->where('category_id', $filters['category_id']));
        }

        return $query->get()->map(function ($tx) {
            $referenceUrl = $this->getReferenceUrl($tx);

            return [
                'id' => $tx->id,
                'transaction_date' => $tx->transaction_date?->format('d/m/Y'),
                'transaction_type' => $tx->transaction_type,
                'quantity' => $tx->quantity,
                'item_code' => optional($tx->item)->code,
                'item_name' => optional($tx->item)->name,
                'reference_type' => $tx->reference_type,
                'reference_id' => $tx->reference_id,
                'reference_url' => $referenceUrl,
                'notes' => $tx->notes,
            ];
        })->toArray();
    }

    private function getReferenceUrl(InventoryTransaction $tx): ?string
    {
        if (!$tx->reference_type || !$tx->reference_id) {
            return null;
        }

        $routes = [
            'purchase_invoice' => 'purchase-invoices.show',
            'delivery_order_line' => 'delivery-orders.show',
            'sales_order' => 'sales-orders.show',
            'stock_adjustment' => null,
            'stock_transfer' => null,
        ];

        $routeName = $routes[$tx->reference_type] ?? null;
        if (!$routeName) {
            return null;
        }

        $id = $tx->reference_id;
        if ($tx->reference_type === 'delivery_order_line') {
            $line = \App\Models\DeliveryOrderLine::find($tx->reference_id);
            $id = $line?->delivery_order_id ?? $tx->reference_id;
        }

        try {
            return route($routeName, $id);
        } catch (\Exception $e) {
            return null;
        }
    }

    private function buildMovementSummary(array $filters): array
    {
        $today = Carbon::today();
        $monthStart = $today->copy()->startOfMonth();

        $query = InventoryTransaction::query()
            ->whereBetween('transaction_date', [$monthStart, $today]);

        if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
            $query->whereBetween('transaction_date', [$filters['date_from'], $filters['date_to']]);
        } elseif (!empty($filters['date_from'])) {
            $query->whereDate('transaction_date', '>=', $filters['date_from']);
        } elseif (!empty($filters['date_to'])) {
            $query->whereDate('transaction_date', '<=', $filters['date_to']);
        }

        if (!empty($filters['warehouse_id'])) {
            $query->where('warehouse_id', $filters['warehouse_id']);
        }

        if (!empty($filters['category_id'])) {
            $query->whereHas('item', fn ($q) => $q->where('category_id', $filters['category_id']));
        }

        $purchasesIn = (int) (clone $query)->where('transaction_type', 'purchase')->sum('quantity');
        $salesOut = (int) abs((clone $query)->where('transaction_type', 'sale')->sum('quantity'));
        $adjustments = (int) (clone $query)->where('transaction_type', 'adjustment')->sum('quantity');
        $netChange = $purchasesIn - $salesOut + $adjustments;

        return [
            'purchases_in' => $purchasesIn,
            'sales_out' => $salesOut,
            'adjustments' => $adjustments,
            'net_change' => $netChange,
        ];
    }
}
