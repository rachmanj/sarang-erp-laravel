<?php

namespace App\Services;

use App\Models\UnitOfMeasure;
use App\Models\UnitConversion;
use App\Models\InventoryItem;
use App\Models\InventoryItemUnit;
use Illuminate\Support\Facades\DB;

class UnitConversionService
{
    /**
     * Get conversion factor between two units
     */
    public function getConversionFactor(int $fromUnitId, int $toUnitId): ?float
    {
        // Same unit
        if ($fromUnitId === $toUnitId) {
            return 1.0;
        }

        // Check if conversion exists
        $conversion = UnitConversion::where('from_unit_id', $fromUnitId)
            ->where('to_unit_id', $toUnitId)
            ->where('is_active', true)
            ->first();

        return $conversion ? $conversion->conversion_factor : null;
    }

    /**
     * Convert quantity from one unit to another
     */
    public function convertQuantity(float $quantity, int $fromUnitId, int $toUnitId): ?float
    {
        $factor = $this->getConversionFactor($fromUnitId, $toUnitId);
        return $factor ? $quantity * $factor : null;
    }

    /**
     * Validate if conversion between units is possible
     */
    public function validateConversion(int $fromUnitId, int $toUnitId): bool
    {
        $fromUnit = UnitOfMeasure::find($fromUnitId);
        $toUnit = UnitOfMeasure::find($toUnitId);

        if (!$fromUnit || !$toUnit) {
            return false;
        }

        // Check if units are of the same type
        if ($fromUnit->unit_type !== $toUnit->unit_type) {
            return false;
        }

        // Check if conversion exists
        return $this->getConversionFactor($fromUnitId, $toUnitId) !== null;
    }

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

        // Convert to base unit
        return $this->convertQuantity($quantity, $orderUnitId, $baseUnit->unit_id);
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

        // Convert price to base unit
        $factor = $this->getConversionFactor($orderUnitId, $baseUnit->unit_id);
        return $factor ? $orderPrice / $factor : null;
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
                'purchase_price' => $itemUnit->purchase_price,
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
            'purchase_price' => $data['purchase_price'] ?? 0,
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
     * Get conversion preview text
     */
    public function getConversionPreview(int $fromUnitId, int $toUnitId, float $quantity = 1): ?string
    {
        $factor = $this->getConversionFactor($fromUnitId, $toUnitId);
        if (!$factor) {
            return null;
        }

        $fromUnit = UnitOfMeasure::find($fromUnitId);
        $toUnit = UnitOfMeasure::find($toUnitId);

        if (!$fromUnit || !$toUnit) {
            return null;
        }

        $convertedQuantity = $quantity * $factor;

        return "{$quantity} {$fromUnit->name} = {$convertedQuantity} {$toUnit->name}";
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

        // If same as base unit, no conversion needed
        if ($fromUnitId === $baseUnit->unit_id) {
            return null;
        }

        // Get the item unit to use custom conversion quantity
        $itemUnit = InventoryItemUnit::where('inventory_item_id', $itemId)
            ->where('unit_id', $fromUnitId)
            ->first();

        if (!$itemUnit || $itemUnit->conversion_quantity <= 0) {
            return null;
        }

        $convertedQuantity = $quantity * $itemUnit->conversion_quantity;
        $fromUnit = UnitOfMeasure::find($fromUnitId);
        $toUnit = $baseUnit->unit;

        return "{$quantity} {$fromUnit->name} = {$convertedQuantity} {$toUnit->name}";
    }

    /**
     * Get all unit types
     */
    public function getUnitTypes(): array
    {
        return [
            'count' => 'Count',
            'weight' => 'Weight',
            'volume' => 'Volume',
            'length' => 'Length',
            'area' => 'Area',
            'time' => 'Time',
            'temperature' => 'Temperature',
            'custom' => 'Custom',
        ];
    }

    /**
     * Get units by type
     */
    public function getUnitsByType(string $type): array
    {
        return UnitOfMeasure::where('unit_type', $type)
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function ($unit) {
                return [
                    'id' => $unit->id,
                    'code' => $unit->code,
                    'name' => $unit->name,
                    'display_name' => $unit->display_name,
                    'is_base_unit' => $unit->is_base_unit,
                ];
            })
            ->toArray();
    }

    /**
     * Validate unit type compatibility for item
     */
    public function validateUnitTypeForItem(int $itemId, int $unitId): bool
    {
        $baseUnit = $this->getBaseUnitForItem($itemId);
        if (!$baseUnit) {
            return true; // No base unit set yet
        }

        $unit = UnitOfMeasure::find($unitId);
        if (!$unit) {
            return false;
        }

        return $unit->unit_type === $baseUnit->unit->unit_type;
    }
}
