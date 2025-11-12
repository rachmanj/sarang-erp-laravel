<?php

namespace App\Services;

use App\Models\Accounting\PurchaseInvoice;
use App\Models\Accounting\PurchasePayment;
use App\Models\GoodsReceiptPO;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderApproval;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PurchaseDashboardDataService
{
    private const CACHE_KEY = 'dashboard:data:purchase';
    private const CACHE_TTL = 300;

    public function getPurchaseDashboardData(bool $refresh = false): array
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
                'kpis' => $this->buildPurchaseKpis(),
                'ap_aging' => $this->buildApAging(),
                'purchase_orders' => $this->buildPurchaseOrderStats(),
                'purchase_invoices' => $this->buildPurchaseInvoiceStats(),
                'goods_receipts' => $this->buildGoodsReceiptStats(),
                'suppliers' => $this->buildSupplierStats(),
                'recent_invoices' => $this->getRecentInvoices(),
            ];
        });
    }

    private function buildPurchaseKpis(): array
    {
        $today = Carbon::today();
        $monthStart = $today->copy()->startOfMonth();

        $purchasesMtd = PurchaseInvoice::whereBetween('date', [$monthStart, $today])
            ->where('status', 'posted')
            ->sum('total_amount');

        $outstandingAp = $this->calculateOutstandingAp();

        $pendingPurchaseApprovals = PurchaseOrderApproval::where('status', 'pending')->count();

        $openPurchaseOrders = PurchaseOrder::where('closure_status', 'open')
            ->whereNotIn('status', ['draft', 'cancelled', 'closed'])
            ->count();

        return [
            'purchases_mtd' => (float) $purchasesMtd,
            'outstanding_ap' => (float) $outstandingAp,
            'pending_approvals' => $pendingPurchaseApprovals,
            'open_purchase_orders' => $openPurchaseOrders,
        ];
    }

    private function buildApAging(): array
    {
        $invoices = DB::table('purchase_invoices as pi')
            ->leftJoin('purchase_payment_allocations as ppa', 'ppa.invoice_id', '=', 'pi.id')
            ->select(
                'pi.id',
                'pi.invoice_no',
                'pi.date',
                'pi.due_date',
                'pi.total_amount',
                'pi.business_partner_id',
                DB::raw('COALESCE(SUM(ppa.amount), 0) as paid_amount'),
                DB::raw('(pi.total_amount - COALESCE(SUM(ppa.amount), 0)) as outstanding_amount')
            )
            ->where('pi.closure_status', 'open')
            ->where('pi.status', 'posted')
            ->groupBy('pi.id', 'pi.invoice_no', 'pi.date', 'pi.due_date', 'pi.total_amount', 'pi.business_partner_id')
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

    private function calculateOutstandingAp(): float
    {
        $result = DB::table('purchase_invoices as pi')
            ->leftJoin('purchase_payment_allocations as ppa', 'ppa.invoice_id', '=', 'pi.id')
            ->select(
                'pi.id',
                'pi.total_amount',
                DB::raw('COALESCE(SUM(ppa.amount), 0) as paid_amount')
            )
            ->where('pi.closure_status', 'open')
            ->where('pi.status', 'posted')
            ->groupBy('pi.id', 'pi.total_amount')
            ->get();

        return (float) $result->sum(function ($invoice) {
            return max(0, (float) $invoice->total_amount - (float) $invoice->paid_amount);
        });
    }

    private function buildPurchaseOrderStats(): array
    {
        $total = PurchaseOrder::count();
        $draft = PurchaseOrder::where('status', 'draft')->count();
        $approved = PurchaseOrder::where('status', 'approved')->count();
        $closed = PurchaseOrder::where('status', 'closed')->count();
        $open = PurchaseOrder::where('closure_status', 'open')
            ->whereNotIn('status', ['draft', 'cancelled', 'closed'])
            ->count();

        $totalValue = PurchaseOrder::where('closure_status', 'open')
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

    private function buildPurchaseInvoiceStats(): array
    {
        $total = PurchaseInvoice::count();
        $draft = PurchaseInvoice::where('status', 'draft')->count();
        $posted = PurchaseInvoice::where('status', 'posted')->count();
        $open = PurchaseInvoice::where('closure_status', 'open')
            ->where('status', 'posted')
            ->count();

        $totalAmount = PurchaseInvoice::where('status', 'posted')->sum('total_amount');
        $outstandingAmount = $this->calculateOutstandingAp();

        return [
            'total' => $total,
            'draft' => $draft,
            'posted' => $posted,
            'open' => $open,
            'total_amount' => (float) $totalAmount,
            'outstanding_amount' => (float) $outstandingAmount,
        ];
    }

    private function buildGoodsReceiptStats(): array
    {
        $total = GoodsReceiptPO::count();
        $pending = GoodsReceiptPO::whereIn('status', ['draft', 'pending'])->count();
        $completed = GoodsReceiptPO::where('status', 'completed')->count();

        return [
            'total' => $total,
            'pending' => $pending,
            'completed' => $completed,
        ];
    }

    private function buildSupplierStats(): array
    {
        $topSuppliers = DB::table('purchase_invoices as pi')
            ->join('business_partners as bp', 'bp.id', '=', 'pi.business_partner_id')
            ->leftJoin('purchase_payment_allocations as ppa', 'ppa.invoice_id', '=', 'pi.id')
            ->select(
                'bp.id',
                'bp.name',
                DB::raw('COUNT(DISTINCT pi.id) as invoice_count'),
                DB::raw('SUM(pi.total_amount) as total_amount'),
                DB::raw('SUM(COALESCE(ppa.amount, 0)) as paid_amount'),
                DB::raw('SUM(pi.total_amount - COALESCE(ppa.amount, 0)) as outstanding_amount')
            )
            ->where('pi.status', 'posted')
            ->groupBy('bp.id', 'bp.name')
            ->orderByDesc('outstanding_amount')
            ->limit(10)
            ->get()
            ->map(function ($supplier) {
                return [
                    'id' => $supplier->id,
                    'name' => $supplier->name,
                    'invoice_count' => (int) $supplier->invoice_count,
                    'total_amount' => (float) $supplier->total_amount,
                    'paid_amount' => (float) $supplier->paid_amount,
                    'outstanding_amount' => (float) $supplier->outstanding_amount,
                ];
            });

        return [
            'top_suppliers' => $topSuppliers,
        ];
    }

    private function getRecentInvoices(): Collection
    {
        return DB::table('purchase_invoices as pi')
            ->leftJoin('purchase_payment_allocations as ppa', 'ppa.invoice_id', '=', 'pi.id')
            ->leftJoin('business_partners as bp', 'bp.id', '=', 'pi.business_partner_id')
            ->select(
                'pi.id',
                'pi.invoice_no',
                'pi.date',
                'pi.due_date',
                'pi.total_amount',
                'pi.status',
                'pi.closure_status',
                'bp.name as supplier_name',
                DB::raw('COALESCE(SUM(ppa.amount), 0) as paid_amount'),
                DB::raw('(pi.total_amount - COALESCE(SUM(ppa.amount), 0)) as outstanding_amount')
            )
            ->where('pi.status', 'posted')
            ->groupBy('pi.id', 'pi.invoice_no', 'pi.date', 'pi.due_date', 'pi.total_amount', 'pi.status', 'pi.closure_status', 'bp.name')
            ->orderByDesc('pi.date')
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
                    'supplier_name' => $invoice->supplier_name,
                ];
            });
    }
}

