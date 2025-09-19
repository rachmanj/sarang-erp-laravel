<?php

namespace App\Services;

use App\Models\Warehouse;
use App\Models\InventoryItem;
use App\Models\InventoryWarehouseStock;
use App\Models\InventoryTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class WarehouseService
{
    /**
     * Create a new warehouse
     */
    public function createWarehouse($data)
    {
        return DB::transaction(function () use ($data) {
            $warehouse = Warehouse::create($data);

            // Log the creation
            app(AuditLogService::class)->logWarehouse(
                'created',
                $warehouse->id,
                null,
                $warehouse->getAttributes(),
                "Warehouse '{$warehouse->name}' created"
            );

            return $warehouse;
        });
    }

    /**
     * Update warehouse
     */
    public function updateWarehouse($warehouseId, $data)
    {
        return DB::transaction(function () use ($warehouseId, $data) {
            $warehouse = Warehouse::findOrFail($warehouseId);
            $oldValues = $warehouse->getAttributes();

            $warehouse->update($data);

            // Log the update
            app(AuditLogService::class)->logWarehouse(
                'updated',
                $warehouse->id,
                $oldValues,
                $warehouse->getAttributes(),
                "Warehouse '{$warehouse->name}' updated"
            );

            return $warehouse;
        });
    }

    /**
     * Delete warehouse
     */
    public function deleteWarehouse($warehouseId)
    {
        return DB::transaction(function () use ($warehouseId) {
            $warehouse = Warehouse::findOrFail($warehouseId);

            // Check if warehouse has stock
            $hasStock = InventoryWarehouseStock::where('warehouse_id', $warehouseId)
                ->where('quantity_on_hand', '>', 0)
                ->exists();

            if ($hasStock) {
                throw new \Exception('Cannot delete warehouse with existing stock');
            }

            $oldValues = $warehouse->getAttributes();
            $warehouse->delete();

            // Log the deletion
            app(AuditLogService::class)->logWarehouse(
                'deleted',
                $warehouseId,
                $oldValues,
                null,
                "Warehouse '{$oldValues['name']}' deleted"
            );

            return true;
        });
    }

    /**
     * Get warehouse stock for an item
     */
    public function getItemStock($itemId, $warehouseId = null)
    {
        $query = InventoryWarehouseStock::where('item_id', $itemId);

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        return $query->with('warehouse')->get();
    }

    /**
     * Update warehouse stock
     */
    public function updateWarehouseStock($itemId, $warehouseId, $quantityChange, $transactionType = 'adjustment', $referenceType = null, $referenceId = null, $notes = null)
    {
        return DB::transaction(function () use ($itemId, $warehouseId, $quantityChange, $transactionType, $referenceType, $referenceId, $notes) {
            // Get or create warehouse stock record
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

            $oldQuantity = $warehouseStock->quantity_on_hand;
            $warehouseStock->quantity_on_hand += $quantityChange;
            $warehouseStock->updateAvailableQuantity();
            $warehouseStock->save();

            // Create inventory transaction
            $transaction = InventoryTransaction::create([
                'item_id' => $itemId,
                'warehouse_id' => $warehouseId,
                'transaction_type' => $transactionType,
                'quantity' => $quantityChange,
                'unit_cost' => 0, // Will be updated by inventory service
                'total_cost' => 0,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'transaction_date' => now()->toDateString(),
                'notes' => $notes ?? "Stock {$transactionType}",
                'created_by' => Auth::id(),
            ]);

            // Log the transaction
            app(AuditLogService::class)->logInventoryTransaction(
                'created',
                $transaction->id,
                null,
                $transaction->getAttributes(),
                "Stock {$transactionType}: {$quantityChange} units in warehouse {$warehouseId}"
            );

            return $warehouseStock;
        });
    }

    /**
     * Transfer stock between warehouses
     */
    public function transferStock($itemId, $fromWarehouseId, $toWarehouseId, $quantity, $notes = null)
    {
        return DB::transaction(function () use ($itemId, $fromWarehouseId, $toWarehouseId, $quantity, $notes) {
            // Check if source warehouse has enough stock
            $fromStock = InventoryWarehouseStock::where('item_id', $itemId)
                ->where('warehouse_id', $fromWarehouseId)
                ->first();

            if (!$fromStock || $fromStock->quantity_on_hand < $quantity) {
                throw new \Exception('Insufficient stock in source warehouse');
            }

            // Update source warehouse stock
            $this->updateWarehouseStock($itemId, $fromWarehouseId, -$quantity, 'transfer', 'warehouse_transfer', $toWarehouseId, "Transfer to warehouse {$toWarehouseId}");

            // Update destination warehouse stock
            $this->updateWarehouseStock($itemId, $toWarehouseId, $quantity, 'transfer', 'warehouse_transfer', $fromWarehouseId, "Transfer from warehouse {$fromWarehouseId}");

            return true;
        });
    }

    /**
     * Get low stock items by warehouse
     */
    public function getLowStockItems($warehouseId = null)
    {
        $query = InventoryWarehouseStock::with(['item', 'warehouse'])
            ->whereRaw('quantity_on_hand <= reorder_point');

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        return $query->get();
    }

    /**
     * Get warehouse summary
     */
    public function getWarehouseSummary($warehouseId)
    {
        $warehouse = Warehouse::findOrFail($warehouseId);

        $stockSummary = InventoryWarehouseStock::where('warehouse_id', $warehouseId)
            ->selectRaw('
                COUNT(*) as total_items,
                SUM(quantity_on_hand) as total_quantity,
                SUM(reserved_quantity) as total_reserved,
                SUM(available_quantity) as total_available,
                COUNT(CASE WHEN quantity_on_hand <= reorder_point THEN 1 END) as low_stock_items
            ')
            ->first();

        return [
            'warehouse' => $warehouse,
            'stock_summary' => $stockSummary,
        ];
    }
}
