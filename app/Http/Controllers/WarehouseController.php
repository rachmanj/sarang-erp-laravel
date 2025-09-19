<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use App\Services\WarehouseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WarehouseController extends Controller
{
    protected $warehouseService;

    public function __construct(WarehouseService $warehouseService)
    {
        $this->warehouseService = $warehouseService;
    }

    /**
     * Display a listing of warehouses.
     */
    public function index()
    {
        $warehouses = Warehouse::active()
            ->orderBy('name')
            ->paginate(20);

        return view('warehouses.index', compact('warehouses'));
    }

    /**
     * Show the form for creating a new warehouse.
     */
    public function create()
    {
        return view('warehouses.create');
    }

    /**
     * Store a newly created warehouse.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:warehouses,code'],
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        $data['is_active'] = $request->has('is_active');

        $warehouse = $this->warehouseService->createWarehouse($data);

        return redirect()->route('warehouses.show', $warehouse->id)
            ->with('success', 'Warehouse created successfully');
    }

    /**
     * Display the specified warehouse.
     */
    public function show(int $id)
    {
        $warehouse = Warehouse::findOrFail($id);
        $summary = $this->warehouseService->getWarehouseSummary($id);

        // Get audit trail
        $auditTrail = app(\App\Services\AuditLogService::class)->getAuditTrail('warehouse', $id);

        return view('warehouses.show', compact('warehouse', 'summary', 'auditTrail'));
    }

    /**
     * Show the form for editing the warehouse.
     */
    public function edit(int $id)
    {
        $warehouse = Warehouse::findOrFail($id);

        return view('warehouses.edit', compact('warehouse'));
    }

    /**
     * Update the specified warehouse.
     */
    public function update(Request $request, int $id)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:warehouses,code,' . $id],
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        $data['is_active'] = $request->has('is_active');

        $warehouse = $this->warehouseService->updateWarehouse($id, $data);

        return redirect()->route('warehouses.show', $warehouse->id)
            ->with('success', 'Warehouse updated successfully');
    }

    /**
     * Remove the specified warehouse.
     */
    public function destroy(int $id)
    {
        try {
            $this->warehouseService->deleteWarehouse($id);

            return redirect()->route('warehouses.index')
                ->with('success', 'Warehouse deleted successfully');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Get warehouses for AJAX requests.
     */
    public function getWarehouses()
    {
        return response()->json(
            Warehouse::active()
                ->select('id', 'code', 'name')
                ->orderBy('name')
                ->get()
        );
    }

    /**
     * Get warehouse stock for an item.
     */
    public function getItemStock(Request $request, int $itemId)
    {
        $warehouseId = $request->get('warehouse_id');
        $stock = $this->warehouseService->getItemStock($itemId, $warehouseId);

        return response()->json($stock);
    }

    /**
     * Transfer stock between warehouses.
     */
    public function transferStock(Request $request)
    {
        $data = $request->validate([
            'item_id' => ['required', 'integer', 'exists:inventory_items,id'],
            'from_warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'to_warehouse_id' => ['required', 'integer', 'exists:warehouses,id', 'different:from_warehouse_id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->warehouseService->transferStock(
                $data['item_id'],
                $data['from_warehouse_id'],
                $data['to_warehouse_id'],
                $data['quantity'],
                $data['notes']
            );

            return response()->json(['success' => true, 'message' => 'Stock transfer completed successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Get low stock items for a warehouse.
     */
    public function lowStock(int $warehouseId = null)
    {
        $warehouse = $warehouseId ? Warehouse::findOrFail($warehouseId) : null;
        $lowStockItems = $this->warehouseService->getLowStockItems($warehouseId);

        return view('warehouses.low-stock', compact('warehouse', 'lowStockItems'));
    }
}
