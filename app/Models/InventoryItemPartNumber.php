<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryItemPartNumber extends Model
{
    protected $fillable = [
        'inventory_item_id',
        'part_number',
        'description',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    protected static function booted(): void
    {
        static::saving(function (InventoryItemPartNumber $partNumber) {
            if ($partNumber->is_default) {
                static::where('inventory_item_id', $partNumber->inventory_item_id)
                    ->where('id', '!=', $partNumber->id)
                    ->update(['is_default' => false]);
            }
        });
    }
}
