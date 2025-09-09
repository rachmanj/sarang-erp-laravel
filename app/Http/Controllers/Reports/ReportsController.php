<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Services\Reports\ReportService;
use Illuminate\Http\Request;

class ReportsController extends Controller
{
    public function __construct(private ReportService $service)
    {
        $this->middleware('permission:reports.view');
    }

    public function trialBalance(Request $request)
    {
        if ($request->wantsJson()) {
            $data = $this->service->getTrialBalance($request->query('date'));
            return response()->json($data);
        }
        return view('reports.trial-balance');
    }

    public function glDetail(Request $request)
    {
        if ($request->wantsJson()) {
            $filters = $request->only(['account_id', 'from', 'to', 'project_id', 'fund_id', 'dept_id']);
            $data = $this->service->getGlDetail($filters);
            return response()->json($data);
        }
        $projects = \Illuminate\Support\Facades\DB::table('projects')->orderBy('code')->get(['id', 'code', 'name']);
        $funds = \Illuminate\Support\Facades\DB::table('funds')->orderBy('code')->get(['id', 'code', 'name']);
        $departments = \Illuminate\Support\Facades\DB::table('departments')->orderBy('code')->get(['id', 'code', 'name']);
        return view('reports.gl-detail', compact('projects', 'funds', 'departments'));
    }

