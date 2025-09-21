<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryItemUnit extends Model
{
    protected $table = 'inventory_item_units';

    protected $fillable = [
        'inventory_item_id',
        'unit_id',
        'is_base_unit',
        'conversion_quantity',
        'purchase_price',
        'selling_price',
        'selling_price_level_2',
        'selling_price_level_3',
        'is_active',
    ];

    protected $casts = [
        'is_base_unit' => 'boolean',
        'conversion_quantity' => 'decimal:2',
        'purchase_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'selling_price_level_2' => 'decimal:2',
        'selling_price_level_3' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'unit_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeBaseUnits($query)
    {
        return $query->where('is_base_unit', true);
    }

    public function scopeForItem($query, $itemId)
    {
        return $query->where('inventory_item_id', $itemId);
    }

    // Helper methods
    public function getBaseUnitPrice(): float
    {
        if ($this->is_base_unit) {
            return $this->purchase_price;
        }

        // Use custom conversion quantity
        return $this->conversion_quantity > 0 ? $this->purchase_price / $this->conversion_quantity : $this->purchase_price;
    }

    public function getSellingPriceForLevel($level = 1): float
    {
        switch ($level) {
            case 2:
                return $this->selling_price_level_2 ?? $this->selling_price;
            case 3:
                return $this->selling_price_level_3 ?? $this->selling_price;
            default:
                return $this->selling_price;
        }
    }

    public function convertToBaseQuantity($quantity): float
    {
        if ($this->is_base_unit) {
            return $quantity;
        }

        // Use custom conversion quantity
        return $this->conversion_quantity > 0 ? $quantity * $this->conversion_quantity : $quantity;
    }

    // Validation
    public function validateUnitType(): bool
    {
        $item = $this->inventoryItem;
        if (!$item) {
            return false;
        }

        // Get base unit for this item
        $baseUnit = $item->baseUnit();
        if (!$baseUnit) {
            return true; // No base unit set yet
        }

        // Check if unit types match
        return $this->unit->unit_type === $baseUnit->unit->unit_type;
    }

    // Accessors
    public function getDisplayNameAttribute(): string
    {
        return $this->unit->display_name;
    }

    public function getPriceLevelsAttribute(): array
    {
        return [
            'level_1' => $this->selling_price,
            'level_2' => $this->selling_price_level_2 ?? $this->selling_price,
            'level_3' => $this->selling_price_level_3 ?? $this->selling_price,
        ];
    }

    public function getConversionDisplayAttribute(): string
    {
        if ($this->is_base_unit) {
            return '1 ' . strtolower($this->unit->name);
        }

        $baseUnit = $this->inventoryItem->baseUnit;
        if (!$baseUnit) {
            return $this->conversion_quantity . ' units';
        }

        $baseUnitName = strtolower($baseUnit->unit->name);
        return $this->conversion_quantity . ' ' . $baseUnitName;
    }

    public function getConversionFactorAttribute(): float
    {
        return $this->conversion_quantity;
    }
}
