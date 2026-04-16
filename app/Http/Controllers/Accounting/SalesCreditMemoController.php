<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Accounting\SalesCreditMemo;
use App\Models\Accounting\SalesCreditMemoLine;
use App\Models\Accounting\SalesInvoice;
use App\Services\Accounting\PostingService;
use App\Services\DocumentNumberingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class SalesCreditMemoController extends Controller
{
    public function __construct(
        private PostingService $posting,
        private DocumentNumberingService $documentNumberingService
    ) {
        $this->middleware(['auth']);
        $this->middleware('permission:ar.credit-memos.view')->only(['index', 'show', 'data']);
        $this->middleware('permission:ar.credit-memos.create')->only(['create', 'store']);
        $this->middleware('permission:ar.credit-memos.post')->only(['post']);
    }

    public function index()
    {
        $ptCahaya = \App\Models\CompanyEntity::where('code', '71')->first();
        $cvCahaya = \App\Models\CompanyEntity::where('code', '72')->first();

        return view('sales_credit_memos.index', compact('ptCahaya', 'cvCahaya'));
    }

    public function data(Request $request)
    {
        $q = DB::table('sales_credit_memos as scm')
            ->leftJoin('business_partners as c', 'c.id', '=', 'scm.business_partner_id')
            ->leftJoin('sales_invoices as si', 'si.id', '=', 'scm.sales_invoice_id')
            ->select(
                'scm.id',
                'scm.date',
                'scm.memo_no',
                'scm.total_amount',
                'scm.status',
                'scm.sales_invoice_id',
                'si.invoice_no as sales_invoice_no',
                'c.name as customer_name'
            );

        if ($request->filled('from')) {
            $q->whereDate('scm.date', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $q->whereDate('scm.date', '<=', $request->input('to'));
        }
        if ($request->filled('status')) {
            $q->where('scm.status', $request->input('status'));
        }
        if ($request->filled('company_entity_id')) {
            $q->where('scm.company_entity_id', (int) $request->company_entity_id);
        }
        if ($request->filled('q')) {
            $kw = $request->input('q');
            $q->where(function ($w) use ($kw) {
                $w->where('scm.memo_no', 'like', '%'.$kw.'%')
                    ->orWhere('si.invoice_no', 'like', '%'.$kw.'%')
                    ->orWhere('c.name', 'like', '%'.$kw.'%');
            });
        }

        $totalsRow = DB::query()->fromSub($q->clone(), 't')
            ->selectRaw('COALESCE(SUM(t.total_amount), 0) as sum_total_amount')
            ->first();

        return DataTables::of($q)
            ->with('sum_total_amount', (float) $totalsRow->sum_total_amount)
            ->editColumn('total_amount', function ($row) {
                return number_format((float) $row->total_amount, 2);
            })
            ->editColumn('status', function ($row) {
                return strtoupper((string) $row->status);
            })
            ->addColumn('customer', function ($row) {
                return $row->customer_name ?: '—';
            })
            ->addColumn('sales_invoice', function ($row) {
                return $row->sales_invoice_no ? $row->sales_invoice_no : ('#'.$row->sales_invoice_id);
            })
            ->addColumn('actions', function ($row) {
                $url = route('sales-credit-memos.show', $row->id);

                return '<a href="'.$url.'" class="btn btn-xs btn-info">View</a>';
            })
            ->rawColumns(['actions'])
            ->toJson();
    }

    public function create(Request $request)
    {
        $salesInvoiceId = (int) $request->query('sales_invoice_id', 0);
        if (! $salesInvoiceId) {
            return redirect()->route('sales-credit-memos.index')
                ->with('error', 'Select a posted Sales Invoice. Open an invoice and use Create Credit Memo.');
        }

        $invoice = SalesInvoice::with(['lines.taxCode', 'businessPartner', 'companyEntity'])->findOrFail($salesInvoiceId);
        $reason = $this->creditMemoBlockedReason($invoice);
        if ($reason) {
            return redirect()->route('sales-invoices.show', $invoice->id)
                ->with('error', $reason);
        }

        if (SalesCreditMemo::where('sales_invoice_id', $invoice->id)->exists()) {
            $existing = SalesCreditMemo::where('sales_invoice_id', $invoice->id)->first();

            return redirect()->route('sales-credit-memos.show', $existing->id)
                ->with('info', 'A credit memo already exists for this invoice.');
        }

        return view('sales_credit_memos.create', compact('invoice'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'sales_invoice_id' => 'required|integer|exists:sales_invoices,id',
            'date' => 'required|date',
            'description' => 'nullable|string|max:2000',
        ]);

        $invoice = SalesInvoice::with('lines')->findOrFail((int) $request->sales_invoice_id);
        $reason = $this->creditMemoBlockedReason($invoice);
        if ($reason) {
            return back()->withInput()->with('error', $reason);
        }

        if (SalesCreditMemo::where('sales_invoice_id', $invoice->id)->exists()) {
            return back()->with('error', 'A credit memo already exists for this sales invoice.');
        }

        $memoNo = $this->documentNumberingService->generateNumber('sales_credit_memo', $request->date, [
            'company_entity_id' => $invoice->company_entity_id,
        ]);

        $totalAmount = (float) $invoice->lines->sum('amount');

        DB::transaction(function () use ($request, $invoice, $memoNo, $totalAmount) {
            $memo = SalesCreditMemo::create([
                'memo_no' => $memoNo,
                'date' => $request->date,
                'sales_invoice_id' => $invoice->id,
                'business_partner_id' => $invoice->business_partner_id,
                'business_partner_project_id' => $invoice->business_partner_project_id,
                'company_entity_id' => $invoice->company_entity_id,
                'description' => $request->description,
                'total_amount' => $totalAmount,
                'status' => 'draft',
                'created_by' => Auth::id(),
            ]);

            foreach ($invoice->lines as $line) {
                SalesCreditMemoLine::create([
                    'credit_memo_id' => $memo->id,
                    'account_id' => $line->account_id,
                    'delivery_order_line_id' => $line->delivery_order_line_id,
                    'inventory_item_id' => $line->inventory_item_id,
                    'item_code' => $line->item_code,
                    'item_name' => $line->item_name,
                    'description' => $line->description,
                    'qty' => $line->qty,
                    'unit_price' => $line->unit_price,
                    'amount' => $line->amount,
                    'tax_code_id' => $line->tax_code_id,
                    'project_id' => $line->project_id,
                    'dept_id' => $line->dept_id,
                ]);
            }
        });

        $memo = SalesCreditMemo::where('sales_invoice_id', $invoice->id)->first();

        return redirect()->route('sales-credit-memos.show', $memo->id)
            ->with('success', 'Credit memo '.$memo->memo_no.' created. Review and post.');
    }

    public function show(int $id)
    {
        $memo = SalesCreditMemo::with([
            'lines.account',
            'lines.taxCode',
            'salesInvoice',
            'businessPartner',
            'companyEntity',
        ])->findOrFail($id);

        return view('sales_credit_memos.show', compact('memo'));
    }

    public function post(int $id)
    {
        $memo = SalesCreditMemo::with(['lines', 'salesInvoice'])->findOrFail($id);
        if ($memo->status === 'posted') {
            return back()->with('success', 'Already posted');
        }

        $invoice = $memo->salesInvoice;
        $reason = $this->creditMemoBlockedReason($invoice);
        if ($reason) {
            return back()->with('error', $reason);
        }

        $arUnInvoiceAccountId = (int) DB::table('accounts')->where('code', '1.1.2.04')->value('id');
        $arAccountId = (int) DB::table('accounts')->where('code', '1.1.2.01')->value('id');
        $ppnOutputId = (int) DB::table('accounts')->where('code', '2.1.2')->value('id');

        $revenueTotal = 0.0;
        $ppnTotal = 0.0;

        foreach ($memo->lines as $l) {
            $revenueTotal += (float) $l->amount;
            if (! empty($l->tax_code_id)) {
                $rate = (float) DB::table('tax_codes')->where('id', $l->tax_code_id)->value('rate');
                $ppnTotal += round($l->amount * $rate, 2);
            }
        }

        $lines = [
            [
                'account_id' => $arUnInvoiceAccountId,
                'debit' => $revenueTotal,
                'credit' => 0,
                'project_id' => null,
                'dept_id' => null,
                'memo' => 'Restore AR UnInvoice — Credit Memo #'.$memo->memo_no,
            ],
            [
                'account_id' => $arAccountId,
                'debit' => 0,
                'credit' => $revenueTotal + $ppnTotal,
                'project_id' => null,
                'dept_id' => null,
                'memo' => 'Reduce AR — Credit Memo #'.$memo->memo_no,
            ],
        ];
        if ($ppnTotal > 0) {
            $lines[] = [
                'account_id' => $ppnOutputId,
                'debit' => $ppnTotal,
                'credit' => 0,
                'project_id' => null,
                'dept_id' => null,
                'memo' => 'Reduce PPN Keluaran — Credit Memo #'.$memo->memo_no,
            ];
        }

        DB::transaction(function () use ($memo, $lines) {
            $this->posting->postJournal([
                'date' => $memo->date->toDateString(),
                'description' => 'Post AR Credit Memo #'.$memo->memo_no,
                'source_type' => 'sales_credit_memo',
                'source_id' => $memo->id,
                'posted_by' => Auth::id(),
                'lines' => $lines,
            ]);

            $memo->update(['status' => 'posted', 'posted_at' => now()]);
        });

        return back()->with('success', 'Credit memo posted');
    }

    /**
     * @return string|null Error message if blocked, null if OK
     */
    private function creditMemoBlockedReason(SalesInvoice $invoice): ?string
    {
        if ($invoice->status !== 'posted') {
            return 'Credit memo can only be created for a posted sales invoice.';
        }
        if ($invoice->is_opening_balance) {
            return 'Credit memo is not supported for opening balance invoices.';
        }
        if (DB::table('sales_receipt_allocations')->where('invoice_id', $invoice->id)->exists()) {
            return 'Cannot create credit memo: this invoice has sales receipt allocations.';
        }

        return null;
    }
}
