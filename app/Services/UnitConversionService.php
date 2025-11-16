<?php

namespace App\Services;

use App\Models\UnitOfMeasure;
use App\Models\UnitConversion;
use App\Models\InventoryItem;
use App\Models\InventoryItemUnit;
use Illuminate\Support\Facades\DB;

class UnitConversionService
{
    // Note: global unit-to-unit conversion via UnitConversion is no longer used for items.
    // Item-specific conversions are handled via InventoryItemUnit.conversion_quantity.

    /**
     * Get base unit for an inventory item
     */
    public function getBaseUnitForItem(int $itemId): ?InventoryItemUnit
    {
        return InventoryItemUnit::where('inventory_item_id', $itemId)
            ->where('is_base_unit', true)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Convert order quantity to base unit quantity
     */
    public function convertToBaseUnit(int $itemId, float $quantity, int $orderUnitId): ?float
    {
        $baseUnit = $this->getBaseUnitForItem($itemId);
        if (!$baseUnit) {
            return $quantity; // No base unit set, return original quantity
        }

        // Same as base unit
        if ($orderUnitId === $baseUnit->unit_id) {
            return $quantity;
        }

        // Convert to base unit using item-specific conversion_quantity
        $itemUnit = InventoryItemUnit::where('inventory_item_id', $itemId)
            ->where('unit_id', $orderUnitId)
            ->first();

        if (!$itemUnit || $itemUnit->conversion_quantity <= 0) {
            return $quantity;
        }

        return $quantity * $itemUnit->conversion_quantity;
    }

    /**
     * Calculate base unit price from order unit price
     */
    public function calculateBaseUnitPrice(float $orderPrice, int $itemId, int $orderUnitId): ?float
    {
        $baseUnit = $this->getBaseUnitForItem($itemId);
        if (!$baseUnit) {
            return $orderPrice; // No base unit set, return original price
        }

        // Same as base unit
        if ($orderUnitId === $baseUnit->unit_id) {
            return $orderPrice;
        }

        // Convert price to base unit using item-specific conversion_quantity
        $itemUnit = InventoryItemUnit::where('inventory_item_id', $itemId)
            ->where('unit_id', $orderUnitId)
            ->first();

        if (!$itemUnit || $itemUnit->conversion_quantity <= 0) {
            return $orderPrice;
        }

        return $orderPrice / $itemUnit->conversion_quantity;
    }

    /**
     * Get available units for an inventory item
     */
    public function getAvailableUnitsForItem(int $itemId): array
    {
        $itemUnits = InventoryItemUnit::where('inventory_item_id', $itemId)
            ->where('is_active', true)
            ->with('unit')
            ->get();

        return $itemUnits->map(function ($itemUnit) {
            return [
                'id' => $itemUnit->unit_id,
                'code' => $itemUnit->unit->code,
                'name' => $itemUnit->unit->name,
                'display_name' => $itemUnit->unit->display_name,
                'is_base_unit' => $itemUnit->is_base_unit,
                'selling_price' => $itemUnit->selling_price,
                'selling_price_level_2' => $itemUnit->selling_price_level_2,
                'selling_price_level_3' => $itemUnit->selling_price_level_3,
            ];
        })->toArray();
    }

    /**
     * Process order line with unit conversion
     */
    public function processOrderLine(array $lineData, int $itemId): array
    {
        $orderUnitId = $lineData['order_unit_id'] ?? null;
        $quantity = $lineData['qty'] ?? 0;
        $unitPrice = $lineData['unit_price'] ?? 0;

        if (!$orderUnitId) {
            return $lineData; // No unit conversion needed
        }

        // Get base unit for item
        $baseUnit = $this->getBaseUnitForItem($itemId);
        if (!$baseUnit) {
            return $lineData; // No base unit set
        }

        // If same as base unit, no conversion needed
        if ($orderUnitId === $baseUnit->unit_id) {
            $lineData['base_quantity'] = $quantity;
            $lineData['unit_conversion_factor'] = 1;
            $lineData['base_unit_price'] = $unitPrice;
            return $lineData;
        }

        // Get the item unit to use custom conversion quantity
        $itemUnit = InventoryItemUnit::where('inventory_item_id', $itemId)
            ->where('unit_id', $orderUnitId)
            ->first();

        if (!$itemUnit || $itemUnit->conversion_quantity <= 0) {
            return $lineData; // No custom conversion available
        }

        // Calculate base quantities and prices using custom conversion quantity
        $baseQuantity = $quantity * $itemUnit->conversion_quantity;
        $baseUnitPrice = $unitPrice / $itemUnit->conversion_quantity;

        // Update line data
        $lineData['base_quantity'] = $baseQuantity;
        $lineData['unit_conversion_factor'] = $itemUnit->conversion_quantity;
        $lineData['base_unit_price'] = $baseUnitPrice;

        return $lineData;
    }

    /**
     * Create inventory item unit relationships
     */
    public function createItemUnit(int $itemId, int $unitId, array $data = []): InventoryItemUnit
    {
        return InventoryItemUnit::create([
            'inventory_item_id' => $itemId,
            'unit_id' => $unitId,
            'is_base_unit' => $data['is_base_unit'] ?? false,
            'conversion_quantity' => $data['conversion_quantity'] ?? 1,
            'selling_price' => $data['selling_price'] ?? 0,
            'selling_price_level_2' => $data['selling_price_level_2'] ?? null,
            'selling_price_level_3' => $data['selling_price_level_3'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    /**
     * Set base unit for an inventory item
     */
    public function setBaseUnit(int $itemId, int $unitId): bool
    {
        DB::transaction(function () use ($itemId, $unitId) {
            // Remove existing base unit
            InventoryItemUnit::where('inventory_item_id', $itemId)
                ->update(['is_base_unit' => false]);

            // Set new base unit
            InventoryItemUnit::where('inventory_item_id', $itemId)
                ->where('unit_id', $unitId)
                ->update(['is_base_unit' => true]);
        });

        return true;
    }

    /**
     * Get conversion preview for item context
     */
    public function getItemConversionPreview(int $itemId, int $fromUnitId, float $quantity = 1): ?string
    {
        $baseUnit = $this->getBaseUnitForItem($itemId);
        if (!$baseUnit) {
            return null;
        }

        // Item-level conversions and compatibility checks no longer enforce unit_type.
        return null;
    }
}
