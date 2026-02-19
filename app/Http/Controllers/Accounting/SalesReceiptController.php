<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Accounting\SalesReceipt;
use App\Models\Accounting\SalesReceiptLine;
use App\Models\Accounting\SalesInvoice;
use App\Models\SalesOrder;
use App\Services\Accounting\PostingService;
use App\Services\DocumentNumberingService;
use App\Services\DocumentClosureService;
use App\Services\CompanyEntityService;
use App\Services\SalesWorkflowAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class SalesReceiptController extends Controller
{
    public function __construct(
        private PostingService $posting,
        private DocumentNumberingService $documentNumberingService,
        private DocumentClosureService $documentClosureService,
        private CompanyEntityService $companyEntityService
    ) {
        $this->middleware(['auth']);
        $this->middleware('permission:ar.receipts.view')->only(['index', 'show']);
        $this->middleware('permission:ar.receipts.create')->only(['create', 'store', 'getAvailableInvoices', 'previewAllocation']);
        $this->middleware('permission:ar.receipts.post')->only(['post']);
    }

    public function index()
    {
        return view('sales_receipts.index');
    }

    public function create()
    {
        $customers = DB::table('business_partners')->where('partner_type', 'customer')->orderBy('name')->get();
        $accounts = DB::table('accounts')->where('is_postable', 1)->orderBy('code')->get();
        $entities = $this->companyEntityService->getActiveEntities();
        $defaultEntity = $this->companyEntityService->getDefaultEntity();
        return view('sales_receipts.create', compact('customers', 'accounts', 'entities', 'defaultEntity'));
    }

    public function getDocumentNumber(Request $request)
    {
        $entityId = $request->input('company_entity_id');
        $date = $request->input('date', now()->toDateString());

        try {
            if (!$entityId) {
                return response()->json(['error' => 'Company entity is required'], 400);
            }

            $documentNumber = $this->documentNumberingService->previewNumber('sales_receipt', $date, [
                'company_entity_id' => $entityId,
            ]);

            return response()->json(['document_number' => $documentNumber]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error generating document number: ' . $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'business_partner_id' => ['required', 'integer', 'exists:business_partners,id'],
            'company_entity_id' => ['required', 'integer', 'exists:company_entities,id'],
            'description' => ['nullable', 'string', 'max:255'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.account_id' => ['required', 'integer', 'exists:accounts,id'],
            'lines.*.description' => ['nullable', 'string', 'max:255'],
            'lines.*.amount' => ['required', 'numeric', 'min:0.01'],
            'allocations' => ['required', 'array', 'min:1'],
            'allocations.*.invoice_id' => ['required', 'integer', 'exists:sales_invoices,id'],
            'allocations.*.amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        $entity = $this->companyEntityService->getEntity($data['company_entity_id']);

        return DB::transaction(function () use ($data, $entity, $request) {
            $totalReceipt = 0;
            foreach ($data['lines'] as $l) {
                $totalReceipt += (float) $l['amount'];
            }

            $totalAllocation = 0;
            foreach ($data['allocations'] as $alloc) {
                $totalAllocation += (float) $alloc['amount'];
            }

            if (abs($totalReceipt - $totalAllocation) > 0.01) {
                return back()->withErrors(['lines' => 'Receipt total must match allocation total.'])->withInput();
            }

            foreach ($data['allocations'] as $alloc) {
                $invoice = SalesInvoice::findOrFail($alloc['invoice_id']);

                if ($invoice->business_partner_id != $data['business_partner_id']) {
                    return back()->withErrors(['allocations' => 'Selected invoice does not belong to selected customer.'])->withInput();
                }

                if ($invoice->status !== 'posted') {
                    return back()->withErrors(['allocations' => 'Invoice must be posted before receipt allocation.'])->withInput();
                }

                $allocated = DB::table('sales_receipt_allocations')
                    ->where('invoice_id', $invoice->id)
                    ->sum('amount');
                $remaining = (float) $invoice->total_amount - (float) $allocated;
                $allocAmount = (float) $alloc['amount'];

                if ($allocAmount > $remaining + 0.01) {
                    return back()->withErrors(['allocations' => "Allocation amount for invoice {$invoice->invoice_no} exceeds remaining balance."])->withInput();
                }
            }

            $baseCurrency = \App\Models\Currency::getBaseCurrency();
            $currencyId = $baseCurrency ? $baseCurrency->id : 1;

            $receipt = SalesReceipt::create([
                'receipt_no' => null,
                'date' => $data['date'],
                'business_partner_id' => $data['business_partner_id'],
                'company_entity_id' => $entity->id,
                'currency_id' => $currencyId,
                'description' => $data['description'] ?? null,
                'status' => 'draft',
                'total_amount' => $totalReceipt,
            ]);

            $receiptNo = $this->documentNumberingService->generateNumber('sales_receipt', $data['date'], [
                'company_entity_id' => $entity->id,
            ]);
            $receipt->update(['receipt_no' => $receiptNo]);

            foreach ($data['lines'] as $l) {
                SalesReceiptLine::create([
                    'receipt_id' => $receipt->id,
                    'account_id' => $l['account_id'],
                    'description' => $l['description'] ?? null,
                    'amount' => (float) $l['amount'],
                ]);
            }

            foreach ($data['allocations'] as $alloc) {
                DB::table('sales_receipt_allocations')->insert([
                    'receipt_id' => $receipt->id,
                    'invoice_id' => $alloc['invoice_id'],
                    'amount' => (float) $alloc['amount'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Attempt to close related Sales Invoices if fully paid
            try {
                $this->documentClosureService->closeSalesInvoiceByReceipt($receipt->id, auth()->id());
            } catch (\Exception $closureException) {
                // Log closure failure but don't fail the receipt creation
                Log::warning('Failed to close Sales Invoices after receipt creation', [
                    'receipt_id' => $receipt->id,
                    'error' => $closureException->getMessage()
                ]);
            }

            // Log receipt creation in Sales Order audit trail
            // Find Sales Orders through allocated invoices
            $allocatedInvoiceIds = DB::table('sales_receipt_allocations')
                ->where('receipt_id', $receipt->id)
                ->pluck('invoice_id')
                ->toArray();

            if (!empty($allocatedInvoiceIds)) {
                $salesOrderIds = SalesInvoice::whereIn('id', $allocatedInvoiceIds)
                    ->whereNotNull('sales_order_id')
                    ->pluck('sales_order_id')
                    ->unique()
                    ->toArray();

                $workflowAuditService = app(SalesWorkflowAuditService::class);
                foreach ($salesOrderIds as $soId) {
                    $so = SalesOrder::find($soId);
                    if ($so) {
                        $workflowAuditService->logSalesReceiptCreation($so, $receipt->id);
                    }
                }
            }

            return redirect()->route('sales-receipts.show', $receipt->id)->with('success', 'Receipt created');
        });
    }

    public function show(int $id)
    {
        $receipt = SalesReceipt::with('lines')->findOrFail($id);

        $allocations = DB::table('sales_receipt_allocations as sra')
            ->join('sales_invoices as si', 'si.id', '=', 'sra.invoice_id')
            ->select(
                'sra.id as allocation_id',
                'sra.amount as allocation_amount',
                'si.id as invoice_id',
                'si.invoice_no',
                'si.date as invoice_date',
                'si.due_date',
                'si.total_amount as invoice_total',
                'si.status as invoice_status'
            )
            ->where('sra.receipt_id', $id)
            ->orderBy('si.date', 'ASC')
            ->orderBy('si.invoice_no', 'ASC')
            ->get();

        $creator = null;
        $auditLog = DB::table('audit_logs')
            ->where('entity_type', 'sales_receipt')
            ->where('entity_id', $id)
            ->where('action', 'created')
            ->orderBy('created_at', 'ASC')
            ->first();

        if ($auditLog && $auditLog->user_id) {
            $creator = DB::table('users')->find($auditLog->user_id);
        }

        return view('sales_receipts.show', compact('receipt', 'allocations', 'creator'));
    }

    public function pdf(int $id)
    {
        $receipt = SalesReceipt::with('lines')->findOrFail($id);
        $pdf = app(\App\Services\PdfService::class)->renderViewToString('sales_receipts.print', [
            'receipt' => $receipt,
        ]);
        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="receipt-' . $id . '.pdf"'
        ]);
    }

    public function queuePdf(int $id)
    {
        $receipt = SalesReceipt::with('lines')->findOrFail($id);
        $path = 'public/pdfs/receipt-' . $receipt->id . '.pdf';
        \App\Jobs\GeneratePdfJob::dispatch('sales_receipts.print', ['receipt' => $receipt], $path);
        $url = \Illuminate\Support\Facades\Storage::url($path);
        return back()->with('success', 'PDF generation started')->with('pdf_url', $url);
    }

    public function post(int $id)
    {
        $receipt = SalesReceipt::with('lines')->findOrFail($id);
        if ($receipt->status === 'posted') {
            return back()->with('success', 'Already posted');
        }

        $cashAccountId = (int) DB::table('accounts')->where('code', '1.1.1.01')->value('id'); // Kas di Tangan
        $arAccountId = (int) DB::table('accounts')->where('code', '1.1.2.01')->value('id'); // Piutang Dagang

        $total = (float) $receipt->total_amount;
        $lines = [];
        // Debit Cash/Bank
        $lines[] = [
            'account_id' => $cashAccountId,
            'debit' => $total,
            'credit' => 0,
            'project_id' => null,
            'fund_id' => null,
            'dept_id' => null,
            'memo' => 'Receipt cash/bank',
        ];
        // Credit AR
        $lines[] = [
            'account_id' => $arAccountId,
            'debit' => 0,
            'credit' => $total,
            'project_id' => null,
            'fund_id' => null,
            'dept_id' => null,
            'memo' => 'Settle Accounts Receivable',
        ];

        DB::transaction(function () use ($receipt, $lines) {
            $jid = $this->posting->postJournal([
                'date' => $receipt->date->toDateString(),
                'description' => 'Post Sales Receipt #' . $receipt->id,
                'source_type' => 'sales_receipt',
                'source_id' => $receipt->id,
                'lines' => $lines,
            ]);

            $receipt->update(['status' => 'posted', 'posted_at' => now()]);
        });

        return back()->with('success', 'Receipt posted');
    }

    public function data(Request $request)
    {
        $q = DB::table('sales_receipts as sr')
            ->leftJoin('business_partners as c', 'c.id', '=', 'sr.business_partner_id')
            ->select('sr.id', 'sr.date', 'sr.receipt_no', 'sr.business_partner_id', 'c.name as customer_name', 'sr.total_amount', 'sr.status');

        if ($request->filled('status')) {
            $q->where('sr.status', $request->input('status'));
        }
        if ($request->filled('from')) {
            $q->whereDate('sr.date', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $q->whereDate('sr.date', '<=', $request->input('to'));
        }
        if ($request->filled('q')) {
            $kw = $request->input('q');
            $q->where(function ($w) use ($kw) {
                $w->where('sr.receipt_no', 'like', '%' . $kw . '%')
                    ->orWhere('sr.description', 'like', '%' . $kw + '%')
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
                $url = route('sales-receipts.show', $row->id);
                return '<a href="' . $url . '" class="btn btn-xs btn-info">View</a>';
            })
            ->rawColumns(['actions'])
            ->toJson();
    }

    public function previewAllocation(Request $request)
    {
        $request->validate([
            'business_partner_id' => ['required', 'integer'],
            'amount' => ['nullable', 'numeric', 'min:0'],
        ]);
        $pool = (float) ($request->input('amount') ?? 0);
        $rows = [];
        $open = DB::table('sales_invoices as si')
            ->leftJoin('sales_receipt_allocations as sra', 'sra.invoice_id', '=', 'si.id')
            ->select('si.id', 'si.invoice_no', 'si.total_amount', DB::raw('COALESCE(SUM(sra.amount),0) as allocated'), DB::raw('COALESCE(si.due_date, si.date) as eff_date'))
            ->where('si.business_partner_id', (int) $request->input('business_partner_id'))
            ->where('si.status', 'posted')
            ->groupBy('si.id', 'si.invoice_no', 'si.total_amount', 'eff_date')
            ->orderBy('eff_date')
            ->orderBy('si.id')
            ->get();
        foreach ($open as $inv) {
            $remainingInv = (float) $inv->total_amount - (float) $inv->allocated;
            if ($remainingInv <= 0) continue;
            $alloc = $pool > 0 ? min($remainingInv, $pool) : 0;
            $pool -= $alloc;
            $rows[] = [
                'invoice_id' => $inv->id,
                'invoice_no' => $inv->invoice_no,
                'remaining_before' => round($remainingInv, 2),
                'allocate' => round($alloc, 2),
            ];
        }
        return response()->json(['rows' => $rows]);
    }

    public function getAvailableInvoices(Request $request)
    {
        $request->validate([
            'business_partner_id' => ['required', 'integer', 'exists:business_partners,id'],
        ]);

        $invoices = DB::table('sales_invoices as si')
            ->leftJoin('sales_receipt_allocations as sra', 'sra.invoice_id', '=', 'si.id')
            ->select(
                'si.id',
                'si.invoice_no',
                'si.date',
                'si.due_date',
                'si.total_amount',
                DB::raw('COALESCE(SUM(sra.amount), 0) as allocated_amount'),
                DB::raw('si.total_amount - COALESCE(SUM(sra.amount), 0) as remaining_balance'),
                DB::raw('COALESCE(si.due_date, si.date) as effective_due_date'),
                DB::raw('DATEDIFF(CURDATE(), COALESCE(si.due_date, si.date)) as days_overdue')
            )
            ->where('si.business_partner_id', (int) $request->input('business_partner_id'))
            ->where('si.status', 'posted')
            ->groupBy('si.id', 'si.invoice_no', 'si.date', 'si.due_date', 'si.total_amount')
            ->havingRaw('remaining_balance > 0')
            ->orderBy('effective_due_date', 'ASC')
            ->orderBy('si.id', 'ASC')
            ->get();

        return response()->json([
            'invoices' => $invoices->map(function ($inv) {
                return [
                    'id' => $inv->id,
                    'invoice_no' => $inv->invoice_no,
                    'date' => $inv->date,
                    'due_date' => $inv->due_date,
                    'total_amount' => (float) $inv->total_amount,
                    'allocated_amount' => (float) $inv->allocated_amount,
                    'remaining_balance' => (float) $inv->remaining_balance,
                    'days_overdue' => (int) $inv->days_overdue,
                    'is_overdue' => (int) $inv->days_overdue > 0,
                ];
            }),
        ]);
    }
}
