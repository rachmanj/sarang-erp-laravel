<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Accounting\PurchasePayment;
use App\Models\Accounting\PurchasePaymentLine;
use App\Services\Accounting\PostingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchasePaymentController extends Controller
{
    public function __construct(private PostingService $posting)
    {
        $this->middleware(['auth']);
        $this->middleware('permission:ap.payments.view')->only(['index', 'show']);
        $this->middleware('permission:ap.payments.create')->only(['create', 'store']);
        $this->middleware('permission:ap.payments.post')->only(['post']);
    }

    public function index()
    {
        $query = PurchasePayment::query();
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
                $w->where('payment_no', 'like', "%$q%")
                    ->orWhere('description', 'like', "%$q%")
                    ->orWhereIn('vendor_id', function ($sub) use ($q) {
                        $sub->from('vendors')->select('id')->where('name', 'like', "%$q%");
                    });
            });
        }

        if (request('export') === 'csv') {
            $rows = $query->orderByDesc('date')->orderByDesc('id')->get(['date', 'payment_no', 'vendor_id', 'total_amount', 'status']);
            $csv = "date,payment_no,vendor,total,status\n";
            foreach ($rows as $r) {
                $name = \Illuminate\Support\Facades\DB::table('vendors')->where('id', $r->vendor_id)->value('name');
                $csv .= sprintf("%s,%s,%s,%.2f,%s\n", $r->date, $r->payment_no, str_replace(',', ' ', (string) $name), $r->total_amount, $r->status);
            }
            return response($csv, 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="purchase-payments.csv"']);
        }

        $payments = $query->orderByDesc('date')->orderByDesc('id')->paginate(20)->appends(request()->query());
        return view('purchase_payments.index', compact('payments'));
    }

    public function create()
    {
        $vendors = DB::table('vendors')->orderBy('name')->get();
        $accounts = DB::table('accounts')->where('is_postable', 1)->orderBy('code')->get();
        return view('purchase_payments.create', compact('vendors', 'accounts'));
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
            'lines.*.amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        return DB::transaction(function () use ($data) {
            $payment = PurchasePayment::create([
                'payment_no' => null,
                'date' => $data['date'],
                'vendor_id' => $data['vendor_id'],
                'description' => $data['description'] ?? null,
                'status' => 'draft',
                'total_amount' => 0,
            ]);

            $ym = date('Ym', strtotime($data['date']));
            $payment->update(['payment_no' => sprintf('PP-%s-%06d', $ym, $payment->id)]);

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
}
