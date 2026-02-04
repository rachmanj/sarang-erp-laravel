<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Accounting\PurchasePayment;
use App\Models\Accounting\PurchasePaymentLine;
use App\Models\Accounting\PurchaseInvoice;
use App\Models\PurchaseOrder;
use App\Services\Accounting\PostingService;
use App\Services\DocumentNumberingService;
use App\Services\DocumentClosureService;
use App\Services\CompanyEntityService;
use App\Services\PurchaseWorkflowAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class PurchasePaymentController extends Controller
{
    public function __construct(
        private PostingService $posting,
        private DocumentNumberingService $documentNumberingService,
        private DocumentClosureService $documentClosureService,
        private CompanyEntityService $companyEntityService
    ) {
        $this->middleware(['auth']);
        $this->middleware('permission:ap.payments.view')->only(['index', 'show']);
        $this->middleware('permission:ap.payments.create')->only(['create', 'store', 'getAvailableInvoices', 'previewAllocation']);
        $this->middleware('permission:ap.payments.post')->only(['post']);
    }

    public function index()
    {
        return view('purchase_payments.index');
    }

    public function create()
    {
        $vendors = DB::table('business_partners')->where('partner_type', 'supplier')->orderBy('name')->get();
        $accounts = DB::table('accounts')->where('is_postable', 1)->orderBy('code')->get();
        $entities = $this->companyEntityService->getActiveEntities();
        $defaultEntity = $this->companyEntityService->getDefaultEntity();
        return view('purchase_payments.create', compact('vendors', 'accounts', 'entities', 'defaultEntity'));
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
            'allocations.*.invoice_id' => ['required', 'integer', 'exists:purchase_invoices,id'],
            'allocations.*.amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        $entity = $this->companyEntityService->getEntity($request->input('company_entity_id'));

        return DB::transaction(function () use ($data, $entity, $request) {
            // Calculate total payment amount
            $totalPayment = 0;
            foreach ($data['lines'] as $l) {
                $totalPayment += (float)$l['amount'];
            }

            // Calculate total allocation amount
            $totalAllocation = 0;
            foreach ($data['allocations'] as $alloc) {
                $totalAllocation += (float)$alloc['amount'];
            }

            // Validate that payment total matches allocation total
            if (abs($totalPayment - $totalAllocation) > 0.01) {
                return back()->withErrors(['lines' => 'Payment total must match allocation total.'])->withInput();
            }

            // Validate allocations don't exceed remaining balances
            foreach ($data['allocations'] as $alloc) {
                $invoice = PurchaseInvoice::findOrFail($alloc['invoice_id']);

                // Verify invoice belongs to selected vendor
                if ($invoice->business_partner_id != $data['business_partner_id']) {
                    return back()->withErrors(['allocations' => 'Selected invoice does not belong to selected vendor.'])->withInput();
                }

                // Verify invoice is posted
                if ($invoice->status !== 'posted') {
                    return back()->withErrors(['allocations' => 'Invoice must be posted before payment allocation.'])->withInput();
                }

                // Calculate remaining balance
                $allocated = DB::table('purchase_payment_allocations')
                    ->where('invoice_id', $invoice->id)
                    ->sum('amount');
                $remaining = (float)$invoice->total_amount - (float)$allocated;
                $allocAmount = (float)$alloc['amount'];

                if ($allocAmount > $remaining + 0.01) {
                    return back()->withErrors(['allocations' => "Allocation amount for invoice {$invoice->invoice_no} exceeds remaining balance."])->withInput();
                }
            }

            $payment = PurchasePayment::create([
                'payment_no' => null,
                'date' => $data['date'],
                'business_partner_id' => $data['business_partner_id'],
                'company_entity_id' => $entity->id,
                'description' => $data['description'] ?? null,
                'status' => 'draft',
                'total_amount' => $totalPayment,
            ]);

            $paymentNo = $this->documentNumberingService->generateNumber('purchase_payment', $data['date'], [
                'company_entity_id' => $entity->id,
            ]);
            $payment->update(['payment_no' => $paymentNo]);

            // Create payment lines
            foreach ($data['lines'] as $l) {
                PurchasePaymentLine::create([
                    'payment_id' => $payment->id,
                    'account_id' => $l['account_id'],
                    'description' => $l['description'] ?? null,
                    'amount' => (float)$l['amount'],
                ]);
            }

            // Create explicit allocations
            foreach ($data['allocations'] as $alloc) {
                DB::table('purchase_payment_allocations')->insert([
                    'payment_id' => $payment->id,
                    'invoice_id' => $alloc['invoice_id'],
                    'amount' => (float)$alloc['amount'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Attempt to close related Purchase Invoices if fully paid
            try {
                $this->documentClosureService->closePurchaseInvoiceByPayment($payment->id, auth()->id());
            } catch (\Exception $closureException) {
                // Log closure failure but don't fail the payment creation
                Log::warning('Failed to close Purchase Invoices after payment creation', [
                    'payment_id' => $payment->id,
                    'error' => $closureException->getMessage()
                ]);
            }

            // Log payment creation in Purchase Order audit trail
            // Find Purchase Orders through allocated invoices
            $allocatedInvoiceIds = DB::table('purchase_payment_allocations')
                ->where('payment_id', $payment->id)
                ->pluck('invoice_id')
                ->toArray();

            if (!empty($allocatedInvoiceIds)) {
                $purchaseOrderIds = PurchaseInvoice::whereIn('id', $allocatedInvoiceIds)
                    ->whereNotNull('purchase_order_id')
                    ->pluck('purchase_order_id')
                    ->unique()
                    ->toArray();

                $workflowAuditService = app(PurchaseWorkflowAuditService::class);
                foreach ($purchaseOrderIds as $poId) {
                    $po = PurchaseOrder::find($poId);
                    if ($po) {
                        $workflowAuditService->logPurchasePaymentCreation($po, $payment->id);
                    }
                }
            }

            return redirect()->route('purchase-payments.show', $payment->id)->with('success', 'Payment created');
        });
    }

    public function show(int $id)
    {
        $payment = PurchasePayment::with(['lines', 'allocations.invoice'])->findOrFail($id);

        // Load allocations with invoice details
        $allocations = DB::table('purchase_payment_allocations as ppa')
            ->join('purchase_invoices as pi', 'pi.id', '=', 'ppa.invoice_id')
            ->leftJoin('business_partners as bp', 'bp.id', '=', 'pi.business_partner_id')
            ->select(
                'ppa.id as allocation_id',
                'ppa.amount as allocation_amount',
                'pi.id as invoice_id',
                'pi.invoice_no',
                'pi.date as invoice_date',
                'pi.due_date',
                'pi.total_amount as invoice_total',
                'pi.status as invoice_status'
            )
            ->where('ppa.payment_id', $id)
            ->orderBy('pi.date', 'ASC')
            ->orderBy('pi.invoice_no', 'ASC')
            ->get();

        // Get creator from audit log if available
        $creator = null;
        $auditLog = DB::table('audit_logs')
            ->where('entity_type', 'purchase_payment')
            ->where('entity_id', $id)
            ->where('action', 'created')
            ->orderBy('created_at', 'ASC')
            ->first();

        if ($auditLog && $auditLog->user_id) {
            $creator = DB::table('users')->find($auditLog->user_id);
        }

        return view('purchase_payments.show', compact('payment', 'allocations', 'creator'));
    }

    public function pdf(int $id)
    {
        $payment = PurchasePayment::with('lines')->findOrFail($id);
        $pdf = app(\App\Services\PdfService::class)->renderViewToString('purchase_payments.print', [
            'payment' => $payment,
        ]);
        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="purchase-payment-' . $id . '.pdf"'
        ]);
    }

    public function queuePdf(int $id)
    {
        $payment = PurchasePayment::with('lines')->findOrFail($id);
        $path = 'public/pdfs/purchase-payment-' . $payment->id . '.pdf';
        \App\Jobs\GeneratePdfJob::dispatch('purchase_payments.print', ['payment' => $payment], $path);
        $url = \Illuminate\Support\Facades\Storage::url($path);
        return back()->with('success', 'PDF generation started')->with('pdf_url', $url);
    }

    public function post(int $id)
    {
        $payment = PurchasePayment::with('lines')->findOrFail($id);
        if ($payment->status === 'posted') {
            return back()->with('success', 'Already posted');
        }

        $cashAccountId = (int) DB::table('accounts')->where('code', '1.1.1.01')->value('id'); // Kas di Tangan
        $apAccountId = (int) DB::table('accounts')->where('code', '2.1.1.01')->value('id'); // Utang Dagang

        $total = (float) $payment->total_amount;
        $lines = [];
        // Credit Cash/Bank
        $lines[] = [
            'account_id' => $cashAccountId,
            'debit' => 0,
            'credit' => $total,
            'project_id' => null,
            'fund_id' => null,
            'dept_id' => null,
            'memo' => 'Payment cash/bank',
        ];
        // Debit AP
        $lines[] = [
            'account_id' => $apAccountId,
            'debit' => $total,
            'credit' => 0,
            'project_id' => null,
            'fund_id' => null,
            'dept_id' => null,
            'memo' => 'Settle Accounts Payable',
        ];

        DB::transaction(function () use ($payment, $lines) {
            $jid = $this->posting->postJournal([
                'date' => $payment->date->toDateString(),
                'description' => 'Post Purchase Payment #' . $payment->id,
                'source_type' => 'purchase_payment',
                'source_id' => $payment->id,
                'lines' => $lines,
            ]);

            $payment->update(['status' => 'posted', 'posted_at' => now()]);
        });

        return back()->with('success', 'Payment posted');
    }

    public function data(Request $request)
    {
        $q = DB::table('purchase_payments as pp')
            ->leftJoin('business_partners as v', 'v.id', '=', 'pp.business_partner_id')
            ->select('pp.id', 'pp.date', 'pp.payment_no', 'pp.business_partner_id', 'v.name as vendor_name', 'pp.total_amount', 'pp.status');

        if ($request->filled('status')) {
            $q->where('pp.status', $request->input('status'));
        }
        if ($request->filled('from')) {
            $q->whereDate('pp.date', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $q->whereDate('pp.date', '<=', $request->input('to'));
        }
        if ($request->filled('q')) {
            $kw = $request->input('q');
            $q->where(function ($w) use ($kw) {
                $w->where('pp.payment_no', 'like', '%' . $kw . '%')
                    ->orWhere('pp.description', 'like', '%' . $kw + '%')
                    ->orWhere('v.name', 'like', '%' . $kw + '%');
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
                $url = route('purchase-payments.show', $row->id);
                return '<a href="' . $url . '" class="btn btn-xs btn-info">View</a>';
            })
            ->rawColumns(['actions'])
            ->toJson();
    }

    public function getAvailableInvoices(Request $request)
    {
        $request->validate([
            'business_partner_id' => ['required', 'integer', 'exists:business_partners,id'],
        ]);

        $invoices = DB::table('purchase_invoices as pi')
            ->leftJoin('purchase_payment_allocations as ppa', 'ppa.invoice_id', '=', 'pi.id')
            ->select(
                'pi.id',
                'pi.invoice_no',
                'pi.date',
                'pi.due_date',
                'pi.total_amount',
                DB::raw('COALESCE(SUM(ppa.amount), 0) as allocated_amount'),
                DB::raw('pi.total_amount - COALESCE(SUM(ppa.amount), 0) as remaining_balance'),
                DB::raw('COALESCE(pi.due_date, pi.date) as effective_due_date'),
                DB::raw('DATEDIFF(CURDATE(), COALESCE(pi.due_date, pi.date)) as days_overdue')
            )
            ->where('pi.business_partner_id', (int)$request->input('business_partner_id'))
            ->where('pi.status', 'posted')
            ->where(function ($query) {
                $query->where('pi.payment_method', '!=', 'cash')
                    ->orWhere(function ($q) {
                        $q->whereNull('pi.payment_method')
                            ->where('pi.is_direct_purchase', 0);
                    })
                    ->orWhereNull('pi.is_direct_purchase');
            })
            ->groupBy('pi.id', 'pi.invoice_no', 'pi.date', 'pi.due_date', 'pi.total_amount')
            ->havingRaw('remaining_balance > 0')
            ->orderBy('effective_due_date', 'ASC')
            ->orderBy('pi.id', 'ASC')
            ->get();

        return response()->json([
            'invoices' => $invoices->map(function ($inv) {
                return [
                    'id' => $inv->id,
                    'invoice_no' => $inv->invoice_no,
                    'date' => $inv->date,
                    'due_date' => $inv->due_date,
                    'total_amount' => (float)$inv->total_amount,
                    'allocated_amount' => (float)$inv->allocated_amount,
                    'remaining_balance' => (float)$inv->remaining_balance,
                    'days_overdue' => (int)$inv->days_overdue,
                    'is_overdue' => (int)$inv->days_overdue > 0,
                ];
            })
        ]);
    }

    public function previewAllocation(Request $request)
    {
        $request->validate([
            'business_partner_id' => ['required', 'integer'],
            'amount' => ['required', 'numeric', 'min:0'],
        ]);
        $pool = (float)$request->input('amount');
        $rows = [];
        if ($pool > 0) {
            $open = DB::table('purchase_invoices as pi')
                ->leftJoin('purchase_payment_allocations as ppa', 'ppa.invoice_id', '=', 'pi.id')
                ->leftJoin('business_partners as v', 'v.id', '=', 'pi.business_partner_id')
                ->select('pi.id', 'pi.invoice_no', 'pi.total_amount', DB::raw('COALESCE(SUM(ppa.amount),0) as allocated'), DB::raw('COALESCE(pi.due_date, pi.date) as eff_date'))
                ->where('pi.business_partner_id', (int)$request->input('business_partner_id'))
                ->where('pi.status', 'posted')
                ->groupBy('pi.id', 'pi.invoice_no', 'pi.total_amount', 'eff_date')
                ->orderBy('eff_date')
                ->orderBy('pi.id')
                ->get();
            foreach ($open as $inv) {
                $remainingInv = (float)$inv->total_amount - (float)$inv->allocated;
                if ($remainingInv <= 0) continue;
                if ($pool <= 0) break;
                $alloc = min($remainingInv, $pool);
                $rows[] = [
                    'invoice_id' => $inv->id,
                    'invoice_no' => $inv->invoice_no,
                    'remaining_before' => round($remainingInv, 2),
                    'allocate' => round($alloc, 2),
                ];
                $pool -= $alloc;
            }
        }
        return response()->json(['rows' => $rows]);
    }
}
