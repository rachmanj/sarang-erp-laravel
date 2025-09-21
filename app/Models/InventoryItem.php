<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class InventoryItem extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'category_id',
        'default_warehouse_id',
        'unit_of_measure',
        'purchase_price',
        'selling_price',
        'selling_price_level_2',
        'selling_price_level_3',
        'price_level_2_percentage',
        'price_level_3_percentage',
        'min_stock_level',
        'max_stock_level',
        'reorder_point',
        'valuation_method',
        'item_type',
        'is_active',
    ];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'selling_price_level_2' => 'decimal:2',
        'selling_price_level_3' => 'decimal:2',
        'price_level_2_percentage' => 'decimal:2',
        'price_level_3_percentage' => 'decimal:2',
        'min_stock_level' => 'integer',
        'max_stock_level' => 'integer',
        'reorder_point' => 'integer',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class, 'item_id');
    }

    public function valuations(): HasMany
    {
        return $this->hasMany(InventoryValuation::class, 'item_id');
    }

    public function defaultWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'default_warehouse_id');
    }

    public function warehouseStock(): HasMany
    {
        return $this->hasMany(InventoryWarehouseStock::class, 'item_id');
    }

    public function customerPriceLevels(): HasMany
    {
        return $this->hasMany(CustomerItemPriceLevel::class, 'inventory_item_id');
    }

    // Unit conversion relationships
    public function itemUnits(): HasMany
    {
        return $this->hasMany(InventoryItemUnit::class);
    }

    public function activeItemUnits(): HasMany
    {
        return $this->hasMany(InventoryItemUnit::class)->where('is_active', true);
    }

    public function baseUnit(): HasOne
    {
        return $this->hasOne(InventoryItemUnit::class, 'inventory_item_id')->where('is_base_unit', true);
    }

    public function availableUnits(): HasMany
    {
        return $this->itemUnits()->where('is_active', true);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeItems($query)
    {
        return $query->where('item_type', 'item');
    }

    public function scopeServices($query)
    {
        return $query->where('item_type', 'service');
    }

    public function scopeLowStock($query)
    {
        return $query->whereRaw('current_stock <= reorder_point');
    }

    // Helper methods for price levels
    public function getPriceForLevel($level, $customerId = null)
    {
        // Check for customer-specific price level first
        if ($customerId) {
            $customerPriceLevel = $this->customerPriceLevels()
                ->where('business_partner_id', $customerId)
                ->first();

            if ($customerPriceLevel && $customerPriceLevel->custom_price) {
                return $customerPriceLevel->custom_price;
            }
        }

        switch ($level) {
            case '1':
                return $this->selling_price;
            case '2':
                if ($this->selling_price_level_2) {
                    return $this->selling_price_level_2;
                }
                if ($this->price_level_2_percentage) {
                    return $this->selling_price * (1 + $this->price_level_2_percentage / 100);
                }
                return $this->selling_price;
            case '3':
                if ($this->selling_price_level_3) {
                    return $this->selling_price_level_3;
                }
                if ($this->price_level_3_percentage) {
                    return $this->selling_price * (1 + $this->price_level_3_percentage / 100);
                }
                return $this->selling_price;
            default:
                return $this->selling_price;
        }
    }

    public function getAccountByType($type)
    {
        if (!$this->category) {
            return null;
        }

        switch ($type) {
            case 'inventory':
                return $this->category->inventoryAccount;
            case 'cogs':
                return $this->category->cogsAccount;
            case 'sales':
                return $this->category->salesAccount;
            default:
                return null;
        }
    }

    // Accessors
    public function getCurrentStockAttribute()
    {
        // Services don't have stock
        if ($this->item_type === 'service') {
            return 0;
        }

        $lastTransaction = $this->transactions()
            ->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$lastTransaction) {
            return 0;
        }

        $totalIn = $this->transactions()
            ->where('transaction_type', 'purchase')
            ->sum('quantity');

        $totalOut = $this->transactions()
            ->whereIn('transaction_type', ['sale', 'adjustment'])
            ->sum('quantity');

        return $totalIn - $totalOut;
    }

    public function getCurrentValueAttribute()
    {
        $lastValuation = $this->valuations()
            ->orderBy('valuation_date', 'desc')
            ->first();

        return $lastValuation ? $lastValuation->total_value : 0;
    }

    // Unit conversion helper methods
    public function getBaseUnitCode(): ?string
    {
        $baseUnit = $this->baseUnit;
        return $baseUnit ? $baseUnit->unit->code : null;
    }

    public function getBaseUnitName(): ?string
    {
        $baseUnit = $this->baseUnit;
        return $baseUnit ? $baseUnit->unit->name : null;
    }

    public function convertToBaseUnit($quantity, $fromUnitId): float
    {
        if (!$fromUnitId) {
            return $quantity;
        }

        $fromUnit = $this->itemUnits()->where('unit_id', $fromUnitId)->first();
        if (!$fromUnit) {
            return $quantity;
        }

        return $fromUnit->convertToBaseQuantity($quantity);
    }

    public function getPriceForUnit($unitId, $priceLevel = 1): float
    {
        $itemUnit = $this->itemUnits()->where('unit_id', $unitId)->first();
        if (!$itemUnit) {
            return 0;
        }

        return $itemUnit->getSellingPriceForLevel($priceLevel);
    }

    public function getAvailableUnitsForSelection(): array
    {
        return $this->availableUnits()
            ->with('unit')
            ->get()
            ->map(function ($itemUnit) {
                return [
                    'id' => $itemUnit->unit_id,
                    'code' => $itemUnit->unit->code,
                    'name' => $itemUnit->unit->name,
                    'display_name' => $itemUnit->unit->display_name,
                    'is_base_unit' => $itemUnit->is_base_unit,
                    'purchase_price' => $itemUnit->purchase_price,
                    'selling_price' => $itemUnit->selling_price,
                ];
            })
            ->toArray();
    }

    public function hasMultipleUnits(): bool
    {
        return $this->itemUnits()->count() > 1;
    }

    public function canUseUnit($unitId): bool
    {
        $baseUnit = $this->baseUnit;
        if (!$baseUnit) {
            return true; // No base unit set yet
        }

        $unit = UnitOfMeasure::find($unitId);
        if (!$unit) {
            return false;
        }

        // Check if unit types match
        return $unit->unit_type === $baseUnit->unit->unit_type;
    }
}
