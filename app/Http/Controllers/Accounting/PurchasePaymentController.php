<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Accounting\PurchasePayment;
use App\Models\Accounting\PurchasePaymentLine;
use App\Services\Accounting\PostingService;
use App\Services\DocumentNumberingService;
use App\Services\DocumentClosureService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class PurchasePaymentController extends Controller
{
    public function __construct(
        private PostingService $posting,
        private DocumentNumberingService $documentNumberingService,
        private DocumentClosureService $documentClosureService
    ) {
        $this->middleware(['auth']);
        $this->middleware('permission:ap.payments.view')->only(['index', 'show']);
        $this->middleware('permission:ap.payments.create')->only(['create', 'store']);
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
        return view('purchase_payments.create', compact('vendors', 'accounts'));
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
            'lines.*.amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        return DB::transaction(function () use ($data) {
            $payment = PurchasePayment::create([
                'payment_no' => null,
                'date' => $data['date'],
                'business_partner_id' => $data['business_partner_id'],
                'description' => $data['description'] ?? null,
                'status' => 'draft',
                'total_amount' => 0,
            ]);

            $paymentNo = $this->documentNumberingService->generateNumber('purchase_payment', $data['date']);
            $payment->update(['payment_no' => $paymentNo]);

            $total = 0;
            foreach ($data['lines'] as $l) {
                $amount = (float) $l['amount'];
                $total += $amount;
                PurchasePaymentLine::create([
                    'payment_id' => $payment->id,
                    'account_id' => $l['account_id'],
                    'description' => $l['description'] ?? null,
                    'amount' => $amount,
                ]);
            }

            $payment->update(['total_amount' => $total]);
            // Auto-allocate to oldest open vendor invoices
            $remainingPool = $total;
            if ($remainingPool > 0) {
                $open = DB::table('purchase_invoices as pi')
                    ->leftJoin('purchase_payment_allocations as ppa', 'ppa.invoice_id', '=', 'pi.id')
                    ->select('pi.id', 'pi.total_amount', DB::raw('COALESCE(SUM(ppa.amount),0) as allocated'), DB::raw('COALESCE(pi.due_date, pi.date) as eff_date'))
                    ->where('pi.business_partner_id', $payment->business_partner_id)
                    ->where('pi.status', 'posted')
                    ->groupBy('pi.id', 'pi.total_amount', 'eff_date')
                    ->orderBy('eff_date')
                    ->orderBy('pi.id')
                    ->get();
                foreach ($open as $inv) {
                    $remainingInv = (float)$inv->total_amount - (float)$inv->allocated;
                    if ($remainingInv <= 0) continue;
                    if ($remainingPool <= 0) break;
                    $alloc = min($remainingInv, $remainingPool);
                    DB::table('purchase_payment_allocations')->insert([
                        'payment_id' => $payment->id,
                        'invoice_id' => $inv->id,
                        'amount' => $alloc,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $remainingPool -= $alloc;
                }
            }

            // Attempt to close related Purchase Invoices if fully paid
            try {
                $this->documentClosureService->closePurchaseInvoiceByPayment($payment->id, auth()->id());
            } catch (\Exception $closureException) {
                // Log closure failure but don't fail the payment creation
                \Log::warning('Failed to close Purchase Invoices after payment creation', [
                    'payment_id' => $payment->id,
                    'error' => $closureException->getMessage()
                ]);
            }

            return redirect()->route('purchase-payments.show', $payment->id)->with('success', 'Payment created');
        });
    }

    public function show(int $id)
    {
        $payment = PurchasePayment::with('lines')->findOrFail($id);
        return view('purchase_payments.show', compact('payment'));
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

        $cashAccountId = (int) DB::table('accounts')->where('code', '1.1.2.01')->value('id');
        $apAccountId = (int) DB::table('accounts')->where('code', '2.1.1')->value('id');

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
                ->leftJoin('business_partners as v', 'v.id', '=', 'pi.vendor_id')
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
