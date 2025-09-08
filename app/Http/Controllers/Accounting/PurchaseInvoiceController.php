<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Accounting\PurchaseInvoice;
use App\Models\Accounting\PurchaseInvoiceLine;
use App\Services\Accounting\PostingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseInvoiceController extends Controller
{
    public function __construct(private PostingService $posting)
    {
        $this->middleware(['auth']);
        $this->middleware('permission:ap.invoices.view')->only(['index', 'show']);
        $this->middleware('permission:ap.invoices.create')->only(['create', 'store']);
        $this->middleware('permission:ap.invoices.post')->only(['post']);
    }

    public function index()
    {
        $query = PurchaseInvoice::query();
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
                    ->orWhereIn('vendor_id', function ($sub) use ($q) {
                        $sub->from('vendors')->select('id')->where('name', 'like', "%$q%");
                    });
            });
        }

        if (request('export') === 'csv') {
            $rows = $query->orderByDesc('date')->orderByDesc('id')->get(['date', 'invoice_no', 'vendor_id', 'total_amount', 'status']);
            $csv = "date,invoice_no,vendor,total,status\n";
            foreach ($rows as $r) {
                $name = \Illuminate\Support\Facades\DB::table('vendors')->where('id', $r->vendor_id)->value('name');
                $csv .= sprintf("%s,%s,%s,%.2f,%s\n", $r->date, $r->invoice_no, str_replace(',', ' ', (string) $name), $r->total_amount, $r->status);
            }
            return response($csv, 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="purchase-invoices.csv"']);
        }

        $invoices = $query->orderByDesc('date')->orderByDesc('id')->paginate(20)->appends(request()->query());
        return view('purchase_invoices.index', compact('invoices'));
    }

    public function create()
    {
        $accounts = DB::table('accounts')->where('is_postable', 1)->orderBy('code')->get();
        $vendors = DB::table('vendors')->orderBy('name')->get();
        $taxCodes = DB::table('tax_codes')->orderBy('code')->get();
        return view('purchase_invoices.create', compact('accounts', 'vendors', 'taxCodes'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'vendor_id' => ['required', 'integer', 'exists:vendors,id'],
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
            $invoice = PurchaseInvoice::create([
                'invoice_no' => null,
                'date' => $data['date'],
                'vendor_id' => $data['vendor_id'],
                'description' => $data['description'] ?? null,
                'status' => 'draft',
                'total_amount' => 0,
            ]);

            $ym = date('Ym', strtotime($data['date']));
            $invoice->update(['invoice_no' => sprintf('PI-%s-%06d', $ym, $invoice->id)]);

            $total = 0;
            foreach ($data['lines'] as $l) {
                $amount = (float) $l['qty'] * (float) $l['unit_price'];
                $total += $amount;
                PurchaseInvoiceLine::create([
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
            return redirect()->route('purchase-invoices.show', $invoice->id)->with('success', 'Purchase invoice created');
        });
    }

    public function show(int $id)
    {
        $invoice = PurchaseInvoice::with('lines')->findOrFail($id);
        return view('purchase_invoices.show', compact('invoice'));
    }

    public function post(int $id)
    {
        $invoice = PurchaseInvoice::with('lines')->findOrFail($id);
        if ($invoice->status === 'posted') {
            return back()->with('success', 'Already posted');
        }

        $apAccountId = (int) DB::table('accounts')->where('code', '2.1.1')->value('id');
        $ppnInputId = (int) DB::table('accounts')->where('code', '1.1.6')->value('id');

        $expenseTotal = 0.0;
        $ppnTotal = 0.0;
        $lines = [];
        foreach ($invoice->lines as $l) {
            $expenseTotal += (float) $l->amount;
            if (!empty($l->tax_code_id)) {
                $rate = (float) DB::table('tax_codes')->where('id', $l->tax_code_id)->value('rate');
                $ppnTotal += round($l->amount * $rate, 2);
            }
            $lines[] = [
                'account_id' => (int) $l->account_id,
                'debit' => (float) $l->amount,
                'credit' => 0,
                'project_id' => $l->project_id,
                'fund_id' => $l->fund_id,
                'dept_id' => $l->dept_id,
                'memo' => $l->description,
            ];
        }

        if ($ppnTotal > 0) {
            $lines[] = [
                'account_id' => $ppnInputId,
                'debit' => $ppnTotal,
                'credit' => 0,
                'project_id' => null,
                'fund_id' => null,
                'dept_id' => null,
                'memo' => 'PPN Masukan',
            ];
        }

        $lines[] = [
            'account_id' => $apAccountId,
            'debit' => 0,
            'credit' => $expenseTotal + $ppnTotal,
            'project_id' => null,
            'fund_id' => null,
            'dept_id' => null,
            'memo' => 'Accounts Payable',
        ];

        DB::transaction(function () use ($invoice, $lines) {
            $jid = $this->posting->postJournal([
                'date' => $invoice->date->toDateString(),
                'description' => 'Post AP Invoice #' . $invoice->id,
                'source_type' => 'purchase_invoice',
                'source_id' => $invoice->id,
                'lines' => $lines,
            ]);

            $invoice->update(['status' => 'posted', 'posted_at' => now()]);
        });

        return back()->with('success', 'Purchase invoice posted');
    }

    public function print(int $id)
    {
        $invoice = PurchaseInvoice::with('lines')->findOrFail($id);
        return view('purchase_invoices.print', compact('invoice'));
    }

    public function pdf(int $id)
    {
        $invoice = PurchaseInvoice::with('lines')->findOrFail($id);
        $pdf = app(\App\Services\PdfService::class)->renderViewToString('purchase_invoices.print', [
            'invoice' => $invoice,
        ]);
        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="purchase-invoice-' . $id . '.pdf"'
        ]);
    }

    public function queuePdf(int $id)
    {
        $invoice = PurchaseInvoice::with('lines')->findOrFail($id);
        $path = 'public/pdfs/purchase-invoice-' . $invoice->id . '.pdf';
        \App\Jobs\GeneratePdfJob::dispatch('purchase_invoices.print', ['invoice' => $invoice], $path);
        $url = \Illuminate\Support\Facades\Storage::url($path);
        return back()->with('success', 'PDF generation started')->with('pdf_url', $url);
    }
}
