<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Accounting\SalesInvoice;
use App\Models\Accounting\SalesInvoiceLine;
use App\Services\Accounting\PostingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesInvoiceController extends Controller
{
    public function __construct(private PostingService $posting)
    {
        $this->middleware(['auth']);
        $this->middleware('permission:ar.invoices.view')->only(['index', 'show']);
        $this->middleware('permission:ar.invoices.create')->only(['create', 'store']);
        $this->middleware('permission:ar.invoices.post')->only(['post']);
    }

    public function index()
    {
        $query = SalesInvoice::query();
        if (request('status')) {
            $query->where('status', request('status'));
        }
        if (request('from')) {
            $query->whereDate('date', '>=', request('from'));
        }
        if (request('to')) {
            $query->whereDate('date', '<=', request('to'));
        }
        if (request('q')) {
            $q = request('q');
            $query->where(function ($w) use ($q) {
                $w->where('invoice_no', 'like', "%$q%")
                    ->orWhere('description', 'like', "%$q%")
                    ->orWhereIn('customer_id', function ($sub) use ($q) {
                        $sub->from('customers')->select('id')->where('name', 'like', "%$q%");
                    });
            });
        }

        if (request('export') === 'csv') {
            $rows = $query->orderByDesc('date')->orderByDesc('id')->get(['date', 'invoice_no', 'customer_id', 'total_amount', 'status']);
            $csv = "date,invoice_no,customer,total,status\n";
            foreach ($rows as $r) {
                $name = \Illuminate\Support\Facades\DB::table('customers')->where('id', $r->customer_id)->value('name');
                $csv .= sprintf("%s,%s,%s,%.2f,%s\n", $r->date, $r->invoice_no, str_replace(',', ' ', (string) $name), $r->total_amount, $r->status);
            }
            return response($csv, 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="sales-invoices.csv"']);
        }

        $invoices = $query->orderByDesc('date')->orderByDesc('id')->paginate(20)->appends(request()->query());
        return view('sales_invoices.index', compact('invoices'));
    }

    public function create()
    {
        $accounts = DB::table('accounts')->where('is_postable', 1)->orderBy('code')->get();
        $customers = DB::table('customers')->orderBy('name')->get();
        $taxCodes = DB::table('tax_codes')->orderBy('code')->get();
        return view('sales_invoices.create', compact('accounts', 'customers', 'taxCodes'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'description' => ['nullable', 'string', 'max:255'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.account_id' => ['required', 'integer', 'exists:accounts,id'],
            'lines.*.description' => ['nullable', 'string', 'max:255'],
            'lines.*.qty' => ['required', 'numeric', 'min:0.01'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.tax_code_id' => ['nullable', 'integer', 'exists:tax_codes,id'],
            'lines.*.project_id' => ['nullable', 'integer'],
            'lines.*.fund_id' => ['nullable', 'integer'],
            'lines.*.dept_id' => ['nullable', 'integer'],
        ]);

        return DB::transaction(function () use ($data) {
            $invoice = SalesInvoice::create([
                'invoice_no' => null,
                'date' => $data['date'],
                'customer_id' => $data['customer_id'],
                'description' => $data['description'] ?? null,
                'status' => 'draft',
                'total_amount' => 0,
            ]);

            // Generate human-readable number
            $ym = date('Ym', strtotime($data['date']));
            $invoice->update(['invoice_no' => sprintf('SI-%s-%06d', $ym, $invoice->id)]);

            $total = 0;
            foreach ($data['lines'] as $l) {
                $amount = (float) $l['qty'] * (float) $l['unit_price'];
                $total += $amount;
                SalesInvoiceLine::create([
                    'invoice_id' => $invoice->id,
                    'account_id' => $l['account_id'],
                    'description' => $l['description'] ?? null,
                    'qty' => (float) $l['qty'],
                    'unit_price' => (float) $l['unit_price'],
                    'amount' => $amount,
                    'tax_code_id' => $l['tax_code_id'] ?? null,
                    'project_id' => $l['project_id'] ?? null,
                    'fund_id' => $l['fund_id'] ?? null,
                    'dept_id' => $l['dept_id'] ?? null,
                ]);
            }

            $invoice->update(['total_amount' => $total]);
            return redirect()->route('sales-invoices.show', $invoice->id)->with('success', 'Invoice created');
        });
    }

    public function show(int $id)
    {
        $invoice = SalesInvoice::with('lines')->findOrFail($id);
        return view('sales_invoices.show', compact('invoice'));
    }

    public function pdf(int $id)
    {
        $invoice = SalesInvoice::with('lines')->findOrFail($id);
        $pdf = app(\App\Services\PdfService::class)->renderViewToString('sales_invoices.print', [
            'invoice' => $invoice,
        ]);
        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="invoice-' . $id . '.pdf"'
        ]);
    }

    public function queuePdf(int $id)
    {
        $invoice = SalesInvoice::with('lines')->findOrFail($id);
        $path = 'public/pdfs/invoice-' . $invoice->id . '.pdf';
        \App\Jobs\GeneratePdfJob::dispatch('sales_invoices.print', ['invoice' => $invoice], $path);
        $url = \Illuminate\Support\Facades\Storage::url($path);
        return back()->with('success', 'PDF generation started')->with('pdf_url', $url);
    }

    public function post(int $id)
    {
        $invoice = SalesInvoice::with('lines')->findOrFail($id);
        if ($invoice->status === 'posted') {
            return back()->with('success', 'Already posted');
        }

        $arAccountId = (int) DB::table('accounts')->where('code', '1.1.4')->value('id');
        $ppnOutputId = (int) DB::table('accounts')->where('code', '2.1.2')->value('id');

        $revenueTotal = 0.0;
        $ppnTotal = 0.0;
        $lines = [];
        foreach ($invoice->lines as $l) {
            $revenueTotal += (float) $l->amount;
            if (!empty($l->tax_code_id)) {
                $rate = (float) DB::table('tax_codes')->where('id', $l->tax_code_id)->value('rate');
                $ppnTotal += round($l->amount * $rate, 2);
            }
            $lines[] = [
                'account_id' => (int) $l->account_id,
                'debit' => 0,
                'credit' => (float) $l->amount,
                'project_id' => $l->project_id,
                'fund_id' => $l->fund_id,
                'dept_id' => $l->dept_id,
                'memo' => $l->description,
            ];
        }

        if ($ppnTotal > 0) {
            $lines[] = [
                'account_id' => $ppnOutputId,
                'debit' => 0,
                'credit' => $ppnTotal,
                'project_id' => null,
                'fund_id' => null,
                'dept_id' => null,
                'memo' => 'PPN Keluaran',
            ];
        }

        $lines[] = [
            'account_id' => $arAccountId,
            'debit' => $revenueTotal + $ppnTotal,
            'credit' => 0,
            'project_id' => null,
            'fund_id' => null,
            'dept_id' => null,
            'memo' => 'Accounts Receivable',
        ];

        DB::transaction(function () use ($invoice, $lines) {
            $jid = $this->posting->postJournal([
                'date' => $invoice->date->toDateString(),
                'description' => 'Post AR Invoice #' . $invoice->id,
                'source_type' => 'sales_invoice',
                'source_id' => $invoice->id,
                'lines' => $lines,
            ]);

            $invoice->update(['status' => 'posted', 'posted_at' => now()]);
        });

        return back()->with('success', 'Invoice posted');
    }
}
