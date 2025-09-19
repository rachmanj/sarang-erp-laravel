<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerItemPriceLevel extends Model
{
    protected $fillable = [
        'business_partner_id',
        'inventory_item_id',
        'price_level',
        'custom_price',
    ];

    protected $casts = [
        'custom_price' => 'decimal:2',
    ];

    // Relationships
    public function businessPartner(): BelongsTo
    {
        return $this->belongsTo(BusinessPartner::class, 'business_partner_id');
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    // Scopes
    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('business_partner_id', $customerId);
    }

    public function scopeByItem($query, $itemId)
    {
        return $query->where('inventory_item_id', $itemId);
    }

    public function scopeByPriceLevel($query, $level)
    {
        return $query->where('price_level', $level);
    }

    // Helper methods
    public function getEffectivePrice()
    {
        if ($this->custom_price) {
            return $this->custom_price;
        }

        return $this->inventoryItem->getPriceForLevel($this->price_level);
    }
}
