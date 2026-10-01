<?php

namespace App\Services;

use App\Models\InventoryWarehouseStock;

class InventoryWarehouseStockService
{
    public function applyDelta(int $itemId, int $warehouseId, int $quantityChange): InventoryWarehouseStock
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
