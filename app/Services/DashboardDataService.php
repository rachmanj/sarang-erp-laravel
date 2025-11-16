<?php

namespace App\Services;

use App\Models\Accounting\Journal;
use App\Models\Accounting\PurchaseInvoice;
use App\Models\Accounting\SalesInvoice;
use App\Models\Asset;
use App\Models\AssetDepreciationRun;
use App\Models\Finance\Period;
use App\Models\GRGIHeader;
use App\Models\InventoryValuation;
use App\Models\InventoryWarehouseStock;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\SupplierPerformance;
use App\Models\TaxComplianceLog;
use App\Models\TaxReport;
use App\Models\TaxSetting;
use App\Models\TaxTransaction;
use App\Models\PurchaseOrderApproval;
use App\Models\SalesOrderApproval;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardDataService
{
    private const CACHE_KEY = 'dashboard:data:global';
    private const CACHE_TTL = 300;

    public function getDashboardData(bool $refresh = false): array
    {
        if ($refresh) {
            Cache::forget(self::CACHE_KEY);
        }

        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return [
                'meta' => [
                    'generated_at' => now()->toIso8601String(),
                    'cache_ttl_seconds' => self::CACHE_TTL,
                ],
                'kpis' => $this->buildKpis(),
                'finance' => $this->buildFinanceSnapshot(),
                'sales_procurement' => $this->buildSalesAndProcurementSnapshot(),
                'inventory' => $this->buildInventorySnapshot(),
                'assets' => $this->buildAssetSnapshot(),
                'compliance' => $this->buildComplianceSnapshot(),
                'configuration' => $this->buildConfigurationAlerts(),
            ];
        });
    }

    private function buildKpis(): array
    {
        $today = Carbon::today();
        $monthStart = $today->copy()->startOfMonth();

        $salesMtd = SalesInvoice::whereBetween('date', [$monthStart, $today])->sum('total_amount');
        $purchasesMtd = PurchaseInvoice::whereBetween('date', [$monthStart, $today])->sum('total_amount');

        $cashOnHand = DB::table('control_account_balances')->sum('balance');

        $pendingApprovals = PurchaseOrderApproval::where('status', 'pending')->count()
            + SalesOrderApproval::where('status', 'pending')->count();

        return [
            'sales_mtd' => (float) $salesMtd,
            'purchases_mtd' => (float) $purchasesMtd,
            'cash_on_hand' => (float) $cashOnHand,
            'approvals_pending' => $pendingApprovals,
        ];
    }

    private function buildFinanceSnapshot(): array
    {
        $arInvoices = SalesInvoice::where('closure_status', 'open')
            ->get(['due_date', 'date', 'total_amount']);
        $apInvoices = PurchaseInvoice::where('closure_status', 'open')
            ->get(['due_date', 'date', 'total_amount']);

        $openPeriods = Period::where('is_closed', false)
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->limit(6)
            ->get(['year', 'month']);

        $unpostedJournals = Journal::whereNull('posted_at')->count();

        return [
            'ar_aging' => $this->calculateAgingBuckets($arInvoices),
            'ap_aging' => $this->calculateAgingBuckets($apInvoices),
            'period_close' => [
                'open_periods' => $openPeriods->map(function (Period $period) {
                    return [
                        'year' => $period->year,
                        'month' => $period->month,
                    ];
                })->values(),
                'unposted_journals' => $unpostedJournals,
            ],
        ];
    }

    private function buildSalesAndProcurementSnapshot(): array
    {
        $salesOrderCounts = [
            'draft' => SalesOrder::where('status', 'draft')->count(),
            'approved' => SalesOrder::where('status', 'approved')->count(),
            'closed' => SalesOrder::where('status', 'closed')->count(),
        ];

        $deliveryBacklog = DB::table('delivery_orders')
            ->whereIn('status', ['pending', 'approved'])
            ->count();

        $purchaseOrderCounts = [
            'draft' => PurchaseOrder::where('status', 'draft')->count(),
            'approved' => PurchaseOrder::where('status', 'approved')->count(),
            'closed' => PurchaseOrder::where('status', 'closed')->count(),
        ];

        $openPurchaseOrders = PurchaseOrder::where('closure_status', 'open')
            ->whereNotIn('status', ['draft', 'cancelled', 'closed'])
            ->count();

        $topSuppliers = collect();

        if (Schema::hasTable('supplier_performances')) {
            $topSuppliers = SupplierPerformance::orderByDesc('overall_rating')
                ->limit(5)
                ->with('vendor:id,name')
                ->get()
                ->map(function (SupplierPerformance $performance) {
                    return [
                        'name' => optional($performance->vendor)->name ?: 'N/A',
                        'overall_rating' => (float) $performance->overall_rating,
                        'total_orders' => (int) $performance->total_orders,
                    ];
                });
        }

        return [
            'sales_orders' => $salesOrderCounts,
            'delivery_backlog' => $deliveryBacklog,
            'purchase_orders' => $purchaseOrderCounts,
            'open_purchase_orders' => $openPurchaseOrders,
            'top_suppliers' => $topSuppliers,
        ];
    }

    private function buildInventorySnapshot(): array
    {
        $totalInventoryValue = (float) InventoryValuation::sum('total_value');

        $inventoryByCategory = InventoryValuation::select(
                'inventory_items.category_id',
                DB::raw('SUM(inventory_valuations.total_value) as total_value')
            )
            ->join('inventory_items', 'inventory_items.id', '=', 'inventory_valuations.item_id')
            ->groupBy('inventory_items.category_id')
            ->orderByDesc(DB::raw('SUM(inventory_valuations.total_value)'))
            ->limit(5)
            ->get()
            ->map(function ($row) {
                return [
                    'category_id' => $row->category_id,
                    'total_value' => (float) $row->total_value,
                ];
            });

        $inventoryByWarehouse = DB::table('inventory_warehouse_stock')
            ->select('warehouse_id', DB::raw('SUM(available_quantity) as available_quantity'))
            ->groupBy('warehouse_id')
            ->orderByDesc(DB::raw('SUM(available_quantity)'))
            ->limit(5)
            ->get()
            ->map(function ($row) {
                return [
                    'warehouse_id' => $row->warehouse_id,
                    'available_quantity' => (int) $row->available_quantity,
                ];
            });

        $lowStockItems = InventoryWarehouseStock::lowStock()
            ->with('item:id,code,name')
            ->orderBy('available_quantity')
            ->limit(10)
            ->get()
            ->map(function (InventoryWarehouseStock $stock) {
                return [
                    'item_code' => optional($stock->item)->code,
                    'item_name' => optional($stock->item)->name,
                    'available_quantity' => (int) $stock->available_quantity,
                    'reorder_point' => (int) $stock->reorder_point,
                ];
            });

        $grgiPending = GRGIHeader::whereIn('status', ['draft', 'pending_approval'])->count();

        return [
            'total_value' => $totalInventoryValue,
            'by_category' => $inventoryByCategory,
            'by_warehouse' => $inventoryByWarehouse,
            'low_stock' => $lowStockItems,
            'gr_gi_pending' => $grgiPending,
        ];
    }

    private function buildAssetSnapshot(): array
    {
        $assetCount = Asset::count();
        $acquisitionValue = (float) Asset::sum('acquisition_cost');
        $bookValue = (float) Asset::sum('current_book_value');
        $pendingDepreciation = AssetDepreciationRun::where('status', 'draft')->count();

        return [
            'counts' => [
                'total_assets' => $assetCount,
            ],
            'values' => [
                'acquisition' => $acquisitionValue,
                'book' => $bookValue,
            ],
            'depreciation_pending' => $pendingDepreciation,
        ];
    }

    private function buildComplianceSnapshot(): array
    {
        $upcomingTaxDeadlines = DB::table('tax_calendar')
            ->where('is_active', true)
            ->whereDate('event_date', '>=', Carbon::today())
            ->orderBy('event_date')
            ->limit(5)
            ->get()
            ->map(function ($event) {
                return [
                    'event_name' => $event->event_name,
                    'event_type' => $event->event_type,
                    'event_date' => Carbon::parse($event->event_date)->toDateString(),
                    'tax_type' => $event->tax_type,
                ];
            });

        $recentComplianceLogs = TaxComplianceLog::orderByDesc('created_at')
            ->limit(5)
            ->get(['action', 'entity_type', 'entity_id', 'created_at'])
            ->map(function (TaxComplianceLog $log) {
                return [
                    'action' => $log->action,
                    'entity_type' => $log->entity_type,
                    'entity_id' => $log->entity_id,
                    'created_at' => optional($log->created_at)->toIso8601String(),
                ];
            });

        return [
            'tax_deadlines' => $upcomingTaxDeadlines,
            'compliance_logs' => $recentComplianceLogs,
        ];
    }

    private function buildConfigurationAlerts(): array
    {
        $openDocuments = DB::table('document_relationships')
            ->select('target_document_type as document_type', DB::raw('COUNT(*) as total'))
            ->groupBy('target_document_type')
            ->orderByDesc(DB::raw('COUNT(*)'))
            ->limit(5)
            ->get()
            ->map(function ($row) {
                return [
                    'document_type' => $row->document_type,
                    'total' => (int) $row->total,
                ];
            });

        $inactiveTaxSettings = TaxSetting::where('is_active', false)->count();
        $pendingTaxReports = TaxReport::whereIn('status', ['draft', 'submitted'])->count();
        $unpostedTaxTransactions = TaxTransaction::where('status', 'pending')->count();

        return [
            'open_document_links' => $openDocuments,
            'inactive_tax_settings' => $inactiveTaxSettings,
            'pending_tax_reports' => $pendingTaxReports,
            'unposted_tax_transactions' => $unpostedTaxTransactions,
        ];
    }

    private function calculateAgingBuckets(Collection $documents): array
    {
        $buckets = [
            '0_30' => 0.0,
            '31_60' => 0.0,
            '61_90' => 0.0,
            '90_plus' => 0.0,
        ];

        $today = Carbon::today();

        foreach ($documents as $document) {
            $amount = (float) ($document->total_amount ?? 0);
            $dueDate = $document->due_date ? Carbon::parse($document->due_date) : Carbon::parse($document->date);

            if (!$dueDate) {
                $buckets['0_30'] += $amount;
                continue;
            }

            $daysPastDue = $dueDate->diffInDays($today, false);

            if ($daysPastDue <= 30) {
                $buckets['0_30'] += $amount;
            } elseif ($daysPastDue <= 60) {
                $buckets['31_60'] += $amount;
            } elseif ($daysPastDue <= 90) {
                $buckets['61_90'] += $amount;
            } else {
                $buckets['90_plus'] += $amount;
            }
        }

        return $buckets;
    }
}

