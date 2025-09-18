<?php

namespace App\Http\Controllers;

use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptLine;
use App\Models\PurchaseOrder;
use App\Services\DocumentNumberingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GoodsReceiptController extends Controller
{
    public function __construct(
        private DocumentNumberingService $documentNumberingService
    ) {}

    public function index()
    {
        return view('goods_receipts.index');
    }

    public function create()
    {
        $vendors = DB::table('vendors')->orderBy('name')->get();
        $accounts = DB::table('accounts')->where('is_postable', 1)->orderBy('code')->get();
        $taxCodes = DB::table('tax_codes')->orderBy('code')->get();
        $purchaseOrders = DB::table('purchase_orders')->orderByDesc('id')->limit(50)->get(['id', 'order_no']);
        return view('goods_receipts.create', compact('vendors', 'accounts', 'taxCodes', 'purchaseOrders'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'vendor_id' => ['required', 'integer', 'exists:vendors,id'],
            'purchase_order_id' => ['nullable', 'integer', 'exists:purchase_orders,id'],
            'description' => ['nullable', 'string', 'max:255'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.account_id' => ['required', 'integer', 'exists:accounts,id'],
            'lines.*.description' => ['nullable', 'string', 'max:255'],
            'lines.*.qty' => ['required', 'numeric', 'min:0.01'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.tax_code_id' => ['nullable', 'integer', 'exists:tax_codes,id'],
        ]);

        return DB::transaction(function () use ($data) {
            $grn = GoodsReceipt::create([
                'grn_no' => null,
                'date' => $data['date'],
                'vendor_id' => $data['vendor_id'],
                'purchase_order_id' => $data['purchase_order_id'] ?? null,
                'description' => $data['description'] ?? null,
                'status' => 'draft',
                'total_amount' => 0,
            ]);
            $grnNo = $this->documentNumberingService->generateNumber('goods_receipt', $data['date']);
            $grn->update(['grn_no' => $grnNo]);
            $total = 0;
            foreach ($data['lines'] as $l) {
                $amount = (float)$l['qty'] * (float)$l['unit_price'];
                $total += $amount;
                GoodsReceiptLine::create([
                    'grn_id' => $grn->id,
                    'account_id' => $l['account_id'],
                    'description' => $l['description'] ?? null,
                    'qty' => (float)$l['qty'],
                    'unit_price' => (float)$l['unit_price'],
                    'amount' => $amount,
                    'tax_code_id' => $l['tax_code_id'] ?? null,
                ]);
            }
            $grn->update(['total_amount' => $total]);
            return redirect()->route('goods-receipts.show', $grn->id)->with('success', 'Goods Receipt created');
        });
    }

    public function show(int $id)
    {
        $grn = GoodsReceipt::with('lines')->findOrFail($id);
        return view('goods_receipts.show', compact('grn'));
    }

    public function receive(int $id)
    {
        $grn = GoodsReceipt::findOrFail($id);
        if ($grn->status === 'received') {
            return back()->with('success', 'Already received');
        }
        $grn->update(['status' => 'received']);
        return back()->with('success', 'Goods Receipt marked as received');
    }

    public function createInvoice(int $id)
    {
        $grn = GoodsReceipt::with('lines')->findOrFail($id);
        $accounts = DB::table('accounts')->where('is_postable', 1)->orderBy('code')->get();
        $vendors = DB::table('vendors')->orderBy('name')->get();
        $taxCodes = DB::table('tax_codes')->orderBy('code')->get();
        $prefill = [
            'date' => now()->toDateString(),
            'vendor_id' => $grn->vendor_id,
            'description' => 'From GRN ' . ($grn->grn_no ?: ('#' . $grn->id)),
            'lines' => $grn->lines->map(function ($l) {
                return [
                    'account_id' => (int)$l->account_id,
                    'description' => $l->description,
                    'qty' => (float)$l->qty,
                    'unit_price' => (float)$l->unit_price,
                    'tax_code_id' => $l->tax_code_id,
                ];
            })->toArray(),
        ];
        return view('purchase_invoices.create', compact('accounts', 'vendors', 'taxCodes') + ['prefill' => $prefill, 'goods_receipt_id' => $grn->id]);
    }
}
