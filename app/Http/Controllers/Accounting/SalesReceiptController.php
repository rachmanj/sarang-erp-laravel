<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Accounting\SalesReceipt;
use App\Models\Accounting\SalesReceiptLine;
use App\Services\Accounting\PostingService;
use App\Services\DocumentNumberingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class SalesReceiptController extends Controller
{
    public function __construct(
        private PostingService $posting,
        private DocumentNumberingService $documentNumberingService
    ) {
        $this->middleware(['auth']);
        $this->middleware('permission:ar.receipts.view')->only(['index', 'show']);
        $this->middleware('permission:ar.receipts.create')->only(['create', 'store']);
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
        return view('sales_receipts.create', compact('customers', 'accounts'));
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
            $receipt = SalesReceipt::create([
                'receipt_no' => null,
                'date' => $data['date'],
                'business_partner_id' => $data['business_partner_id'],
                'description' => $data['description'] ?? null,
                'status' => 'draft',
                'total_amount' => 0,
            ]);

            $receiptNo = $this->documentNumberingService->generateNumber('sales_receipt', $data['date']);
            $receipt->update(['receipt_no' => $receiptNo]);
            $total = 0;
            foreach ($data['lines'] as $l) {
                $amount = (float) $l['amount'];
                $total += $amount;
                SalesReceiptLine::create([
                    'receipt_id' => $receipt->id,
                    'account_id' => $l['account_id'],
                    'description' => $l['description'] ?? null,
                    'amount' => $amount,
                ]);
            }

            $receipt->update(['total_amount' => $total]);
            // Auto-allocate to oldest open invoices
            $remainingPool = $total;
            if ($remainingPool > 0) {
                $open = DB::table('sales_invoices as si')
                    ->leftJoin('sales_receipt_allocations as sra', 'sra.invoice_id', '=', 'si.id')
                    ->select('si.id', 'si.total_amount', DB::raw('COALESCE(SUM(sra.amount),0) as allocated'), DB::raw('COALESCE(si.due_date, si.date) as eff_date'))
                    ->where('si.business_partner_id', $receipt->business_partner_id)
                    ->where('si.status', 'posted')
                    ->groupBy('si.id', 'si.total_amount', 'eff_date')
                    ->orderBy('eff_date')
                    ->orderBy('si.id')
                    ->get();
                foreach ($open as $inv) {
                    $remainingInv = (float)$inv->total_amount - (float)$inv->allocated;
                    if ($remainingInv <= 0) continue;
                    if ($remainingPool <= 0) break;
                    $alloc = min($remainingInv, $remainingPool);
                    DB::table('sales_receipt_allocations')->insert([
                        'receipt_id' => $receipt->id,
                        'invoice_id' => $inv->id,
                        'amount' => $alloc,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $remainingPool -= $alloc;
                }
            }
            return redirect()->route('sales-receipts.show', $receipt->id)->with('success', 'Receipt created');
        });
    }

    public function show(int $id)
    {
        $receipt = SalesReceipt::with('lines')->findOrFail($id);
        return view('sales_receipts.show', compact('receipt'));
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

        $cashAccountId = (int) DB::table('accounts')->where('code', '1.1.2.01')->value('id');
        $arAccountId = (int) DB::table('accounts')->where('code', '1.1.4')->value('id');

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
            'amount' => ['required', 'numeric', 'min:0'],
        ]);
        $pool = (float)$request->input('amount');
        $rows = [];
        if ($pool > 0) {
            $open = DB::table('sales_invoices as si')
                ->leftJoin('sales_receipt_allocations as sra', 'sra.invoice_id', '=', 'si.id')
                ->leftJoin('business_partners as c', 'c.id', '=', 'si.business_partner_id')
                ->select('si.id', 'si.invoice_no', 'si.total_amount', DB::raw('COALESCE(SUM(sra.amount),0) as allocated'), DB::raw('COALESCE(si.due_date, si.date) as eff_date'))
                ->where('si.business_partner_id', (int)$request->input('business_partner_id'))
                ->where('si.status', 'posted')
                ->groupBy('si.id', 'si.invoice_no', 'si.total_amount', 'eff_date')
                ->orderBy('eff_date')
                ->orderBy('si.id')
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
