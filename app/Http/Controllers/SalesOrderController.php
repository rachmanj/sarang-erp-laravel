<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use App\Models\SalesOrderApproval;
use App\Models\InventoryItem;
use App\Models\CustomerCreditLimit;
use App\Models\CustomerPricingTier;
use App\Services\SalesService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class SalesOrderController extends Controller
{
    protected $salesService;

    public function __construct(SalesService $salesService)
    {
        $this->salesService = $salesService;
    }

    public function index()
    {
        return view('sales_orders.index');
    }

    public function create()
    {
        $customers = DB::table('customers')->orderBy('name')->get();
        $accounts = DB::table('accounts')->where('is_postable', 1)->orderBy('code')->get();
        $taxCodes = DB::table('tax_codes')->orderBy('code')->get();
        $inventoryItems = InventoryItem::active()->orderBy('name')->get();

        return view('sales_orders.create', compact('customers', 'accounts', 'taxCodes', 'inventoryItems'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'expected_delivery_date' => ['nullable', 'date'],
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'description' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'terms_conditions' => ['nullable', 'string'],
            'payment_terms' => ['nullable', 'string', 'max:100'],
            'delivery_method' => ['nullable', 'string', 'max:100'],
            'freight_cost' => ['nullable', 'numeric', 'min:0'],
            'handling_cost' => ['nullable', 'numeric', 'min:0'],
            'insurance_cost' => ['nullable', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'discount_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.account_id' => ['required', 'integer', 'exists:accounts,id'],
            'lines.*.inventory_item_id' => ['nullable', 'integer', 'exists:inventory_items,id'],
            'lines.*.item_code' => ['nullable', 'string', 'max:50'],
            'lines.*.item_name' => ['nullable', 'string', 'max:255'],
            'lines.*.unit_of_measure' => ['nullable', 'string', 'max:50'],
            'lines.*.description' => ['nullable', 'string', 'max:255'],
            'lines.*.qty' => ['required', 'numeric', 'min:0.01'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.freight_cost' => ['nullable', 'numeric', 'min:0'],
            'lines.*.handling_cost' => ['nullable', 'numeric', 'min:0'],
            'lines.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.discount_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.tax_code_id' => ['nullable', 'integer', 'exists:tax_codes,id'],
            'lines.*.notes' => ['nullable', 'string'],
        ]);

        try {
            $so = $this->salesService->createSalesOrder($data);
            return redirect()->route('sales-orders.show', $so->id)
                ->with('success', 'Sales Order created successfully');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Error creating sales order: ' . $e->getMessage());
        }
    }

    public function show(int $id)
    {
        $order = SalesOrder::with(['lines', 'customer', 'approvals.user', 'approvedBy', 'createdBy', 'commissions'])
            ->findOrFail($id);
        return view('sales_orders.show', compact('order'));
    }

    public function approve(int $id, Request $request)
    {
        $request->validate([
            'comments' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->salesService->approveSalesOrder($id, Auth::id(), $request->comments);
            return back()->with('success', 'Sales Order approved successfully');
        } catch (\Exception $e) {
            return back()->with('error', 'Error approving sales order: ' . $e->getMessage());
        }
    }

    public function reject(int $id, Request $request)
    {
        $request->validate([
            'comments' => ['required', 'string', 'max:500'],
        ]);

        try {
            $this->salesService->rejectSalesOrder($id, Auth::id(), $request->comments);
            return back()->with('success', 'Sales Order rejected');
        } catch (\Exception $e) {
            return back()->with('error', 'Error rejecting sales order: ' . $e->getMessage());
        }
    }

    public function confirm(int $id)
    {
        try {
            $this->salesService->confirmSalesOrder($id);
            return back()->with('success', 'Sales Order confirmed successfully');
        } catch (\Exception $e) {
            return back()->with('error', 'Error confirming sales order: ' . $e->getMessage());
        }
    }

    public function deliver(int $id)
    {
        $order = SalesOrder::with('lines')->findOrFail($id);
        return view('sales_orders.deliver', compact('order'));
    }

    public function processDeliver(int $id, Request $request)
    {
        $data = $request->validate([
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.line_id' => ['required', 'integer', 'exists:sales_order_lines,id'],
            'lines.*.delivered_qty' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        try {
            $this->salesService->deliverSalesOrder($id, $data);
            return redirect()->route('sales-orders.show', $id)
                ->with('success', 'Sales Order delivered successfully');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Error delivering sales order: ' . $e->getMessage());
        }
    }

    public function close(int $id)
    {
        try {
            $this->salesService->closeSalesOrder($id);
            return back()->with('success', 'Sales Order closed successfully');
        } catch (\Exception $e) {
            return back()->with('error', 'Error closing sales order: ' . $e->getMessage());
        }
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

    public function checkCreditLimit(Request $request)
    {
        $request->validate([
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'order_amount' => ['required', 'numeric', 'min:0'],
        ]);

        try {
            $this->salesService->checkCreditLimit($request->customer_id, $request->order_amount);
            return response()->json(['status' => 'approved', 'message' => 'Credit limit check passed']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'rejected', 'message' => $e->getMessage()], 400);
        }
    }

    public function getCustomerPricingTier(Request $request)
    {
        $request->validate([
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'order_amount' => ['required', 'numeric', 'min:0'],
        ]);

        $pricingTier = CustomerPricingTier::where('customer_id', $request->customer_id)
            ->where('is_active', true)
            ->where('min_order_amount', '<=', $request->order_amount)
            ->orderBy('min_order_amount', 'desc')
            ->first();

        if ($pricingTier) {
            return response()->json([
                'tier_name' => $pricingTier->tier_name,
                'discount_percentage' => $pricingTier->discount_percentage,
                'discount_amount' => ($request->order_amount * $pricingTier->discount_percentage) / 100,
                'net_amount' => $request->order_amount - (($request->order_amount * $pricingTier->discount_percentage) / 100),
            ]);
        }

        return response()->json(['message' => 'No pricing tier applicable']);
    }

    public function analyzeCustomerProfitability(Request $request)
    {
        $request->validate([
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
        ]);

        try {
            $analysis = $this->salesService->analyzeCustomerProfitability(
                $request->customer_id,
                $request->start_date,
                $request->end_date
            );
            return response()->json($analysis);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function getInventoryItems()
    {
        return response()->json(
            InventoryItem::active()
                ->select('id', 'code', 'name', 'unit_of_measure', 'selling_price', 'current_stock')
                ->orderBy('name')
                ->get()
        );
    }

    public function getItemDetails(int $id)
    {
        $item = InventoryItem::findOrFail($id);

        return response()->json([
            'id' => $item->id,
            'code' => $item->code,
            'name' => $item->name,
            'unit_of_measure' => $item->unit_of_measure,
            'selling_price' => $item->selling_price,
            'current_stock' => $item->current_stock,
            'min_stock_level' => $item->min_stock_level,
        ]);
    }

    public function getCustomerCreditInfo(int $customerId)
    {
        $creditLimit = CustomerCreditLimit::where('customer_id', $customerId)->first();

        if (!$creditLimit) {
            return response()->json(['message' => 'No credit limit set for this customer']);
        }

        return response()->json([
            'credit_limit' => $creditLimit->credit_limit,
            'current_balance' => $creditLimit->current_balance,
            'available_credit' => $creditLimit->available_credit,
            'credit_status' => $creditLimit->credit_status,
            'credit_utilization' => $creditLimit->credit_utilization,
        ]);
    }
}
