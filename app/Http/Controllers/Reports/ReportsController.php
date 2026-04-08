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
        $onlyPosted = ! $request->boolean('include_unposted');
        $export = $request->query('export');
        if ($export === 'csv') {
            $data = $this->service->getTrialBalance($request->query('date'), $onlyPosted);
            $csv = "code,name,type,currencies,debit,credit,balance\n";
            foreach ($data['rows'] as $r) {
                $csv .= sprintf(
                    "%s,%s,%s,%s,%.2f,%.2f,%.2f\n",
                    $r['code'],
                    str_replace(',', ' ', (string) $r['name']),
                    $r['type'],
                    str_replace(',', ' ', (string) ($r['currencies'] ?? 'IDR')),
                    $r['debit'],
                    $r['credit'],
                    $r['balance']
                );
            }
            $csv .= sprintf("TOTAL,,,,%.2f,%.2f,\n", $data['totals']['debit'], $data['totals']['credit']);

            return response($csv, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="trial-balance.csv"',
            ]);
        }
        if ($export === 'pdf') {
            $data = $this->service->getTrialBalance($request->query('date'), $onlyPosted);
            $pdf = app(\App\Services\PdfService::class)->renderViewToString('reports.pdf.trial-balance', $data);

            return response($pdf, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="trial-balance.pdf"',
            ]);
        }
        if ($request->wantsJson()) {
            $data = $this->service->getTrialBalance($request->query('date'), $onlyPosted);

            return response()->json($data);
        }

        return view('reports.trial-balance');
    }

    public function balanceSheet(Request $request)
    {
        $asOf = $request->query('as_of');
        $onlyPosted = ! $request->boolean('include_unposted');
        $hideZero = ! $request->boolean('show_zero');
        $export = $request->query('export');
        $data = $this->service->getBalanceSheet($asOf, $onlyPosted, $hideZero);

        if ($export === 'csv') {
            $csv = "section,code,name,depth,is_parent,amount\n";
            foreach ($data['sections'] as $section) {
                foreach ($section['rows'] as $r) {
                    $csv .= sprintf(
                        "%s,%s,%s,%d,%s,%.2f\n",
                        str_replace(',', ' ', $section['label']),
                        $r['code'],
                        str_replace(',', ' ', (string) $r['name']),
                        (int) ($r['depth'] ?? 0),
                        ! empty($r['is_parent']) ? 'Y' : 'N',
                        $r['amount']
                    );
                }
                $csv .= sprintf(
                    "%s,TOTAL,,0,N,%.2f\n",
                    str_replace(',', ' ', $section['label']),
                    $section['total']
                );
            }
            $csv .= sprintf("Summary,TOTAL ASSETS,,0,N,%.2f\n", $data['totals']['assets']);
            $csv .= sprintf("Summary,TOTAL LIABILITIES,,0,N,%.2f\n", $data['totals']['liabilities']);
            $csv .= sprintf("Summary,TOTAL EQUITY,,0,N,%.2f\n", $data['totals']['equity']);
            $csv .= sprintf("Summary,DIFFERENCE (A - L - E),,0,N,%.2f\n", $data['totals']['difference']);
            $csv .= sprintf("Summary,UNCLOSED P_L CUMULATIVE (income and expense TB),,0,N,%.2f\n", $data['totals']['unclosed_pnl_cumulative']);
            $csv .= sprintf("Summary,CHECK (difference minus unclosed P_L),,0,N,%.2f\n", $data['totals']['difference_vs_unclosed_pnl']);

            return response($csv, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="balance-sheet.csv"',
            ]);
        }
        if ($export === 'pdf') {
            $pdf = app(\App\Services\PdfService::class)->renderViewToString('reports.pdf.balance-sheet', $data);

            return response($pdf, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="balance-sheet.pdf"',
            ]);
        }
        if ($request->wantsJson()) {
            return response()->json($data);
        }

        return view('reports.balance-sheet');
    }

    public function profitLoss(Request $request)
    {
        $onlyPosted = ! $request->boolean('include_unposted');
        $hideZero = ! $request->boolean('show_zero');
        $export = $request->query('export');
        $filters = $request->only(['from', 'to', 'project_id', 'fund_id', 'dept_id']);
        $data = $this->service->getProfitAndLoss($filters, $onlyPosted, $hideZero);

        if ($export === 'csv') {
            $csv = "section,code,name,depth,is_parent,amount\n";
            foreach ($data['sections'] as $section) {
                foreach ($section['rows'] as $r) {
                    $csv .= sprintf(
                        "%s,%s,%s,%d,%s,%.2f\n",
                        str_replace(',', ' ', $section['label']),
                        $r['code'],
                        str_replace(',', ' ', (string) $r['name']),
                        (int) ($r['depth'] ?? 0),
                        ! empty($r['is_parent']) ? 'Y' : 'N',
                        $r['amount']
                    );
                }
                $csv .= sprintf(
                    "%s,TOTAL,,0,N,%.2f\n",
                    str_replace(',', ' ', $section['label']),
                    $section['total']
                );
            }
            $csv .= sprintf("Subtotal,Gross profit,,0,N,%.2f\n", $data['subtotals']['gross_profit']);
            $csv .= sprintf("Subtotal,Operating income,,0,N,%.2f\n", $data['subtotals']['operating_income']);
            $csv .= sprintf("Subtotal,Net income,,0,N,%.2f\n", $data['subtotals']['net_income']);

            return response($csv, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="profit-loss.csv"',
            ]);
        }
        if ($export === 'pdf') {
            $pdf = app(\App\Services\PdfService::class)->renderViewToString('reports.pdf.profit-loss', $data);

            return response($pdf, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="profit-loss.pdf"',
            ]);
        }
        if ($request->wantsJson()) {
            return response()->json($data);
        }

        return view('reports.profit-loss');
    }

    public function cashFlowStatement(Request $request)
    {
        $onlyPosted = ! $request->boolean('include_unposted');
        $export = $request->query('export');
        $filters = $request->only(['from', 'to']);
        $data = $this->service->getCashFlowStatement($filters, $onlyPosted);

        if ($export === 'csv') {
            $csv = "section,line,amount\n";
            foreach (['operating', 'investing', 'financing'] as $block) {
                foreach ($data[$block]['lines'] as $line) {
                    $csv .= sprintf(
                        "%s,%s,%.2f\n",
                        str_replace(',', ' ', $data[$block]['label']),
                        str_replace(',', ' ', $line['label']),
                        $line['amount']
                    );
                }
                $csv .= sprintf("%s,%s,%.2f\n", str_replace(',', ' ', $data[$block]['label']), 'Subtotal', $data[$block]['subtotal']);
            }
            $csv .= sprintf("Summary,Net change (computed),%.2f\n", $data['summary']['net_change_computed']);
            $csv .= sprintf("Summary,Net change (cash 1.1.1.x),%.2f\n", $data['summary']['net_change_cash_accounts']);
            $csv .= sprintf("Summary,Reconciliation difference,%.2f\n", $data['summary']['reconciliation_difference']);

            return response($csv, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="cash-flow-statement.csv"',
            ]);
        }
        if ($export === 'pdf') {
            $pdf = app(\App\Services\PdfService::class)->renderViewToString('reports.pdf.cash-flow-statement', ['data' => $data]);

            return response($pdf, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="cash-flow-statement.pdf"',
            ]);
        }
        if ($request->wantsJson()) {
            return response()->json($data);
        }

        return view('reports.cash-flow-statement');
    }

    public function glDetail(Request $request)
    {
        $onlyPosted = ! $request->boolean('include_unposted');
        $filters = $request->only(['account_id', 'from', 'to', 'project_id', 'fund_id', 'dept_id']);
        $export = $request->query('export');
        if ($export === 'csv') {
            $data = $this->service->getGlDetail($filters, $onlyPosted);
            $csv = "date,journal,account_code,account_name,currency,debit,credit,debit_fc,credit_fc,rate,memo\n";
            foreach ($data['rows'] as $r) {
                $csv .= sprintf(
                    "%s,%s,%s,%s,%s,%.2f,%.2f,%.2f,%.2f,%.6f,%s\n",
                    $r['date'],
                    str_replace(',', ' ', (string) ($r['journal_desc'] ?? '')),
                    $r['account_code'],
                    str_replace(',', ' ', (string) $r['account_name']),
                    $r['currency_code'] ?? 'IDR',
                    $r['debit'],
                    $r['credit'],
                    $r['debit_foreign'],
                    $r['credit_foreign'],
                    $r['exchange_rate'],
                    str_replace(["\n", "\r", ','], ' ', (string) ($r['memo'] ?? ''))
                );
            }

            return response($csv, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="gl-detail.csv"',
            ]);
        }
        if ($export === 'pdf') {
            $data = $this->service->getGlDetail($filters, $onlyPosted);
            $pdf = app(\App\Services\PdfService::class)->renderViewToString('reports.pdf.gl-detail', $data);

            return response($pdf, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="gl-detail.pdf"',
            ]);
        }
        if ($request->wantsJson()) {
            $data = $this->service->getGlDetail($filters, $onlyPosted);

            return response()->json($data);
        }

        return view('reports.gl-detail');
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
                $name = $r['customer_name'] ?? ('#'.$r['customer_id']);
                $csv .= sprintf("%s,%.2f,%.2f,%.2f,%.2f,%.2f\n", str_replace(',', ' ', (string) $name), $r['current'], $r['d31_60'], $r['d61_90'], $r['d91_plus'], $r['total']);
            }

            return response($csv, 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="ar-aging.csv"']);
        }
        if ($export === 'pdf') {
            $data = $this->service->getArAging($asOf, ['overdue_only' => $overdueOnly]);
            $pdf = app(\App\Services\PdfService::class)->renderViewToString('reports.pdf.ar-aging', $data);
            $path = 'public/pdfs/ar-aging-'.($data['as_of'] ?? now()->toDateString()).'-'.time().'.pdf';
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
                $name = $r['vendor_name'] ?? ('#'.$r['vendor_id']);
                $csv .= sprintf("%s,%.2f,%.2f,%.2f,%.2f,%.2f\n", str_replace(',', ' ', (string) $name), $r['current'], $r['d31_60'], $r['d61_90'], $r['d91_plus'], $r['total']);
            }

            return response($csv, 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="ap-aging.csv"']);
        }
        if ($export === 'pdf') {
            $data = $this->service->getApAging($asOf, ['overdue_only' => $overdueOnly]);
            $pdf = app(\App\Services\PdfService::class)->renderViewToString('reports.pdf.ap-aging', $data);
            $path = 'public/pdfs/ap-aging-'.($data['as_of'] ?? now()->toDateString()).'-'.time().'.pdf';
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
        $onlyPosted = ! $request->boolean('include_unposted');
        if ($export === 'csv') {
            $data = $this->service->getCashLedger($filters, $onlyPosted);
            $csv = "date,description,debit,credit,balance\n";
            foreach ($data['rows'] as $r) {
                $csv .= sprintf("%s,%s,%.2f,%.2f,%.2f\n", $r['date'], str_replace(',', ' ', (string) ($r['description'] ?? '')), $r['debit'], $r['credit'], $r['balance']);
            }

            return response($csv, 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="cash-ledger.csv"']);
        }
        if ($export === 'pdf') {
            $data = $this->service->getCashLedger($filters, $onlyPosted);
            $account = \Illuminate\Support\Facades\DB::table('accounts')->where('id', $data['account_id'])->first();
            $pdf = app(\App\Services\PdfService::class)->renderViewToString('reports.pdf.cash-ledger', ['data' => $data, 'account' => $account]);
            $label = $account ? ($account->code) : 'cash';
            $path = 'public/pdfs/cash-ledger-'.$label.'-'.time().'.pdf';
            \Illuminate\Support\Facades\Storage::put($path, $pdf);

            return response($pdf, 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="cash-ledger.pdf"']);
        }
        if ($request->wantsJson()) {
            $data = $this->service->getCashLedger($filters, $onlyPosted);

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
                $csv .= sprintf("%s,%.2f\n", str_replace(',', ' ', (string) ($r['vendor_name'] ?? ('#'.$r['vendor_id']))), $r['withholding_total']);
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
                $csv .= sprintf("%s,%.2f,%.2f,%.2f\n", str_replace(',', ' ', (string) ($r['customer_name'] ?? ('#'.$r['customer_id']))), $r['invoices'], $r['receipts'], $r['balance']);
            }

            return response($csv, 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="ar-balances.csv"']);
        }
        if ($export === 'pdf') {
            $pdf = app(\App\Services\PdfService::class)->renderViewToString('reports.pdf.ar-balances', $data);

            return response($pdf, 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="ar-balances.pdf"']);
        }
        if ($request->wantsJson()) {
            return response()->json($data);
        }

        return view('reports.ar-balances', $data);
    }

    public function apBalances(Request $request)
    {
        $export = $request->query('export');
        $data = $this->service->getApBalances();
        if ($export === 'csv') {
            $csv = "vendor,invoices,payments,balance\n";
            foreach ($data['rows'] as $r) {
                $csv .= sprintf("%s,%.2f,%.2f,%.2f\n", str_replace(',', ' ', (string) ($r['vendor_name'] ?? ('#'.$r['vendor_id']))), $r['invoices'], $r['payments'], $r['balance']);
            }

            return response($csv, 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="ap-balances.csv"']);
        }
        if ($export === 'pdf') {
            $pdf = app(\App\Services\PdfService::class)->renderViewToString('reports.pdf.ap-balances', $data);

            return response($pdf, 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="ap-balances.pdf"']);
        }
        if ($request->wantsJson()) {
            return response()->json($data);
        }

        return view('reports.ap-balances', $data);
    }
}
