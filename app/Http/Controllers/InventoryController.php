<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\InventoryValuation;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class InventoryController extends Controller
{
    public function index()
    {
        $items = InventoryItem::with('category')
            ->active()
            ->orderBy('name')
            ->paginate(20);

        return view('inventory.index', compact('items'));
    }

    public function create()
    {
        $categories = ProductCategory::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('inventory.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:inventory_items,code'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['required', 'integer', 'exists:product_categories,id'],
            'unit_of_measure' => ['required', 'string', 'max:50'],
            'purchase_price' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'min_stock_level' => ['required', 'integer', 'min:0'],
            'max_stock_level' => ['required', 'integer', 'min:0'],
            'reorder_point' => ['required', 'integer', 'min:0'],
            'valuation_method' => ['required', 'in:fifo,lifo,weighted_average'],
            'is_active' => ['boolean'],
        ]);

        $data['is_active'] = $request->has('is_active');

        return DB::transaction(function () use ($data, $request) {
            $item = InventoryItem::create($data);

            // Create initial stock transaction if initial stock is provided
            if ($request->has('initial_stock') && $request->initial_stock > 0) {
                $initialStock = $request->initial_stock;
                InventoryTransaction::create([
                    'item_id' => $item->id,
                    'transaction_type' => 'adjustment',
                    'quantity' => $initialStock,
                    'unit_cost' => $data['purchase_price'],
                    'total_cost' => $initialStock * $data['purchase_price'],
                    'reference_type' => 'initial_stock',
                    'reference_id' => null,
                    'transaction_date' => now()->toDateString(),
                    'notes' => 'Initial stock entry',
                    'created_by' => Auth::id(),
                ]);

                // Create initial valuation
                InventoryValuation::create([
                    'item_id' => $item->id,
                    'valuation_date' => now()->toDateString(),
                    'quantity_on_hand' => $initialStock,
                    'unit_cost' => $data['purchase_price'],
                    'total_value' => $initialStock * $data['purchase_price'],
                    'valuation_method' => $data['valuation_method'],
                ]);
            }

            return redirect()->route('inventory.show', $item->id)
                ->with('success', 'Inventory item created successfully');
        });
    }

    public function show(int $id)
    {
        $item = InventoryItem::with(['category', 'transactions', 'valuations'])
            ->findOrFail($id);

        $transactions = $item->transactions()
            ->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $valuations = $item->valuations()
            ->orderBy('valuation_date', 'desc')
            ->paginate(10);

        return view('inventory.show', compact('item', 'transactions', 'valuations'));
    }

    public function edit(int $id)
    {
        $item = InventoryItem::findOrFail($id);
        $categories = ProductCategory::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('inventory.edit', compact('item', 'categories'));
    }

    public function update(Request $request, int $id)
    {
        $item = InventoryItem::findOrFail($id);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:inventory_items,code,' . $id],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['required', 'integer', 'exists:product_categories,id'],
            'unit_of_measure' => ['required', 'string', 'max:50'],
            'purchase_price' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'min_stock_level' => ['required', 'integer', 'min:0'],
            'max_stock_level' => ['required', 'integer', 'min:0'],
            'reorder_point' => ['required', 'integer', 'min:0'],
            'valuation_method' => ['required', 'in:fifo,lifo,weighted_average'],
            'is_active' => ['boolean'],
        ]);

        $data['is_active'] = $request->has('is_active');

        $item->update($data);

        return redirect()->route('inventory.show', $item->id)
            ->with('success', 'Inventory item updated successfully');
    }

    public function destroy(int $id)
    {
        $item = InventoryItem::findOrFail($id);

        // Check if item has transactions
        if ($item->transactions()->count() > 0) {
            return back()->with('error', 'Cannot delete inventory item with existing transactions');
        }

        $item->delete();

        return redirect()->route('inventory.index')
            ->with('success', 'Inventory item deleted successfully');
    }

    public function adjustStock(Request $request, int $id)
    {
        $item = InventoryItem::findOrFail($id);

        $data = $request->validate([
            'adjustment_type' => ['required', 'in:increase,decrease'],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit_cost' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        return DB::transaction(function () use ($data, $item) {
            $quantity = $data['adjustment_type'] === 'increase'
                ? $data['quantity']
                : -$data['quantity'];

            $totalCost = $quantity * $data['unit_cost'];

            // Create adjustment transaction
            InventoryTransaction::create([
                'item_id' => $item->id,
                'transaction_type' => 'adjustment',
                'quantity' => $quantity,
                'unit_cost' => $data['unit_cost'],
                'total_cost' => $totalCost,
                'reference_type' => 'stock_adjustment',
                'reference_id' => null,
                'transaction_date' => now()->toDateString(),
                'notes' => $data['notes'] ?? 'Stock adjustment',
                'created_by' => Auth::id(),
            ]);

            // Update valuation
            $this->updateValuation($item);

            return back()->with('success', 'Stock adjustment recorded successfully');
        });
    }

    public function transferStock(Request $request, int $id)
    {
        $item = InventoryItem::findOrFail($id);

        $data = $request->validate([
            'to_item_id' => ['required', 'integer', 'exists:inventory_items,id', 'different:' . $id],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit_cost' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        return DB::transaction(function () use ($data, $item) {
            $totalCost = $data['quantity'] * $data['unit_cost'];

            // Create outgoing transaction
            InventoryTransaction::create([
                'item_id' => $item->id,
                'transaction_type' => 'transfer',
                'quantity' => -$data['quantity'],
                'unit_cost' => $data['unit_cost'],
                'total_cost' => -$totalCost,
                'reference_type' => 'stock_transfer',
                'reference_id' => $data['to_item_id'],
                'transaction_date' => now()->toDateString(),
                'notes' => 'Transfer to ' . ($data['notes'] ?? 'another item'),
                'created_by' => Auth::id(),
            ]);

            // Create incoming transaction
            InventoryTransaction::create([
                'item_id' => $data['to_item_id'],
                'transaction_type' => 'transfer',
                'quantity' => $data['quantity'],
                'unit_cost' => $data['unit_cost'],
                'total_cost' => $totalCost,
                'reference_type' => 'stock_transfer',
                'reference_id' => $item->id,
                'transaction_date' => now()->toDateString(),
                'notes' => 'Transfer from ' . $item->name,
                'created_by' => Auth::id(),
            ]);

            // Update valuations for both items
            $this->updateValuation($item);
            $this->updateValuation(InventoryItem::find($data['to_item_id']));

            return back()->with('success', 'Stock transfer completed successfully');
        });
    }

    public function lowStock()
    {
        $items = InventoryItem::with('category')
            ->active()
            ->lowStock()
            ->orderBy('name')
            ->get();

        return view('inventory.low-stock', compact('items'));
    }

    public function valuationReport()
    {
        $items = InventoryItem::with(['category', 'valuations'])
            ->active()
            ->get()
            ->map(function ($item) {
                $latestValuation = $item->valuations()
                    ->orderBy('valuation_date', 'desc')
                    ->first();

                return [
                    'item' => $item,
                    'current_stock' => $item->current_stock,
                    'current_value' => $item->current_value,
                    'latest_valuation' => $latestValuation,
                ];
            });

        return view('inventory.valuation-report', compact('items'));
    }

    public function getItems()
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
        $item = InventoryItem::with('category')->findOrFail($id);

        return response()->json([
            'id' => $item->id,
            'code' => $item->code,
            'name' => $item->name,
            'description' => $item->description,
            'category' => $item->category->name ?? 'N/A',
            'unit_of_measure' => $item->unit_of_measure,
            'purchase_price' => $item->purchase_price,
            'selling_price' => $item->selling_price,
            'current_stock' => $item->current_stock,
            'min_stock_level' => $item->min_stock_level,
            'max_stock_level' => $item->max_stock_level,
            'reorder_point' => $item->reorder_point,
            'valuation_method' => $item->valuation_method,
        ]);
    }

    public function data(Request $request)
    {
        $query = InventoryItem::with('category');

        // Apply filters
        if ($request->filled('q')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->q . '%')
                    ->orWhere('code', 'like', '%' . $request->q . '%');
            });
        }

        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        if ($request->filled('valuation_method')) {
            $query->where('valuation_method', $request->valuation_method);
        }

        if ($request->filled('stock_status')) {
            switch ($request->stock_status) {
                case 'low':
                    $query->whereRaw('current_stock <= min_stock_level');
                    break;
                case 'out':
                    $query->whereRaw('current_stock <= 0');
                    break;
                case 'available':
                    $query->whereRaw('current_stock > min_stock_level');
                    break;
            }
        }

        $items = $query->orderBy('name')->get();

        return response()->json([
            'data' => $items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'code' => $item->code,
                    'name' => $item->name,
                    'category' => $item->category->name ?? 'N/A',
                    'unit_of_measure' => $item->unit_of_measure,
                    'purchase_price' => $item->purchase_price,
                    'selling_price' => $item->selling_price,
                    'current_stock' => $item->current_stock,
                    'min_stock_level' => $item->min_stock_level,
                    'is_active' => $item->is_active,
                    'actions' => view('inventory.partials.actions', compact('item'))->render(),
                ];
            })
        ]);
    }

    public function export(Request $request)
    {
        // This would typically generate an Excel/CSV export
        // For now, return a simple response
        return response()->json(['message' => 'Export functionality will be implemented']);
    }

    public function exportLowStock()
    {
        // This would typically generate an Excel/CSV export for low stock items
        // For now, return a simple response
        return response()->json(['message' => 'Low stock export functionality will be implemented']);
    }

    public function exportValuation()
    {
        // This would typically generate an Excel/CSV export for valuation report
        // For now, return a simple response
        return response()->json(['message' => 'Valuation export functionality will be implemented']);
    }

    private function updateValuation(InventoryItem $item)
    {
        $currentStock = $item->current_stock;
        $latestValuation = $item->valuations()
            ->orderBy('valuation_date', 'desc')
            ->first();

        if ($latestValuation && $latestValuation->quantity_on_hand == $currentStock) {
            return; // No change in stock
        }

        // Calculate new valuation based on method
        $unitCost = $this->calculateUnitCost($item);
        $totalValue = $currentStock * $unitCost;

        InventoryValuation::create([
            'item_id' => $item->id,
            'valuation_date' => now()->toDateString(),
            'quantity_on_hand' => $currentStock,
            'unit_cost' => $unitCost,
            'total_value' => $totalValue,
            'valuation_method' => $item->valuation_method,
        ]);
    }

    private function calculateUnitCost(InventoryItem $item)
    {
        $transactions = $item->transactions()
            ->where('transaction_type', 'purchase')
            ->orderBy('transaction_date', 'asc')
            ->get();

        if ($transactions->isEmpty()) {
            return $item->purchase_price;
        }

        switch ($item->valuation_method) {
            case 'fifo':
                return $this->calculateFIFOCost($transactions);
            case 'lifo':
                return $this->calculateLIFOCost($transactions);
            case 'weighted_average':
                return $this->calculateWeightedAverageCost($transactions);
            default:
                return $item->purchase_price;
        }
    }

    private function calculateFIFOCost($transactions)
    {
        $totalCost = 0;
        $totalQuantity = 0;

        foreach ($transactions as $transaction) {
            $totalCost += $transaction->total_cost;
            $totalQuantity += $transaction->quantity;
        }

        return $totalQuantity > 0 ? $totalCost / $totalQuantity : 0;
    }

    private function calculateLIFOCost($transactions)
    {
        $remainingStock = $transactions->sum('quantity');
        $totalCost = 0;

        foreach ($transactions->reverse() as $transaction) {
            if ($remainingStock <= 0) break;

            $quantityToUse = min($remainingStock, $transaction->quantity);
            $totalCost += $quantityToUse * $transaction->unit_cost;
            $remainingStock -= $quantityToUse;
        }

        return $remainingStock > 0 ? $totalCost / $remainingStock : 0;
    }

    private function calculateWeightedAverageCost($transactions)
    {
        $totalCost = 0;
        $totalQuantity = 0;

        foreach ($transactions as $transaction) {
            $totalCost += $transaction->total_cost;
            $totalQuantity += $transaction->quantity;
        }

        return $totalQuantity > 0 ? $totalCost / $totalQuantity : 0;
    }
}
