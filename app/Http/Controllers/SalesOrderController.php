<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesOrderController extends Controller
{
    public function index()
    {
        return view('sales_orders.index');
    }

    public function create()
    {
        $customers = DB::table('customers')->orderBy('name')->get();
        $accounts = DB::table('accounts')->where('is_postable', 1)->orderBy('code')->get();
        $taxCodes = DB::table('tax_codes')->orderBy('code')->get();
        return view('sales_orders.create', compact('customers', 'accounts', 'taxCodes'));
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
            'lines.*.qty' => ['required', 'numeric', 'min:0.01'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.tax_code_id' => ['nullable', 'integer', 'exists:tax_codes,id'],
        ]);

        return DB::transaction(function () use ($data) {
            $so = SalesOrder::create([
                'order_no' => null,
                'date' => $data['date'],
                'customer_id' => $data['customer_id'],
                'description' => $data['description'] ?? null,
                'status' => 'draft',
                'total_amount' => 0,
            ]);
            $ym = date('Ym', strtotime($data['date']));
            $so->update(['order_no' => sprintf('SO-%s-%06d', $ym, $so->id)]);
            $total = 0;
            foreach ($data['lines'] as $l) {
                $amount = (float)$l['qty'] * (float)$l['unit_price'];
                $total += $amount;
                SalesOrderLine::create([
                    'order_id' => $so->id,
                    'account_id' => $l['account_id'],
                    'description' => $l['description'] ?? null,
                    'qty' => (float)$l['qty'],
                    'unit_price' => (float)$l['unit_price'],
                    'amount' => $amount,
                    'tax_code_id' => $l['tax_code_id'] ?? null,
                ]);
            }
            $so->update(['total_amount' => $total]);
            return redirect()->route('sales-orders.show', $so->id)->with('success', 'Sales Order created');
        });
    }

    public function show(int $id)
    {
        $order = SalesOrder::with('lines')->findOrFail($id);
        return view('sales_orders.show', compact('order'));
    }

    public function approve(int $id)
    {
        $order = SalesOrder::findOrFail($id);
        if ($order->status !== 'draft') {
            return back()->with('success', 'Already approved');
        }
        $order->update(['status' => 'approved']);
        return back()->with('success', 'Sales Order approved');
    }

    public function close(int $id)
    {
        $order = SalesOrder::findOrFail($id);
        if ($order->status === 'closed') {
            return back()->with('success', 'Already closed');
        }
        $order->update(['status' => 'closed']);
        return back()->with('success', 'Sales Order closed');
    }

    public function createInvoice(int $id)
    {
        $order = SalesOrder::with('lines')->findOrFail($id);
        $accounts = DB::table('accounts')->where('is_postable', 1)->orderBy('code')->get();
        $customers = DB::table('customers')->orderBy('name')->get();
        $taxCodes = DB::table('tax_codes')->orderBy('code')->get();
        $prefill = [
            'date' => now()->toDateString(),
            'customer_id' => $order->customer_id,
            'description' => 'From SO ' . ($order->order_no ?: ('#' . $order->id)),
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
        return view('sales_invoices.create', compact('accounts', 'customers', 'taxCodes') + ['prefill' => $prefill, 'sales_order_id' => $order->id]);
    }
}
