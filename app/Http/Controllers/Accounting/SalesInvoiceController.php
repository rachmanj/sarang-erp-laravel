<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Accounting\SalesInvoice;
use App\Models\Accounting\SalesInvoiceLine;
use App\Services\Accounting\PostingService;
use App\Services\DocumentNumberingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class SalesInvoiceController extends Controller
{
    public function __construct(
        private PostingService $posting,
        private DocumentNumberingService $documentNumberingService
    ) {
        $this->middleware(['auth']);
        $this->middleware('permission:ar.invoices.view')->only(['index', 'show']);
        $this->middleware('permission:ar.invoices.create')->only(['create', 'store']);
        $this->middleware('permission:ar.invoices.post')->only(['post']);
    }

    public function index()
    {
        return view('sales_invoices.index');
    }

    public function create()
    {
        $accounts = DB::table('accounts')->where('is_postable', 1)->orderBy('code')->get();
        $customers = DB::table('business_partners')->where('partner_type', 'customer')->orderBy('name')->get();
        $taxCodes = DB::table('tax_codes')->orderBy('code')->get();
        $projects = DB::table('projects')->orderBy('code')->get(['id', 'code', 'name']);
        $departments = DB::table('departments')->orderBy('code')->get(['id', 'code', 'name']);
        return view('sales_invoices.create', compact('accounts', 'customers', 'taxCodes', 'projects', 'departments'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'business_partner_id' => ['required', 'integer', 'exists:business_partners,id'],
            'description' => ['nullable', 'string', 'max:255'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.account_id' => ['required', 'integer', 'exists:accounts,id'],
            'lines.*.description' => ['nullable', 'string', 'max:255'],
            'lines.*.qty' => ['required', 'numeric', 'min:0.01'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.tax_code_id' => ['nullable', 'integer', 'exists:tax_codes,id'],
            'lines.*.project_id' => ['nullable', 'integer'],
            'lines.*.dept_id' => ['nullable', 'integer'],
        ]);

        return DB::transaction(function () use ($data, $request) {
            $invoice = SalesInvoice::create([
                'invoice_no' => null,
                'date' => $data['date'],
                'business_partner_id' => $data['business_partner_id'],
                'sales_order_id' => $request->input('sales_order_id'),
                'description' => $data['description'] ?? null,
                'status' => 'draft',
                'total_amount' => 0,
            ]);

            // Generate human-readable number
            $invoiceNo = $this->documentNumberingService->generateNumber('sales_invoice', $data['date']);
            $invoice->update(['invoice_no' => $invoiceNo]);

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
                    'dept_id' => $l['dept_id'] ?? null,
                ]);
            }

            $termsDays = (int) ($request->input('terms_days') ?? 0);
            $dueDate = $termsDays > 0 ? date('Y-m-d', strtotime($data['date'] . ' +' . $termsDays . ' days')) : null;
            $invoice->update(['total_amount' => $total, 'terms_days' => $termsDays ?: null, 'due_date' => $dueDate]);
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
                'dept_id' => null,
                'memo' => 'PPN Keluaran',
            ];
        }

        $lines[] = [
            'account_id' => $arAccountId,
            'debit' => $revenueTotal + $ppnTotal,
            'credit' => 0,
            'project_id' => null,
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

    public function data(Request $request)
    {
        $q = DB::table('sales_invoices as si')
            ->leftJoin('business_partners as c', 'c.id', '=', 'si.business_partner_id')
            ->select('si.id', 'si.date', 'si.invoice_no', 'si.business_partner_id', 'c.name as customer_name', 'si.total_amount', 'si.status');

        if ($request->filled('status')) {
            $q->where('si.status', $request->input('status'));
        }
        if ($request->filled('from')) {
            $q->whereDate('si.date', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $q->whereDate('si.date', '<=', $request->input('to'));
        }
        if ($request->filled('q')) {
            $kw = $request->input('q');
            $q->where(function ($w) use ($kw) {
                $w->where('si.invoice_no', 'like', '%' . $kw . '%')
                    ->orWhere('si.description', 'like', '%' . $kw . '%')
                    ->orWhere('c.name', 'like', '%' . $kw . '%');
            });
        }

        return DataTables::of($q)
            ->editColumn('total_amount', function ($row) {
                return number_format((float)$row->total_amount, 2);
            })
            ->editColumn('status', function ($row) {
                return strtoupper($row->status);
            })
            ->addColumn('customer', function ($row) {
                return $row->customer_name ?: ('#' . $row->business_partner_id);
            })
            ->addColumn('actions', function ($row) {
                $url = route('sales-invoices.show', $row->id);
                return '<a href="' . $url . '" class="btn btn-xs btn-info">View</a>';
            })
            ->rawColumns(['actions'])
            ->toJson();
    }
}
