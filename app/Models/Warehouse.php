<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    protected $fillable = [
        'code',
        'name',
        'address',
        'contact_person',
        'phone',
        'email',
        'is_active',
        'is_transit',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_transit' => 'boolean',
    ];

    // Relationships
    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class, 'default_warehouse_id');
    }

    public function warehouseStock(): HasMany
    {
        return $this->hasMany(InventoryWarehouseStock::class, 'warehouse_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class, 'warehouse_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeTransit($query)
    {
        return $query->where('is_transit', true);
    }

    public function scopePhysical($query)
    {
        return $query->where('is_transit', false);
    }

    // Get the transit warehouse for this physical warehouse
    public function getTransitWarehouse()
    {
        return self::where('code', $this->code . '_TRANSIT')->first();
    }

    // Check if this warehouse has a transit warehouse
    public function hasTransitWarehouse()
    {
        return self::where('code', $this->code . '_TRANSIT')->exists();
    }

    // Accessors
    public function getDisplayNameAttribute()
    {
        return "{$this->code} - {$this->name}";
    }
}
