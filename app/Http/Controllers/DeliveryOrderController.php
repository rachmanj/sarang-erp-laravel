<?php

namespace App\Http\Controllers;

use App\Models\DeliveryOrder;
use App\Models\SalesOrder;
use App\Models\Master\Customer;
use App\Services\DeliveryService;
use App\Services\DocumentClosureService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DeliveryOrderController extends Controller
{
    protected $deliveryService;
    protected $documentClosureService;

    public function __construct(DeliveryService $deliveryService, DocumentClosureService $documentClosureService)
    {
        $this->deliveryService = $deliveryService;
        $this->documentClosureService = $documentClosureService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = DeliveryOrder::with(['customer', 'salesOrder', 'createdBy'])
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by customer
        if ($request->filled('business_partner_id')) {
            $query->where('business_partner_id', $request->business_partner_id);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->where('planned_delivery_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('planned_delivery_date', '<=', $request->date_to);
        }

        $deliveryOrders = $query->paginate(20);
        $customers = \App\Models\BusinessPartner::where('partner_type', 'customer')->orderBy('name')->get();

        return view('delivery_orders.index', compact('deliveryOrders', 'customers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $salesOrderId = $request->get('sales_order_id');
        $salesOrder = null;

        if ($salesOrderId) {
            $salesOrder = SalesOrder::with(['customer', 'lines'])->findOrFail($salesOrderId);

            if (!$this->deliveryService->canCreateDeliveryOrder($salesOrder)) {
                return redirect()->back()->with('error', 'Sales Order cannot be converted to Delivery Order');
            }
        }

        $salesOrders = SalesOrder::with('customer')
            ->where('approval_status', 'approved')
            ->where('status', 'confirmed')
            ->where('order_type', 'item')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('delivery_orders.create', compact('salesOrders', 'salesOrder'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'sales_order_id' => ['required', 'integer', 'exists:sales_orders,id'],
            'delivery_address' => ['required', 'string', 'max:500'],
            'delivery_contact_person' => ['nullable', 'string', 'max:100'],
            'delivery_phone' => ['nullable', 'string', 'max:20'],
            'planned_delivery_date' => ['required', 'date'],
            'delivery_method' => ['required', 'in:pickup,courier,own_fleet,customer_pickup'],
            'delivery_instructions' => ['nullable', 'string', 'max:1000'],
            'logistics_cost' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $deliveryOrder = $this->deliveryService->createDeliveryOrderFromSalesOrder(
                $data['sales_order_id'],
                $data
            );

            // Attempt to close the Sales Order if Delivery Order quantity is sufficient
            try {
                $this->documentClosureService->closeSalesOrder($data['sales_order_id'], $deliveryOrder->id, Auth::id());
            } catch (\Exception $closureException) {
                \Log::warning('Failed to close Sales Order after Delivery Order creation', [
                    'so_id' => $data['sales_order_id'],
                    'do_id' => $deliveryOrder->id,
                    'error' => $closureException->getMessage()
                ]);
            }

            return redirect()->route('delivery-orders.show', $deliveryOrder->id)
                ->with('success', 'Delivery Order created successfully');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(DeliveryOrder $deliveryOrder)
    {
        $deliveryOrder->load([
            'customer',
            'salesOrder',
            'lines.inventoryItem',
            'lines.account',
            'tracking',
            'createdBy',
            'approvedBy'
        ]);

        return view('delivery_orders.show', compact('deliveryOrder'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(DeliveryOrder $deliveryOrder)
    {
        if ($deliveryOrder->status !== 'draft') {
            return redirect()->route('delivery-orders.show', $deliveryOrder->id)
                ->with('error', 'Only draft delivery orders can be edited');
        }

        $deliveryOrder->load(['customer', 'salesOrder', 'lines']);

        return view('delivery_orders.edit', compact('deliveryOrder'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, DeliveryOrder $deliveryOrder)
    {
        if ($deliveryOrder->status !== 'draft') {
            return redirect()->route('delivery-orders.show', $deliveryOrder->id)
                ->with('error', 'Only draft delivery orders can be updated');
        }

        $data = $request->validate([
            'delivery_address' => ['required', 'string', 'max:500'],
            'delivery_contact_person' => ['nullable', 'string', 'max:100'],
            'delivery_phone' => ['nullable', 'string', 'max:20'],
            'planned_delivery_date' => ['required', 'date'],
            'delivery_method' => ['required', 'in:pickup,courier,own_fleet,customer_pickup'],
            'delivery_instructions' => ['nullable', 'string', 'max:1000'],
            'logistics_cost' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $deliveryOrder->update($data);

        return redirect()->route('delivery-orders.show', $deliveryOrder->id)
            ->with('success', 'Delivery Order updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DeliveryOrder $deliveryOrder)
    {
        if (!$deliveryOrder->canBeCancelled()) {
            return redirect()->back()
                ->with('error', 'Delivery Order cannot be deleted in current status');
        }

        try {
            $this->deliveryService->cancelDeliveryOrder($deliveryOrder->id, 'Deleted by user');

            return redirect()->route('delivery-orders.index')
                ->with('success', 'Delivery Order cancelled successfully');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Approve delivery order
     */
    public function approve(Request $request, DeliveryOrder $deliveryOrder)
    {
        $data = $request->validate([
            'comments' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->deliveryService->approveDeliveryOrder(
                $deliveryOrder->id,
                Auth::id(),
                $data['comments'] ?? null
            );

            return redirect()->route('delivery-orders.show', $deliveryOrder->id)
                ->with('success', 'Delivery Order approved successfully');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Reject delivery order
     */
    public function reject(Request $request, DeliveryOrder $deliveryOrder)
    {
        $data = $request->validate([
            'comments' => ['required', 'string', 'max:500'],
        ]);

        try {
            $this->deliveryService->rejectDeliveryOrder(
                $deliveryOrder->id,
                Auth::id(),
                $data['comments']
            );

            return redirect()->route('delivery-orders.show', $deliveryOrder->id)
                ->with('success', 'Delivery Order rejected successfully');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Update picking status for a line
     */
    public function updatePicking(Request $request, DeliveryOrder $deliveryOrder)
    {
        $data = $request->validate([
            'line_id' => ['required', 'integer', 'exists:delivery_order_lines,id'],
            'picked_qty' => ['required', 'numeric', 'min:0'],
        ]);

        try {
            $this->deliveryService->updatePickingStatus(
                $data['line_id'],
                $data['picked_qty']
            );

            return redirect()->route('delivery-orders.show', $deliveryOrder->id)
                ->with('success', 'Picking status updated successfully');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Update delivery status for a line
     */
    public function updateDelivery(Request $request, DeliveryOrder $deliveryOrder)
    {
        $data = $request->validate([
            'line_id' => ['required', 'integer', 'exists:delivery_order_lines,id'],
            'delivered_qty' => ['required', 'numeric', 'min:0'],
        ]);

        try {
            $this->deliveryService->updateDeliveryStatus(
                $data['line_id'],
                $data['delivered_qty']
            );

            return redirect()->route('delivery-orders.show', $deliveryOrder->id)
                ->with('success', 'Delivery status updated successfully');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Print delivery order
     */
    public function print(DeliveryOrder $deliveryOrder)
    {
        $deliveryOrder->load([
            'customer',
            'salesOrder',
            'lines.inventoryItem',
            'lines.account',
            'createdBy'
        ]);

        return view('delivery_orders.print', compact('deliveryOrder'));
    }

    /**
     * Complete delivery and create revenue recognition journal entry
     */
    public function completeDelivery(Request $request, DeliveryOrder $deliveryOrder)
    {
        $request->validate([
            'actual_delivery_date' => ['nullable', 'date'],
        ]);

        try {
            $this->deliveryService->completeDelivery(
                $deliveryOrder->id,
                $request->input('actual_delivery_date')
            );

            return back()->with('success', 'Delivery completed and revenue recognized successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to complete delivery: ' . $e->getMessage());
        }
    }
}
