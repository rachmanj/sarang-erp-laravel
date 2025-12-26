<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\InventoryValuation;
use App\Models\InventoryWarehouseStock;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class InventoryService
{
    public function processPurchaseTransaction(int $itemId, int $quantity, float $unitCost, string $referenceType = null, int $referenceId = null, string $notes = null, int $warehouseId = null)
    {
        return DB::transaction(function () use ($itemId, $quantity, $unitCost, $referenceType, $referenceId, $notes, $warehouseId) {
            $item = InventoryItem::findOrFail($itemId);
            $totalCost = $quantity * $unitCost;

            // Use default warehouse if not specified
            if (!$warehouseId) {
                $warehouseId = $item->default_warehouse_id;
            }

            // Create purchase transaction
            $transaction = InventoryTransaction::create([
                'item_id' => $itemId,
                'warehouse_id' => $warehouseId,
                'transaction_type' => 'purchase',
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'total_cost' => $totalCost,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'transaction_date' => now()->toDateString(),
                'notes' => $notes ?? 'Purchase transaction',
                'created_by' => Auth::id(),
            ]);

            // Update warehouse stock
            if ($warehouseId) {
                $this->updateWarehouseStock($itemId, $warehouseId, $quantity);
            }

            // Update valuation
            $this->updateItemValuation($item);

            // Log the transaction
            app(AuditLogService::class)->logInventoryTransaction(
                'created',
                $transaction->id,
                null,
                $transaction->getAttributes(),
                "Purchase transaction: {$quantity} units at {$unitCost}"
            );

            return $transaction;
        });
    }

    public function processSaleTransaction(int $itemId, int $quantity, float $unitCost, string $referenceType = null, int $referenceId = null, string $notes = null)
    {
        return DB::transaction(function () use ($itemId, $quantity, $unitCost, $referenceType, $referenceId, $notes) {
            $item = InventoryItem::findOrFail($itemId);

            // Check stock availability
            if ($item->current_stock < $quantity) {
                throw new \Exception("Insufficient stock. Available: {$item->current_stock}, Required: {$quantity}");
            }

            $totalCost = $quantity * $unitCost;

            // Create sale transaction
            $transaction = InventoryTransaction::create([
                'item_id' => $itemId,
                'transaction_type' => 'sale',
                'quantity' => -$quantity,
                'unit_cost' => $unitCost,
                'total_cost' => -$totalCost,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'transaction_date' => now()->toDateString(),
                'notes' => $notes ?? 'Sale transaction',
                'created_by' => Auth::id(),
            ]);

            // Update valuation
            $this->updateItemValuation($item);

            return $transaction;
        });
    }

    public function processAdjustmentTransaction(int $itemId, int $quantity, float $unitCost, string $notes = null)
    {
        return DB::transaction(function () use ($itemId, $quantity, $unitCost, $notes) {
            $item = InventoryItem::findOrFail($itemId);
            $totalCost = $quantity * $unitCost;

            // Create adjustment transaction
            $transaction = InventoryTransaction::create([
                'item_id' => $itemId,
                'transaction_type' => 'adjustment',
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'total_cost' => $totalCost,
                'reference_type' => 'stock_adjustment',
                'reference_id' => null,
                'transaction_date' => now()->toDateString(),
                'notes' => $notes ?? 'Stock adjustment',
                'created_by' => Auth::id(),
            ]);

            // Update valuation
            $this->updateItemValuation($item);

            return $transaction;
        });
    }

    public function processTransferTransaction(int $fromItemId, int $toItemId, int $quantity, float $unitCost, string $notes = null)
    {
        return DB::transaction(function () use ($fromItemId, $toItemId, $quantity, $unitCost, $notes) {
            $fromItem = InventoryItem::findOrFail($fromItemId);
            $toItem = InventoryItem::findOrFail($toItemId);

            // Check stock availability
            if ($fromItem->current_stock < $quantity) {
                throw new \Exception("Insufficient stock in source item. Available: {$fromItem->current_stock}, Required: {$quantity}");
            }

            $totalCost = $quantity * $unitCost;

            // Create outgoing transaction
            InventoryTransaction::create([
                'item_id' => $fromItemId,
                'transaction_type' => 'transfer',
                'quantity' => -$quantity,
                'unit_cost' => $unitCost,
                'total_cost' => -$totalCost,
                'reference_type' => 'stock_transfer',
                'reference_id' => $toItemId,
                'transaction_date' => now()->toDateString(),
                'notes' => $notes ?? "Transfer to {$toItem->name}",
                'created_by' => Auth::id(),
            ]);

            // Create incoming transaction
            InventoryTransaction::create([
                'item_id' => $toItemId,
                'transaction_type' => 'transfer',
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'total_cost' => $totalCost,
                'reference_type' => 'stock_transfer',
                'reference_id' => $fromItemId,
                'transaction_date' => now()->toDateString(),
                'notes' => $notes ?? "Transfer from {$fromItem->name}",
                'created_by' => Auth::id(),
            ]);

            // Update valuations for both items
            $this->updateItemValuation($fromItem);
            $this->updateItemValuation($toItem);

            return true;
        });
    }

    public function updateItemValuation(InventoryItem $item)
    {
        $currentStock = $item->current_stock;
        $valuationDate = now()->toDateString();
        
        // Check if valuation already exists for today
        $existingValuation = InventoryValuation::where('item_id', $item->id)
            ->where('valuation_date', $valuationDate)
            ->first();

        // Skip if valuation exists and stock hasn't changed
        if ($existingValuation && $existingValuation->quantity_on_hand == $currentStock) {
            return $existingValuation;
        }

        // Calculate new unit cost based on valuation method
        $unitCost = $this->calculateUnitCost($item);
        $totalValue = $currentStock * $unitCost;

        // Use updateOrCreate to handle duplicate entries gracefully
        return InventoryValuation::updateOrCreate(
            [
                'item_id' => $item->id,
                'valuation_date' => $valuationDate,
            ],
            [
                'quantity_on_hand' => $currentStock,
                'unit_cost' => $unitCost,
                'total_value' => $totalValue,
                'valuation_method' => $item->valuation_method,
            ]
        );
    }

    public function calculateUnitCost(InventoryItem $item)
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

    public function getLowStockItems()
    {
        return InventoryItem::with('category')
            ->active()
            ->lowStock()
            ->orderBy('name')
            ->get();
    }

    public function getInventoryValuationReport()
    {
        return InventoryItem::with(['category', 'valuations'])
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
    }

    public function checkReorderPoints()
    {
        $lowStockItems = $this->getLowStockItems();

        return $lowStockItems->map(function ($item) {
            return [
                'item' => $item,
                'current_stock' => $item->current_stock,
                'reorder_point' => $item->reorder_point,
                'shortage' => $item->reorder_point - $item->current_stock,
            ];
        });
    }

    public function generateInventoryReport(string $startDate = null, string $endDate = null)
    {
        $startDate = $startDate ?? now()->startOfMonth()->toDateString();
        $endDate = $endDate ?? now()->endOfMonth()->toDateString();

        $items = InventoryItem::with(['category', 'transactions'])
            ->active()
            ->get()
            ->map(function ($item) use ($startDate, $endDate) {
                $transactions = $item->transactions()
                    ->whereBetween('transaction_date', [$startDate, $endDate])
                    ->get();

                $purchases = $transactions->where('transaction_type', 'purchase');
                $sales = $transactions->where('transaction_type', 'sale');
                $adjustments = $transactions->where('transaction_type', 'adjustment');

                return [
                    'item' => $item,
                    'opening_stock' => $this->getOpeningStock($item, $startDate),
                    'purchases' => $purchases->sum('quantity'),
                    'sales' => abs($sales->sum('quantity')),
                    'adjustments' => $adjustments->sum('quantity'),
                    'closing_stock' => $item->current_stock,
                    'total_purchase_value' => $purchases->sum('total_cost'),
                    'total_sale_value' => abs($sales->sum('total_cost')),
                ];
            });

        return $items;
    }

    private function getOpeningStock(InventoryItem $item, string $startDate)
    {
        $openingTransactions = $item->transactions()
            ->where('transaction_date', '<', $startDate)
            ->get();

        $openingIn = $openingTransactions->where('transaction_type', 'purchase')->sum('quantity');
        $openingOut = $openingTransactions->whereIn('transaction_type', ['sale', 'adjustment'])->sum('quantity');

        return $openingIn - $openingOut;
    }

    /**
     * Update warehouse stock for an item
     */
    private function updateWarehouseStock(int $itemId, int $warehouseId, int $quantityChange)
    {
        $warehouseStock = InventoryWarehouseStock::firstOrCreate(
            ['item_id' => $itemId, 'warehouse_id' => $warehouseId],
            [
                'quantity_on_hand' => 0,
                'reserved_quantity' => 0,
                'available_quantity' => 0,
                'min_stock_level' => 0,
                'max_stock_level' => 0,
                'reorder_point' => 0,
            ]
        );

        $warehouseStock->quantity_on_hand += $quantityChange;
        $warehouseStock->updateAvailableQuantity();
        $warehouseStock->save();

        return $warehouseStock;
    }
}
