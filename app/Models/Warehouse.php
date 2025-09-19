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
    ];

    protected $casts = [
        'is_active' => 'boolean',
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

    // Accessors
    public function getDisplayNameAttribute()
    {
        return "{$this->code} - {$this->name}";
    }
}
