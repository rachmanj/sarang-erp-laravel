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
    public function updateWarehouseStock($itemId, $warehouseId, $quantityChange, $transactionType = 'adjustment', $referenceType = null, $referenceId = null, $notes = null, $transferStatus = 'pending', $transferOutId = null, $transferInId = null)
    {
        return DB::transaction(function () use ($itemId, $warehouseId, $quantityChange, $transactionType, $referenceType, $referenceId, $notes, $transferStatus, $transferOutId, $transferInId) {
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
                'transfer_status' => $transferStatus,
                'transfer_out_id' => $transferOutId,
                'transfer_in_id' => $transferInId,
                'transfer_notes' => $notes,
                'transit_date' => $transferStatus === 'in_transit' ? now() : null,
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
            $this->updateWarehouseStock($itemId, $fromWarehouseId, -$quantity, 'transfer', 'warehouse_transfer', $toWarehouseId, "Transfer to warehouse {$toWarehouseId}", 'completed');

            // Update destination warehouse stock
            $this->updateWarehouseStock($itemId, $toWarehouseId, $quantity, 'transfer', 'warehouse_transfer', $fromWarehouseId, "Transfer from warehouse {$fromWarehouseId}", 'completed');

            return true;
        });
    }

    /**
     * Create Inventory Transfer Out (ITO) - Move items to transit warehouse.
     */
    public function createTransferOut($itemId, $fromWarehouseId, $toWarehouseId, $quantity, $notes = null)
    {
        return DB::transaction(function () use ($itemId, $fromWarehouseId, $toWarehouseId, $quantity, $notes) {
            // Get source warehouse and its transit warehouse
            $fromWarehouse = Warehouse::findOrFail($fromWarehouseId);
            $transitWarehouse = $fromWarehouse->getTransitWarehouse();
            
            if (!$transitWarehouse) {
                throw new \Exception('Transit warehouse not found for source warehouse');
            }

            // Check if source warehouse has enough stock
            $fromStock = InventoryWarehouseStock::where('item_id', $itemId)
                ->where('warehouse_id', $fromWarehouseId)
                ->first();

            if (!$fromStock || $fromStock->quantity_on_hand < $quantity) {
                throw new \Exception('Insufficient stock in source warehouse');
            }

            // Create transfer out transaction (source -> transit)
            $transferOutId = $this->updateWarehouseStock(
                $itemId, 
                $fromWarehouseId, 
                -$quantity, 
                'transfer', 
                'warehouse_transfer_out', 
                $toWarehouseId, 
                "ITO: Transfer to {$toWarehouseId}", 
                'in_transit'
            );

            // Create transit transaction (transit warehouse receives)
            $this->updateWarehouseStock(
                $itemId, 
                $transitWarehouse->id, 
                $quantity, 
                'transfer', 
                'warehouse_transfer_out', 
                $fromWarehouseId, 
                "ITO: Received from {$fromWarehouseId}", 
                'in_transit',
                $transferOutId
            );

            return $transferOutId;
        });
    }

    /**
     * Create Inventory Transfer In (ITI) - Move items from transit to destination warehouse.
     */
    public function createTransferIn($transferOutId, $receivedQuantity = null, $notes = null)
    {
        return DB::transaction(function () use ($transferOutId, $receivedQuantity, $notes) {
            // Get the original transfer out transaction
            $transferOut = InventoryTransaction::findOrFail($transferOutId);
            
            if ($transferOut->transfer_status !== 'in_transit') {
                throw new \Exception('Transfer is not in transit status');
            }

            $quantity = $receivedQuantity ?? abs($transferOut->quantity);
            $itemId = $transferOut->item_id;
            $fromWarehouseId = $transferOut->warehouse_id;
            $toWarehouseId = $transferOut->reference_id;

            // Get transit warehouse
            $fromWarehouse = Warehouse::findOrFail($fromWarehouseId);
            $transitWarehouse = $fromWarehouse->getTransitWarehouse();

            // Check if transit warehouse has enough stock
            $transitStock = InventoryWarehouseStock::where('item_id', $itemId)
                ->where('warehouse_id', $transitWarehouse->id)
                ->first();

            if (!$transitStock || $transitStock->quantity_on_hand < $quantity) {
                throw new \Exception('Insufficient stock in transit warehouse');
            }

            // Create transfer in transaction (transit -> destination)
            $transferInId = $this->updateWarehouseStock(
                $itemId, 
                $transitWarehouse->id, 
                -$quantity, 
                'transfer', 
                'warehouse_transfer_in', 
                $fromWarehouseId, 
                "ITI: Transfer to destination", 
                'completed',
                $transferOutId
            );

            // Create destination transaction (destination warehouse receives)
            $this->updateWarehouseStock(
                $itemId, 
                $toWarehouseId, 
                $quantity, 
                'transfer', 
                'warehouse_transfer_in', 
                $fromWarehouseId, 
                "ITI: Received from transit", 
                'completed',
                $transferOutId,
                $transferInId
            );

            // Update original transfer out status
            $transferOut->update([
                'transfer_status' => 'completed',
                'transfer_in_id' => $transferInId,
                'received_date' => now()
            ]);

            return $transferInId;
        });
    }

    /**
     * Get pending transfers (items in transit).
     */
    public function getPendingTransfers($warehouseId = null)
    {
        $query = InventoryTransaction::with(['item', 'warehouse'])
            ->where('transaction_type', 'transfer')
            ->where('transfer_status', 'in_transit')
            ->where('reference_type', 'warehouse_transfer_out')
            ->orderBy('created_at', 'desc');

        if ($warehouseId) {
            $query->where('reference_id', $warehouseId);
        }

        return $query->get();
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
