<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Accounting\SalesInvoice;
use App\Models\Accounting\SalesInvoiceLine;
use App\Models\SalesOrder;
use App\Models\DeliveryOrder;
use App\Models\SalesQuotation;
use App\Services\Accounting\PostingService;
use App\Services\DocumentNumberingService;
use App\Services\DocumentClosureService;
use App\Services\SalesWorkflowAuditService;
use App\Services\CompanyEntityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class SalesInvoiceController extends Controller
{
    public function __construct(
        private PostingService $posting,
        private DocumentNumberingService $documentNumberingService,
        private DocumentClosureService $documentClosureService,
        private CompanyEntityService $companyEntityService
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

    public function create(Request $request)
    {
        $accounts = DB::table('accounts')->where('is_postable', 1)->orderBy('code')->get();
        $customers = DB::table('business_partners')->where('partner_type', 'customer')->orderBy('name')->get();
        $taxCodes = DB::table('tax_codes')->orderBy('code')->get();
        $projects = DB::table('projects')->orderBy('code')->get(['id', 'code', 'name']);
        $departments = DB::table('departments')->orderBy('code')->get(['id', 'code', 'name']);
        $entities = $this->companyEntityService->getActiveEntities();
        $defaultEntity = $this->companyEntityService->getDefaultEntity();

        $prefill = null;
        $salesQuotation = null;

        if ($request->has('quotation_id')) {
            $salesQuotation = SalesQuotation::with(['lines', 'businessPartner', 'companyEntity'])->findOrFail($request->quotation_id);

            $prefill = [
                'date' => now()->toDateString(),
                'business_partner_id' => $salesQuotation->business_partner_id,
                'company_entity_id' => $salesQuotation->company_entity_id ?? $defaultEntity->id,
                'description' => 'From Quotation ' . ($salesQuotation->quotation_no ?: ('#' . $salesQuotation->id)),
                'lines' => $salesQuotation->lines->map(function ($line) {
                    return [
                        'account_id' => (int)$line->account_id,
                        'description' => $line->description ?? $line->item_name,
                        'qty' => (float)$line->qty,
                        'unit_price' => (float)$line->unit_price,
                        'tax_code_id' => $line->tax_code_id ? (int)$line->tax_code_id : null,
                        'project_id' => null,
                        'dept_id' => null,
                    ];
                }),
            ];
        }

        return view('sales_invoices.create', compact('accounts', 'customers', 'taxCodes', 'projects', 'departments', 'entities', 'defaultEntity', 'prefill', 'salesQuotation'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'business_partner_id' => ['required', 'integer', 'exists:business_partners,id'],
            'company_entity_id' => ['required', 'integer', 'exists:company_entities,id'],
            'is_opening_balance' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string', 'max:255'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.account_id' => ['required', 'integer', 'exists:accounts,id'],
            'lines.*.description' => ['nullable', 'string', 'max:255'],
            'lines.*.qty' => ['required', 'numeric', 'min:0.01'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.tax_code_id' => ['nullable', 'integer', 'exists:tax_codes,id'],
            'lines.*.project_id' => ['nullable', 'integer'],
            'lines.*.dept_id' => ['nullable', 'integer'],
        ]);

        $salesOrder = $request->input('sales_order_id')
            ? SalesOrder::select('id', 'company_entity_id')->find($request->input('sales_order_id'))
            : null;
        $deliveryOrder = $request->input('delivery_order_id')
            ? DeliveryOrder::select('id', 'company_entity_id')->find($request->input('delivery_order_id'))
            : null;
        $salesQuotation = $request->input('sales_quotation_id')
            ? SalesQuotation::select('id', 'company_entity_id')->find($request->input('sales_quotation_id'))
            : null;
        $entity = $this->companyEntityService->resolveFromModel(
            $request->input('company_entity_id'),
            $deliveryOrder ?? $salesOrder ?? $salesQuotation
        );

        return DB::transaction(function () use ($data, $request, $salesOrder, $deliveryOrder, $salesQuotation, $entity) {
            $invoice = SalesInvoice::create([
                'invoice_no' => null,
                'date' => $data['date'],
                'business_partner_id' => $data['business_partner_id'],
                'sales_order_id' => $request->input('sales_order_id'),
                'is_opening_balance' => $request->boolean('is_opening_balance', false),
                'description' => $data['description'] ?? null,
                'reference_no' => $data['reference_no'] ?? null,
                'status' => 'draft',
                'total_amount' => 0,
                'company_entity_id' => $entity->id,
            ]);

            // Store quotation reference if provided
            if ($salesQuotation && $salesQuotation->quotation_no) {
                $quotationRef = 'From Quotation: ' . $salesQuotation->quotation_no;
                $currentDesc = $data['description'] ?? '';
                // Only append if not already in description
                if ($currentDesc && strpos($currentDesc, $quotationRef) === false) {
                    $invoice->update(['description' => $currentDesc . ' (' . $quotationRef . ')']);
                } elseif (!$currentDesc) {
                    $invoice->update(['description' => $quotationRef]);
                }
            }

            // Generate human-readable number
            $invoiceNo = $this->documentNumberingService->generateNumber('sales_invoice', $data['date'], [
                'company_entity_id' => $entity->id,
            ]);
            $invoice->update(['invoice_no' => $invoiceNo]);

            $total = 0;
            foreach ($data['lines'] as $l) {
                $amount = (float) $l['qty'] * (float) $l['unit_price'];
                $total += $amount;
                SalesInvoiceLine::create([
                    'invoice_id' => $invoice->id,
                    'item_code' => $l['item_code'] ?? null,
                    'item_name' => $l['item_name'] ?? null,
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

            // Log invoice creation in Sales Order audit trail
            if ($salesOrder) {
                app(SalesWorkflowAuditService::class)->logSalesInvoiceCreation($salesOrder, $invoice->id);
            }

            // Attempt to close related documents if this SI was created from Delivery Order
            if ($deliveryOrder) {
                try {
                    $this->documentClosureService->closeDeliveryOrder($deliveryOrder->id, $invoice->id, auth()->id());
                } catch (\Exception $closureException) {
                    // Log closure failure but don't fail the SI creation
                    \Log::warning('Failed to close Delivery Order after SI creation', [
                        'do_id' => $request->input('delivery_order_id'),
                        'si_id' => $invoice->id,
                        'error' => $closureException->getMessage()
                    ]);
                }
            }

            return redirect()->route('sales-invoices.show', $invoice->id)->with('success', 'Invoice created');
        });
    }

    public function show(int $id)
    {
        $invoice = SalesInvoice::with([
            'businessPartner.primaryAddress',
            'companyEntity',
            'salesOrder',
            'lines.account',
            'lines.taxCode',
        ])->findOrFail($id);
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

        $arUnInvoiceAccountId = (int) DB::table('accounts')->where('code', '1.1.2.04')->value('id'); // AR UnInvoice
        $arAccountId = (int) DB::table('accounts')->where('code', '1.1.2.01')->value('id'); // Piutang Dagang
        $ppnOutputId = (int) DB::table('accounts')->where('code', '2.1.2')->value('id');

        $revenueTotal = 0.0;
        $ppnTotal = 0.0;
        $lines = [];

        // Calculate totals first
        foreach ($invoice->lines as $l) {
            $revenueTotal += (float) $l->amount;
            if (!empty($l->tax_code_id)) {
                $rate = (float) DB::table('tax_codes')->where('id', $l->tax_code_id)->value('rate');
                $ppnTotal += round($l->amount * $rate, 2);
            }
        }

        // Check if this is an opening balance invoice
        // Opening balance invoices post directly to AR and Retained Earnings Opening Balance
        $isOpeningBalance = $invoice->is_opening_balance;

        if ($isOpeningBalance) {
            // Opening balance invoice: Post directly to AR and Retained Earnings Opening Balance (3.3.1)
            $retainedEarningsAccountId = (int) DB::table('accounts')->where('code', '3.3.1')->value('id'); // Saldo Awal Laba Ditahan
            
            if (!$retainedEarningsAccountId) {
                throw new \Exception('Retained Earnings Opening Balance account (3.3.1) not found. Please ensure this account exists in the chart of accounts.');
            }

            // Debit AR Account (creating accounts receivable)
            $lines[] = [
                'account_id' => $arAccountId,
                'debit' => $revenueTotal + $ppnTotal,
                'credit' => 0,
                'project_id' => null,
                'dept_id' => null,
                'memo' => 'Accounts Receivable - Opening Balance',
            ];

            // Credit Retained Earnings Opening Balance (3.3.1)
            $lines[] = [
                'account_id' => $retainedEarningsAccountId,
                'debit' => 0,
                'credit' => $revenueTotal,
                'project_id' => null,
                'dept_id' => null,
                'memo' => 'Saldo Awal Laba Ditahan - Opening Balance',
            ];

            // Credit VAT Output Account (recognizing VAT liability)
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
        } else {
            // Regular invoice: Post using AR UnInvoice flow
            // Debit AR UnInvoice (reducing un-invoiced receivable)
            $lines[] = [
                'account_id' => $arUnInvoiceAccountId,
                'debit' => $revenueTotal + $ppnTotal,
                'credit' => 0,
                'project_id' => null,
                'dept_id' => null,
                'memo' => 'Reduce AR UnInvoice',
            ];

            // Note: Revenue recognition is handled by Delivery Order, not Sales Invoice
            // Sales Invoice only handles receivable transition and VAT

            // Credit VAT Output Account (recognizing VAT liability)
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

            // Credit AR Account (creating accounts receivable for revenue only)
            $lines[] = [
                'account_id' => $arAccountId,
                'debit' => 0,
                'credit' => $revenueTotal,
                'project_id' => null,
                'dept_id' => null,
                'memo' => 'Accounts Receivable',
            ];
        }

        DB::transaction(function () use ($invoice, $lines) {
            $jid = $this->posting->postJournal([
                'date' => $invoice->date->toDateString(),
                'description' => $invoice->is_opening_balance 
                    ? 'Post AR Invoice (Opening Balance) #' . $invoice->invoice_no
                    : 'Post AR Invoice #' . $invoice->invoice_no,
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