    public function arAging(Request $request)
    {
        $asOf = $request->query('as_of');
        $export = $request->query('export');
        $overdueOnly = (bool) $request->boolean('overdue');
        if ($export === 'csv') {
            $data = $this->service->getArAging($asOf, ['overdue_only' => $overdueOnly]);
            $csv = "customer,current,31-60,61-90,91+,total\n";
            foreach ($data['rows'] as $r) {
                $name = $r['customer_name'] ?? ('#' . $r['customer_id']);
                $csv .= sprintf("%s,%.2f,%.2f,%.2f,%.2f,%.2f\n", str_replace(',', ' ', (string)$name), $r['current'], $r['d31_60'], $r['d61_90'], $r['d91_plus'], $r['total']);
            }
            return response($csv, 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="ar-aging.csv"']);
        }
        if ($export === 'pdf') {
            $data = $this->service->getArAging($asOf, ['overdue_only' => $overdueOnly]);
            $pdf = app(\App\Services\PdfService::class)->renderViewToString('reports.pdf.ar-aging', $data);
            $path = 'public/pdfs/ar-aging-' . ($data['as_of'] ?? now()->toDateString()) . '-' . time() . '.pdf';
            \Illuminate\Support\Facades\Storage::put($path, $pdf);
            return response($pdf, 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="ar-aging.pdf"']);
        }
        if ($request->wantsJson()) {
            $data = $this->service->getArAging($asOf, ['overdue_only' => $overdueOnly]);
            return response()->json($data);
        }
        return view('reports.ar-aging');
    }

    public function apAging(Request $request)
    {
        $asOf = $request->query('as_of');
        $export = $request->query('export');
        $overdueOnly = (bool) $request->boolean('overdue');
        if ($export === 'csv') {
            $data = $this->service->getApAging($asOf, ['overdue_only' => $overdueOnly]);
            $csv = "vendor,current,31-60,61-90,91+,total\n";
            foreach ($data['rows'] as $r) {
                $name = $r['vendor_name'] ?? ('#' . $r['vendor_id']);
                $csv .= sprintf("%s,%.2f,%.2f,%.2f,%.2f,%.2f\n", str_replace(',', ' ', (string)$name), $r['current'], $r['d31_60'], $r['d61_90'], $r['d91_plus'], $r['total']);
            }
            return response($csv, 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="ap-aging.csv"']);
        }
        if ($export === 'pdf') {
            $data = $this->service->getApAging($asOf, ['overdue_only' => $overdueOnly]);
            $pdf = app(\App\Services\PdfService::class)->renderViewToString('reports.pdf.ap-aging', $data);
            $path = 'public/pdfs/ap-aging-' . ($data['as_of'] ?? now()->toDateString()) . '-' . time() . '.pdf';
            \Illuminate\Support\Facades\Storage::put($path, $pdf);
            return response($pdf, 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="ap-aging.pdf"']);
        }
        if ($request->wantsJson()) {
            $data = $this->service->getApAging($asOf, ['overdue_only' => $overdueOnly]);
            return response()->json($data);
        }
        return view('reports.ap-aging');
    }

    public function cashLedger(Request $request)
    {
        $export = $request->query('export');
        $filters = $request->only(['from', 'to', 'account_id']);
        if ($export === 'csv') {
            $data = $this->service->getCashLedger($filters);
            $csv = "date,description,debit,credit,balance\n";
            foreach ($data['rows'] as $r) {
                $csv .= sprintf("%s,%s,%.2f,%.2f,%.2f\n", $r['date'], str_replace(',', ' ', (string)($r['description'] ?? '')), $r['debit'], $r['credit'], $r['balance']);
            }
            return response($csv, 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="cash-ledger.csv"']);
        }
        if ($export === 'pdf') {
            $data = $this->service->getCashLedger($filters);
            $account = \Illuminate\Support\Facades\DB::table('accounts')->where('id', $data['account_id'])->first();
            $pdf = app(\App\Services\PdfService::class)->renderViewToString('reports.pdf.cash-ledger', ['data' => $data, 'account' => $account]);
            $label = $account ? ($account->code) : 'cash';
            $path = 'public/pdfs/cash-ledger-' . $label . '-' . time() . '.pdf';
            \Illuminate\Support\Facades\Storage::put($path, $pdf);
            return response($pdf, 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="cash-ledger.pdf"']);
        }
        if ($request->wantsJson()) {
            $data = $this->service->getCashLedger($filters);
            return response()->json($data);
        }
        return view('reports.cash-ledger');
    }

    public function withholdingRecap(Request $request)
    {
        $filters = $request->only(['from', 'to', 'vendor_id']);
        $export = $request->query('export');
        if ($export === 'csv') {
            $data = $this->service->getWithholdingRecap($filters);
            $csv = "vendor,withholding_total\n";
            foreach ($data['rows'] as $r) {
                $csv .= sprintf("%s,%.2f\n", str_replace(',', ' ', (string)($r['vendor_name'] ?? ('#' . $r['vendor_id']))), $r['withholding_total']);
            }
            return response($csv, 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="withholding-recap.csv"']);
        }
        if ($export === 'pdf') {
            $data = $this->service->getWithholdingRecap($filters);
            $pdf = app(\App\Services\PdfService::class)->renderViewToString('reports.pdf.withholding-recap', $data);
            return response($pdf, 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="withholding-recap.pdf"']);
        }
        if ($request->wantsJson()) {
            return response()->json($this->service->getWithholdingRecap($filters));
        }
        return view('reports.withholding-recap', $this->service->getWithholdingRecap($filters));
    }

    public function arBalances(Request $request)
    {
        $export = $request->query('export');
        $data = $this->service->getArBalances();
        if ($export === 'csv') {
            $csv = "customer,invoices,receipts,balance\n";
            foreach ($data['rows'] as $r) {
                $csv .= sprintf("%s,%.2f,%.2f,%.2f\n", str_replace(',', ' ', (string)($r['customer_name'] ?? ('#' . $r['customer_id']))), $r['invoices'], $r['receipts'], $r['balance']);
            }
            return response($csv, 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="ar-balances.csv"']);
        }
        if ($export === 'pdf') {
            $pdf = app(\App\Services\PdfService::class)->renderViewToString('reports.pdf.ar-balances', $data);
            return response($pdf, 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="ar-balances.pdf"']);
        }
        if ($request->wantsJson()) return response()->json($data);
        return view('reports.ar-balances', $data);
    }

    public function apBalances(Request $request)
    {
        $export = $request->query('export');
        $data = $this->service->getApBalances();
        if ($export === 'csv') {
            $csv = "vendor,invoices,payments,balance\n";
            foreach ($data['rows'] as $r) {
                $csv .= sprintf("%s,%.2f,%.2f,%.2f\n", str_replace(',', ' ', (string)($r['vendor_name'] ?? ('#' . $r['vendor_id']))), $r['invoices'], $r['payments'], $r['balance']);
            }
            return response($csv, 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="ap-balances.csv"']);
        }
        if ($export === 'pdf') {
            $pdf = app(\App\Services\PdfService::class)->renderViewToString('reports.pdf.ap-balances', $data);
            return response($pdf, 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="ap-balances.pdf"']);
        }
        if ($request->wantsJson()) return response()->json($data);
        return view('reports.ap-balances', $data);
    }
}
