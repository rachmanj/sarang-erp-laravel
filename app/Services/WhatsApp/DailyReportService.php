<?php

namespace App\Services\WhatsApp;

use App\Models\Accounting\SalesInvoice;
use App\Models\PurchaseOrder;
use App\Services\Reports\ReportService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DailyReportService
{
    public function __construct(
        private ReportService $reportService
    ) {}

    /**
     * @return array{
     *     sales_today: float,
     *     cash_bank_balance: float,
     *     po_pending_count: int,
     *     overdue_invoice_count: int
     * }
     */
    public function buildDailyReport(): array
    {
        $today = Carbon::today();
        $asOf = $today->toDateString();
        $cashPrefixes = config('cash_flow.account_prefixes.cash_and_bank', ['1.1.1']);

        return [
            'sales_today' => (float) SalesInvoice::query()
                ->where('status', 'posted')
                ->whereDate('date', $today)
                ->sum('total_amount'),
            'cash_bank_balance' => $this->reportService->balanceSheetDisplayTotalForPrefixes(
                $asOf,
                $cashPrefixes,
                true
            ),
            'po_pending_count' => PurchaseOrder::query()->pending()->count(),
            'overdue_invoice_count' => $this->countOverdueInvoices($today),
        ];
    }

    private function countOverdueInvoices(Carbon $today): int
    {
        $invoices = DB::table('sales_invoices as si')
            ->leftJoin('sales_receipt_allocations as sra', 'sra.invoice_id', '=', 'si.id')
            ->select(
                'si.id',
                'si.total_amount',
                DB::raw('COALESCE(SUM(sra.amount), 0) as paid_amount')
            )
            ->where('si.closure_status', 'open')
            ->where('si.status', 'posted')
            ->whereDate('si.due_date', '<', $today)
            ->groupBy('si.id', 'si.total_amount')
            ->get();

        return $invoices->filter(function ($invoice) {
            return max(0, (float) $invoice->total_amount - (float) $invoice->paid_amount) > 0;
        })->count();
    }
}
