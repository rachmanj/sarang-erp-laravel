<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Accounting\PurchaseInvoice;
use App\Models\Accounting\PurchaseInvoiceLine;
use App\Models\PurchaseOrder;
use App\Services\Accounting\PostingService;
use App\Services\DocumentNumberingService;
use App\Services\DocumentClosureService;
use App\Services\PurchaseWorkflowAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class PurchaseInvoiceController extends Controller
{
    public function __construct(
        private PostingService $posting,
        private DocumentNumberingService $documentNumberingService,
        private DocumentClosureService $documentClosureService
    ) {
        $this->middleware(['auth']);
        $this->middleware('permission:ap.invoices.view')->only(['index', 'show']);
        $this->middleware('permission:ap.invoices.create')->only(['create', 'store']);
        $this->middleware('permission:ap.invoices.post')->only(['post']);
    }

    public function index()
    {
        return view('purchase_invoices.index');
    }

    public function create()
    {
        $accounts = DB::table('accounts')->where('is_postable', 1)->orderBy('code')->get();
        $vendors = DB::table('business_partners')->where('partner_type', 'supplier')->orderBy('name')->get();
        $taxCodes = DB::table('tax_codes')->orderBy('code')->get();
        $projects = DB::table('projects')->orderBy('code')->get(['id', 'code', 'name']);
        $departments = DB::table('departments')->orderBy('code')->get(['id', 'code', 'name']);
        return view('purchase_invoices.create', compact('accounts', 'vendors', 'taxCodes', 'projects', 'departments'));
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
            // Log the data being used to create the invoice
            \Log::info('Creating Purchase Invoice with data:', [
                'date' => $data['date'],
                'business_partner_id' => $data['business_partner_id'],
                'purchase_order_id' => $request->input('purchase_order_id'),
                'goods_receipt_id' => $request->input('goods_receipt_id'),
                'description' => $data['description'] ?? null
            ]);

            \Log::info('Creating Purchase Invoice with data:', [
                'date' => $data['date'],
                'business_partner_id' => $data['business_partner_id'] ?? null,
                'purchase_order_id' => $request->input('purchase_order_id'),
                'goods_receipt_id' => $request->input('goods_receipt_id'),
                'description' => $data['description'] ?? null
            ]);

            // Make sure business_partner_id is set
            if (!isset($data['business_partner_id'])) {
                \Log::error('Missing business_partner_id in request data', $data);
                throw new \Exception('Business partner is required');
            }

            // Create invoice data array with all required fields
            $invoiceData = [
                'invoice_no' => null,
                'date' => $data['date'],
                'business_partner_id' => $data['business_partner_id'],
                'purchase_order_id' => $request->input('purchase_order_id'),
                'goods_receipt_id' => $request->input('goods_receipt_id'),
                'description' => $data['description'] ?? null,
                'status' => 'draft',
                'total_amount' => 0,
            ];

            \Log::info('Creating invoice with data:', $invoiceData);

            $invoice = PurchaseInvoice::create($invoiceData);

            $invoiceNo = $this->documentNumberingService->generateNumber('purchase_invoice', $data['date']);
            $invoice->update(['invoice_no' => $invoiceNo]);

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
                    'dept_id' => $l['dept_id'] ?? null,
                ]);
            }

            $termsDays = (int) ($request->input('terms_days') ?? 0);
            $dueDate = $termsDays > 0 ? date('Y-m-d', strtotime($data['date'] . ' +' . $termsDays . ' days')) : null;
            $invoice->update(['total_amount' => $total, 'terms_days' => $termsDays ?: null, 'due_date' => $dueDate]);

            // Log invoice creation in Purchase Order audit trail
            if ($request->input('purchase_order_id')) {
                $po = PurchaseOrder::find($request->input('purchase_order_id'));
                if ($po) {
                    app(PurchaseWorkflowAuditService::class)->logPurchaseInvoiceCreation($po, $invoice->id);
                }
            }

            // Attempt to close related documents if this PI was created from GRPO
            if ($request->input('goods_receipt_id')) {
                try {
                    $this->documentClosureService->closeGoodsReceipt($request->input('goods_receipt_id'), $invoice->id, auth()->id());
                } catch (\Exception $closureException) {
                    // Log closure failure but don't fail the PI creation
                    \Log::warning('Failed to close Goods Receipt after PI creation', [
                        'grpo_id' => $request->input('goods_receipt_id'),
                        'pi_id' => $invoice->id,
                        'error' => $closureException->getMessage()
                    ]);
                }
            }

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

        $apUnInvoiceAccountId = (int) DB::table('accounts')->where('code', '2.1.1.03')->value('id'); // AP UnInvoice
        $apAccountId = (int) DB::table('accounts')->where('code', '2.1.1.01')->value('id'); // Utang Dagang
        $ppnInputId = (int) DB::table('accounts')->where('code', '1.1.6')->value('id');

        $expenseTotal = 0.0;
        $ppnTotal = 0.0;
        $withholdingTotal = 0.0;
        $lines = [];
        foreach ($invoice->lines as $l) {
            $expenseTotal += (float) $l->amount;
            if (!empty($l->tax_code_id)) {
                $tax = DB::table('tax_codes')->where('id', $l->tax_code_id)->first();
                if ($tax) {
                    $rate = (float) $tax->rate;
                    if (str_contains(strtolower((string)$tax->name), 'ppn') || strtolower((string)$tax->type) === 'ppn_input') {
                        $ppnTotal += round($l->amount * $rate, 2);
                    }
                    if (strtolower((string)$tax->type) === 'withholding') {
                        $withholdingTotal += round($l->amount * $rate, 2);
                    }
                }
            }
            // Note: We don't create expense journal lines here anymore
            // The corrected accounting logic uses AP UnInvoice instead
        }

        if ($ppnTotal > 0) {
            $lines[] = [
                'account_id' => $ppnInputId,
                'debit' => $ppnTotal,
                'credit' => 0,
                'project_id' => null,
                'dept_id' => null,
                'memo' => 'PPN Masukan',
            ];
        }

        if ($withholdingTotal > 0) {
            $withholdingPayableId = (int) DB::table('accounts')->where('code', '2.1.3')->value('id');
            if ($withholdingPayableId) {
                $lines[] = [
                    'account_id' => $withholdingPayableId,
                    'debit' => 0,
                    'credit' => $withholdingTotal,
                    'project_id' => null,
                    'dept_id' => null,
                    'memo' => 'Withholding Tax Payable',
                ];
            }
        }

        // Debit AP UnInvoice (reducing un-invoiced liability)
        $lines[] = [
            'account_id' => $apUnInvoiceAccountId,
            'debit' => ($expenseTotal + $ppnTotal) - $withholdingTotal,
            'credit' => 0,
            'project_id' => null,
            'dept_id' => null,
            'memo' => 'Reduce AP UnInvoice',
        ];

        // Credit Utang Dagang (creating proper liability)
        $lines[] = [
            'account_id' => $apAccountId,
            'debit' => 0,
            'credit' => ($expenseTotal + $ppnTotal) - $withholdingTotal,
            'project_id' => null,
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

    public function data(Request $request)
    {
        $q = DB::table('purchase_invoices as pi')
            ->leftJoin('business_partners as v', 'v.id', '=', 'pi.business_partner_id')
            ->select('pi.id', 'pi.date', 'pi.invoice_no', 'pi.business_partner_id', 'v.name as vendor_name', 'pi.total_amount', 'pi.status');

        if ($request->filled('status')) {
            $q->where('pi.status', $request->input('status'));
        }
        if ($request->filled('from')) {
            $q->whereDate('pi.date', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $q->whereDate('pi.date', '<=', $request->input('to'));
        }
        if ($request->filled('q')) {
            $kw = $request->input('q');
            $q->where(function ($w) use ($kw) {
                $w->where('pi.invoice_no', 'like', '%' . $kw . '%')
                    ->orWhere('pi.description', 'like', '%' . $kw . '%')
                    ->orWhere('v.name', 'like', '%' . $kw . '%');
            });
        }

        return DataTables::of($q)
            ->editColumn('total_amount', function ($row) {
                return number_format((float)$row->total_amount, 2);
            })
            ->editColumn('status', function ($row) {
                return strtoupper($row->status);
            })
            ->addColumn('vendor', function ($row) {
                return $row->vendor_name ?: ('#' . $row->business_partner_id);
            })
            ->addColumn('actions', function ($row) {
                $url = route('purchase-invoices.show', $row->id);
                return '<a href="' . $url . '" class="btn btn-xs btn-info">View</a>';
            })
            ->rawColumns(['actions'])
            ->toJson();
    }
}
