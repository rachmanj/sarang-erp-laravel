<?php

namespace App\Http\Controllers;

use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{
    public function index()
    {
        return view('purchase_orders.index');
    }

    public function create()
    {
        $vendors = DB::table('vendors')->orderBy('name')->get();
        $accounts = DB::table('accounts')->where('is_postable', 1)->orderBy('code')->get();
        $taxCodes = DB::table('tax_codes')->orderBy('code')->get();
        return view('purchase_orders.create', compact('vendors', 'accounts', 'taxCodes'));
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
            'lines.*.qty' => ['required', 'numeric', 'min:0.01'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.tax_code_id' => ['nullable', 'integer', 'exists:tax_codes,id'],
        ]);

        return DB::transaction(function () use ($data) {
            $po = PurchaseOrder::create([
                'order_no' => null,
                'date' => $data['date'],
                'vendor_id' => $data['vendor_id'],
                'description' => $data['description'] ?? null,
                'status' => 'draft',
                'total_amount' => 0,
            ]);
            $ym = date('Ym', strtotime($data['date']));
            $po->update(['order_no' => sprintf('PO-%s-%06d', $ym, $po->id)]);
            $total = 0;
            foreach ($data['lines'] as $l) {
                $amount = (float)$l['qty'] * (float)$l['unit_price'];
                $total += $amount;
                PurchaseOrderLine::create([
                    'order_id' => $po->id,
                    'account_id' => $l['account_id'],
                    'description' => $l['description'] ?? null,
                    'qty' => (float)$l['qty'],
                    'unit_price' => (float)$l['unit_price'],
                    'amount' => $amount,
                    'tax_code_id' => $l['tax_code_id'] ?? null,
                ]);
            }
            $po->update(['total_amount' => $total]);
            return redirect()->route('purchase-orders.show', $po->id)->with('success', 'Purchase Order created');
        });
    }

    public function show(int $id)
    {
        $order = PurchaseOrder::with('lines')->findOrFail($id);
        return view('purchase_orders.show', compact('order'));
    }

    public function approve(int $id)
    {
        $order = PurchaseOrder::findOrFail($id);
        if ($order->status !== 'draft') {
            return back()->with('success', 'Already approved');
        }
        $order->update(['status' => 'approved']);
        return back()->with('success', 'Purchase Order approved');
    }

    public function close(int $id)
    {
        $order = PurchaseOrder::findOrFail($id);
        if ($order->status === 'closed') {
            return back()->with('success', 'Already closed');
        }
        $order->update(['status' => 'closed']);
        return back()->with('success', 'Purchase Order closed');
    }

    public function createInvoice(int $id)
    {
        $order = PurchaseOrder::with('lines')->findOrFail($id);
        $accounts = DB::table('accounts')->where('is_postable', 1)->orderBy('code')->get();
        $vendors = DB::table('vendors')->orderBy('name')->get();
        $taxCodes = DB::table('tax_codes')->orderBy('code')->get();
        $prefill = [
            'date' => now()->toDateString(),
            'vendor_id' => $order->vendor_id,
            'description' => 'From PO ' . ($order->order_no ?: ('#' . $order->id)),
            'lines' => $order->lines->map(function ($l) {
                return [
                    'account_id' => (int)$l->account_id,
                    'description' => $l->description,
                    'qty' => (float)$l->qty,
                    'unit_price' => (float)$l->unit_price,
                    'tax_code_id' => $l->tax_code_id,
                ];
            })->toArray(),
        ];
        return view('purchase_invoices.create', compact('accounts', 'vendors', 'taxCodes') + ['prefill' => $prefill, 'purchase_order_id' => $order->id]);
    }
}
