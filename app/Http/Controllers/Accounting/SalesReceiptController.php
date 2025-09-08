<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Accounting\SalesReceipt;
use App\Models\Accounting\SalesReceiptLine;
use App\Services\Accounting\PostingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesReceiptController extends Controller
{
    public function __construct(private PostingService $posting)
    {
        $this->middleware(['auth']);
        $this->middleware('permission:ar.receipts.view')->only(['index', 'show']);
        $this->middleware('permission:ar.receipts.create')->only(['create', 'store']);
        $this->middleware('permission:ar.receipts.post')->only(['post']);
    }

    public function index()
    {
        $query = SalesReceipt::query();
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
                $w->where('receipt_no', 'like', "%$q%")
                    ->orWhere('description', 'like', "%$q%")
                    ->orWhereIn('customer_id', function ($sub) use ($q) {
                        $sub->from('customers')->select('id')->where('name', 'like', "%$q%");
                    });
            });
        }

        if (request('export') === 'csv') {
            $rows = $query->orderByDesc('date')->orderByDesc('id')->get(['date', 'receipt_no', 'customer_id', 'total_amount', 'status']);
            $csv = "date,receipt_no,customer,total,status\n";
            foreach ($rows as $r) {
                $name = \Illuminate\Support\Facades\DB::table('customers')->where('id', $r->customer_id)->value('name');
                $csv .= sprintf("%s,%s,%s,%.2f,%s\n", $r->date, $r->receipt_no, str_replace(',', ' ', (string) $name), $r->total_amount, $r->status);
            }
            return response($csv, 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="sales-receipts.csv"']);
        }

        $receipts = $query->orderByDesc('date')->orderByDesc('id')->paginate(20)->appends(request()->query());
        return view('sales_receipts.index', compact('receipts'));
    }

    public function create()
    {
        $customers = DB::table('customers')->orderBy('name')->get();
        $accounts = DB::table('accounts')->where('is_postable', 1)->orderBy('code')->get();
        return view('sales_receipts.create', compact('customers', 'accounts'));
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
            'lines.*.amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        return DB::transaction(function () use ($data) {
            $receipt = SalesReceipt::create([
                'receipt_no' => null,
                'date' => $data['date'],
                'customer_id' => $data['customer_id'],
                'description' => $data['description'] ?? null,
                'status' => 'draft',
                'total_amount' => 0,
            ]);

            $ym = date('Ym', strtotime($data['date']));
            $receipt->update(['receipt_no' => sprintf('SR-%s-%06d', $ym, $receipt->id)]);
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
}
