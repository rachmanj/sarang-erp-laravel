<?php

namespace App\Services;

use App\Models\Accounting\SalesInvoice;
use App\Models\Accounting\SalesReceipt;
use App\Models\DeliveryOrder;
use App\Models\SalesOrder;
use App\Models\SalesOrderApproval;
use App\Models\SalesQuotation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SalesDashboardDataService
{
    private const CACHE_KEY = 'dashboard:data:sales';
    private const CACHE_TTL = 300;

    public function getSalesDashboardData(array $filters = [], bool $refresh = false): array
    {
        $hasFilters = !empty(array_filter($filters, fn ($v) => $v !== null && $v !== ''));

        if ($hasFilters) {
            return $this->buildDashboardPayload($filters);
        }

        if ($refresh) {
            Cache::forget(self::CACHE_KEY);
        }

        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return $this->buildDashboardPayload([]);
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
            'overview' => $this->buildSalesOverview($filters),
            'kpis' => $this->buildSalesKpis($filters),
            'ar_aging' => $this->buildArAging($filters),
            'sales_quotations' => $this->buildSalesQuotationStats($filters),
            'sales_orders' => $this->buildSalesOrderStats(),
            'sales_invoices' => $this->buildSalesInvoiceStats($filters),
            'delivery_orders' => $this->buildDeliveryOrderStats(),
            'sales_receipts' => $this->buildSalesReceiptStats($filters),
            'funnel' => $this->getSalesFunnelCounts(),
            'customers' => $this->buildCustomerStats($filters),
            'recent_invoices' => $this->getRecentInvoices($filters),
        ];
    }

    private function buildSalesOverview(array $filters = []): array
    {
        $today = Carbon::today();
        $monthStart = $today->copy()->startOfMonth();
        $yearStart = $today->copy()->startOfYear();

        $siQuery = SalesInvoice::where('status', 'posted');
        if (!empty($filters['customer_id'])) {
            $siQuery->where('business_partner_id', (int) $filters['customer_id']);
        }

        $salesMtd = (clone $siQuery)->whereBetween('date', [$monthStart, $today])->sum('total_amount');
        $salesYtd = (clone $siQuery)->whereBetween('date', [$yearStart, $today])->sum('total_amount');
        $openPipelineValue = SalesOrder::where('closure_status', 'open')
            ->whereNotIn('status', ['draft', 'cancelled', 'closed'])
            ->sum('total_amount');
        $outstandingAr = $this->calculateOutstandingAr($filters);

        $srQuery = SalesReceipt::whereNotNull('posted_at');
        if (!empty($filters['customer_id'])) {
            $srQuery->where('business_partner_id', (int) $filters['customer_id']);
        }
        $collectionsMtd = (clone $srQuery)->whereBetween('date', [$monthStart, $today])->sum('total_amount');

        return [
            'sales_mtd' => (float) $salesMtd,
            'sales_ytd' => (float) $salesYtd,
            'open_pipeline_value' => (float) $openPipelineValue,
            'outstanding_ar' => (float) $outstandingAr,
            'collections_mtd' => (float) $collectionsMtd,
        ];
    }

    private function buildSalesKpis(array $filters = []): array
    {
        $today = Carbon::today();
        $monthStart = $today->copy()->startOfMonth();

        $siQuery = SalesInvoice::whereBetween('date', [$monthStart, $today])
            ->where('status', 'posted');
        if (!empty($filters['customer_id'])) {
            $siQuery->where('business_partner_id', (int) $filters['customer_id']);
        }
        $salesMtd = $siQuery->sum('total_amount');

        $outstandingAr = $this->calculateOutstandingAr($filters);

        $pendingSalesApprovals = SalesOrderApproval::where('status', 'pending')->count();

        $openSalesOrders = SalesOrder::where('closure_status', 'open')
            ->whereNotIn('status', ['draft', 'cancelled', 'closed'])
            ->count();

        return [
            'sales_mtd' => (float) $salesMtd,
            'outstanding_ar' => (float) $outstandingAr,
            'pending_approvals' => $pendingSalesApprovals,
            'open_sales_orders' => $openSalesOrders,
        ];
    }

    private function buildSalesQuotationStats(array $filters = []): array
    {
        $query = SalesQuotation::query();
        if (!empty($filters['customer_id'])) {
            $query->where('business_partner_id', (int) $filters['customer_id']);
        }
        if (!empty($filters['date_from'])) {
            $query->whereDate('date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('date', '<=', $filters['date_to']);
        }

        $total = (clone $query)->count();
        $pending = (clone $query)->whereIn('status', ['draft', 'sent'])->count();
        $accepted = (clone $query)->where('status', 'accepted')->count();
        $converted = (clone $query)->where('status', 'converted')->count();
        $openValue = (clone $query)->whereNotIn('status', ['converted', 'rejected', 'expired'])
            ->sum('total_amount');

        return [
            'total' => $total,
            'pending' => $pending,
            'accepted' => $accepted,
            'converted' => $converted,
            'open_value' => (float) $openValue,
        ];
    }

    private function buildSalesReceiptStats(array $filters = []): array
    {
        $query = SalesReceipt::whereNotNull('posted_at');
        if (!empty($filters['customer_id'])) {
            $query->where('business_partner_id', (int) $filters['customer_id']);
        }
        if (!empty($filters['date_from'])) {
            $query->whereDate('date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('date', '<=', $filters['date_to']);
        }

        $today = Carbon::today();
        $monthStart = $today->copy()->startOfMonth();

        $total = (clone $query)->count();
        $totalPosted = (clone $query)->sum('total_amount');
        $mtdCollected = (clone $query)->whereBetween('date', [$monthStart, $today])->sum('total_amount');

        return [
            'total' => $total,
            'total_posted' => (float) $totalPosted,
            'mtd_collected' => (float) $mtdCollected,
        ];
    }

    private function getSalesFunnelCounts(): array
    {
        return [
            'sq_count' => SalesQuotation::count(),
            'so_count' => SalesOrder::count(),
            'do_count' => DeliveryOrder::count(),
            'si_count' => SalesInvoice::count(),
            'sr_count' => SalesReceipt::whereNotNull('posted_at')->count(),
        ];
    }

    private function buildArAging(array $filters = []): array
    {
        $invoicesQuery = DB::table('sales_invoices as si')
            ->leftJoin('sales_receipt_allocations as sra', 'sra.invoice_id', '=', 'si.id')
            ->select(
                'si.id',
                'si.invoice_no',
                'si.date',
                'si.due_date',
                'si.total_amount',
                'si.business_partner_id',
                DB::raw('COALESCE(SUM(sra.amount), 0) as paid_amount'),
                DB::raw('(si.total_amount - COALESCE(SUM(sra.amount), 0)) as outstanding_amount')
            )
            ->where('si.closure_status', 'open')
            ->where('si.status', 'posted');

        if (!empty($filters['customer_id'])) {
            $invoicesQuery->where('si.business_partner_id', (int) $filters['customer_id']);
        }
        if (!empty($filters['date_from'])) {
            $invoicesQuery->whereDate('si.date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $invoicesQuery->whereDate('si.date', '<=', $filters['date_to']);
        }

        $invoices = $invoicesQuery
            ->groupBy('si.id', 'si.invoice_no', 'si.date', 'si.due_date', 'si.total_amount', 'si.business_partner_id')
            ->get();

        $buckets = [
            'current' => 0.0,
            '1_30' => 0.0,
            '31_60' => 0.0,
            '61_90' => 0.0,
            '90_plus' => 0.0,
            'total' => 0.0,
        ];

        $today = Carbon::today();
        $detailedAging = [];

        foreach ($invoices as $invoice) {
            $outstanding = (float) $invoice->outstanding_amount;
            if ($outstanding <= 0) {
                continue;
            }

            $dueDate = $invoice->due_date 
                ? Carbon::parse($invoice->due_date) 
                : Carbon::parse($invoice->date)->addDays(30);

            $daysPastDue = $dueDate->diffInDays($today, false);

            $bucket = 'current';
            $daysOverdue = 0;
            if ($daysPastDue > 0) {
                $daysOverdue = $daysPastDue;
                if ($daysOverdue <= 30) {
                    $bucket = '1_30';
                } elseif ($daysOverdue <= 60) {
                    $bucket = '31_60';
                } elseif ($daysOverdue <= 90) {
                    $bucket = '61_90';
                } else {
                    $bucket = '90_plus';
                }
            }

            $buckets[$bucket] += $outstanding;
            $buckets['total'] += $outstanding;

            $detailedAging[] = [
                'invoice_no' => $invoice->invoice_no,
                'due_date' => $invoice->due_date ?? $invoice->date,
                'total_amount' => (float) $invoice->total_amount,
                'paid_amount' => (float) $invoice->paid_amount,
                'outstanding_amount' => $outstanding,
                'days_past_due' => $daysOverdue,
                'bucket' => $bucket,
                'business_partner_id' => $invoice->business_partner_id,
            ];
        }

        $detailed = collect($detailedAging)->sortByDesc('days_past_due');
        if (!empty($filters['aging_bucket'])) {
            $detailed = $detailed->where('bucket', $filters['aging_bucket']);
        }

        return [
            'buckets' => $buckets,
            'detailed' => $detailed->values()->take(50),
        ];
    }

    private function calculateOutstandingAr(array $filters = []): float
    {
        $resultQuery = DB::table('sales_invoices as si')
            ->leftJoin('sales_receipt_allocations as sra', 'sra.invoice_id', '=', 'si.id')
            ->select(
                'si.id',
                'si.total_amount',
                DB::raw('COALESCE(SUM(sra.amount), 0) as paid_amount')
            )
            ->where('si.closure_status', 'open')
            ->where('si.status', 'posted');

        if (!empty($filters['customer_id'])) {
            $resultQuery->where('si.business_partner_id', (int) $filters['customer_id']);
        }
        if (!empty($filters['date_from'])) {
            $resultQuery->whereDate('si.date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $resultQuery->whereDate('si.date', '<=', $filters['date_to']);
        }

        $result = $resultQuery->groupBy('si.id', 'si.total_amount')->get();

        return (float) $result->sum(function ($invoice) {
            return max(0, (float) $invoice->total_amount - (float) $invoice->paid_amount);
        });
    }

    private function buildSalesOrderStats(): array
    {
        $total = SalesOrder::count();
        $draft = SalesOrder::where('status', 'draft')->count();
        $approved = SalesOrder::where('status', 'approved')->count();
        $closed = SalesOrder::where('status', 'closed')->count();
        $open = SalesOrder::where('closure_status', 'open')
            ->whereNotIn('status', ['draft', 'cancelled', 'closed'])
            ->count();

        $totalValue = SalesOrder::where('closure_status', 'open')
            ->sum('total_amount');

        return [
            'total' => $total,
            'draft' => $draft,
            'approved' => $approved,
            'closed' => $closed,
            'open' => $open,
            'total_value' => (float) $totalValue,
        ];
    }

    private function buildSalesInvoiceStats(array $filters = []): array
    {
        $query = SalesInvoice::query();
        if (!empty($filters['customer_id'])) {
            $query->where('business_partner_id', (int) $filters['customer_id']);
        }
        if (!empty($filters['date_from'])) {
            $query->whereDate('date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('date', '<=', $filters['date_to']);
        }

        $total = (clone $query)->count();
        $draft = (clone $query)->where('status', 'draft')->count();
        $posted = (clone $query)->where('status', 'posted')->count();
        $open = (clone $query)->where('closure_status', 'open')->where('status', 'posted')->count();

        $totalAmount = (clone $query)->where('status', 'posted')->sum('total_amount');
        $outstandingAmount = $this->calculateOutstandingAr($filters);

        return [
            'total' => $total,
            'draft' => $draft,
            'posted' => $posted,
            'open' => $open,
            'total_amount' => (float) $totalAmount,
            'outstanding_amount' => (float) $outstandingAmount,
        ];
    }

    private function buildDeliveryOrderStats(): array
    {
        $total = DeliveryOrder::count();
        $pending = DeliveryOrder::whereIn('status', ['draft', 'picking', 'packed', 'ready', 'in_transit'])->count();
        $completed = DeliveryOrder::where('status', 'completed')->count();
        $delivered = DeliveryOrder::where('status', 'delivered')->count();

        return [
            'total' => $total,
            'pending' => $pending,
            'delivered' => $delivered,
            'completed' => $completed,
        ];
    }

    private function buildCustomerStats(array $filters = []): array
    {
        $topCustomersQuery = DB::table('sales_invoices as si')
            ->join('business_partners as bp', 'bp.id', '=', 'si.business_partner_id')
            ->leftJoin('sales_receipt_allocations as sra', 'sra.invoice_id', '=', 'si.id')
            ->select(
                'bp.id',
                'bp.name',
                DB::raw('COUNT(DISTINCT si.id) as invoice_count'),
                DB::raw('SUM(si.total_amount) as total_amount'),
                DB::raw('SUM(COALESCE(sra.amount, 0)) as paid_amount'),
                DB::raw('SUM(si.total_amount - COALESCE(sra.amount, 0)) as outstanding_amount')
            )
            ->where('si.status', 'posted')
            ->where('si.closure_status', 'open');

        if (!empty($filters['customer_id'])) {
            $topCustomersQuery->where('si.business_partner_id', (int) $filters['customer_id']);
        }
        if (!empty($filters['date_from'])) {
            $topCustomersQuery->whereDate('si.date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $topCustomersQuery->whereDate('si.date', '<=', $filters['date_to']);
        }

        $topCustomers = $topCustomersQuery
            ->groupBy('bp.id', 'bp.name')
            ->orderByDesc('outstanding_amount')
            ->limit(10)
            ->get()
            ->map(function ($customer) {
                return [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'invoice_count' => (int) $customer->invoice_count,
                    'total_amount' => (float) $customer->total_amount,
                    'paid_amount' => (float) $customer->paid_amount,
                    'outstanding_amount' => (float) $customer->outstanding_amount,
                ];
            });

        return [
            'top_customers' => $topCustomers,
        ];
    }

    private function getRecentInvoices(array $filters = []): Collection
    {
        $today = Carbon::today();

        $query = DB::table('sales_invoices as si')
            ->leftJoin('sales_receipt_allocations as sra', 'sra.invoice_id', '=', 'si.id')
            ->leftJoin('business_partners as bp', 'bp.id', '=', 'si.business_partner_id')
            ->select(
                'si.id',
                'si.invoice_no',
                'si.date',
                'si.due_date',
                'si.total_amount',
                'si.status',
                'si.closure_status',
                'bp.name as customer_name',
                DB::raw('COALESCE(SUM(sra.amount), 0) as paid_amount'),
                DB::raw('(si.total_amount - COALESCE(SUM(sra.amount), 0)) as outstanding_amount')
            )
            ->where('si.status', 'posted');

        if (!empty($filters['customer_id'])) {
            $query->where('si.business_partner_id', (int) $filters['customer_id']);
        }
        if (!empty($filters['date_from'])) {
            $query->whereDate('si.date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('si.date', '<=', $filters['date_to']);
        }

        return $query
            ->groupBy('si.id', 'si.invoice_no', 'si.date', 'si.due_date', 'si.total_amount', 'si.status', 'si.closure_status', 'bp.name')
            ->orderByDesc('si.date')
            ->limit(20)
            ->get()
            ->map(function ($invoice) use ($today) {
                $dueDate = $invoice->due_date
                    ? Carbon::parse($invoice->due_date)
                    : Carbon::parse($invoice->date)->addDays(30);
                $daysPastDue = $dueDate->diffInDays($today, false);
                $daysOverdue = $daysPastDue > 0 ? $daysPastDue : 0;

                $bucket = 'current';
                if ($daysOverdue > 0) {
                    if ($daysOverdue <= 30) {
                        $bucket = '1_30';
                    } elseif ($daysOverdue <= 60) {
                        $bucket = '31_60';
                    } elseif ($daysOverdue <= 90) {
                        $bucket = '61_90';
                    } else {
                        $bucket = '90_plus';
                    }
                }

                return [
                    'id' => $invoice->id,
                    'invoice_no' => $invoice->invoice_no,
                    'date' => $invoice->date,
                    'due_date' => $invoice->due_date,
                    'total_amount' => (float) $invoice->total_amount,
                    'paid_amount' => (float) $invoice->paid_amount,
                    'outstanding_amount' => (float) $invoice->outstanding_amount,
                    'status' => $invoice->status,
                    'closure_status' => $invoice->closure_status,
                    'customer_name' => $invoice->customer_name,
                    'days_overdue' => $daysOverdue,
                    'aging_bucket' => $bucket,
                ];
            });
    }
}

