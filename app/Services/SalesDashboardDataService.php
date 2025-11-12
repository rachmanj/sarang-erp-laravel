<?php

namespace App\Services;

use App\Models\Accounting\SalesInvoice;
use App\Models\Accounting\SalesReceipt;
use App\Models\DeliveryOrder;
use App\Models\SalesOrder;
use App\Models\SalesOrderApproval;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SalesDashboardDataService
{
    private const CACHE_KEY = 'dashboard:data:sales';
    private const CACHE_TTL = 300;

    public function getSalesDashboardData(bool $refresh = false): array
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
                'kpis' => $this->buildSalesKpis(),
                'ar_aging' => $this->buildArAging(),
                'sales_orders' => $this->buildSalesOrderStats(),
                'sales_invoices' => $this->buildSalesInvoiceStats(),
                'delivery_orders' => $this->buildDeliveryOrderStats(),
                'customers' => $this->buildCustomerStats(),
                'recent_invoices' => $this->getRecentInvoices(),
            ];
        });
    }

    private function buildSalesKpis(): array
    {
        $today = Carbon::today();
        $monthStart = $today->copy()->startOfMonth();

        $salesMtd = SalesInvoice::whereBetween('date', [$monthStart, $today])
            ->where('status', 'posted')
            ->sum('total_amount');

        $outstandingAr = $this->calculateOutstandingAr();

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

    private function buildArAging(): array
    {
        $invoices = DB::table('sales_invoices as si')
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
            ->where('si.status', 'posted')
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

        return [
            'buckets' => $buckets,
            'detailed' => collect($detailedAging)->sortByDesc('days_past_due')->values()->take(20),
        ];
    }

    private function calculateOutstandingAr(): float
    {
        $result = DB::table('sales_invoices as si')
            ->leftJoin('sales_receipt_allocations as sra', 'sra.invoice_id', '=', 'si.id')
            ->select(
                'si.id',
                'si.total_amount',
                DB::raw('COALESCE(SUM(sra.amount), 0) as paid_amount')
            )
            ->where('si.closure_status', 'open')
            ->where('si.status', 'posted')
            ->groupBy('si.id', 'si.total_amount')
            ->get();

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

    private function buildSalesInvoiceStats(): array
    {
        $total = SalesInvoice::count();
        $draft = SalesInvoice::where('status', 'draft')->count();
        $posted = SalesInvoice::where('status', 'posted')->count();
        $open = SalesInvoice::where('closure_status', 'open')
            ->where('status', 'posted')
            ->count();

        $totalAmount = SalesInvoice::where('status', 'posted')->sum('total_amount');
        $outstandingAmount = $this->calculateOutstandingAr();

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

    private function buildCustomerStats(): array
    {
        $topCustomers = DB::table('sales_invoices as si')
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
            ->where('si.closure_status', 'open')
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

    private function getRecentInvoices(): Collection
    {
        return DB::table('sales_invoices as si')
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
            ->where('si.status', 'posted')
            ->groupBy('si.id', 'si.invoice_no', 'si.date', 'si.due_date', 'si.total_amount', 'si.status', 'si.closure_status', 'bp.name')
            ->orderByDesc('si.date')
            ->limit(10)
            ->get()
            ->map(function ($invoice) {
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
                ];
            });
    }
}

