<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GRGILine extends Model
{
    protected $table = 'gr_gi_lines';

    protected $fillable = [
        'header_id',
        'item_id',
        'quantity',
        'unit_price',
        'total_amount',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    // Relationships
    public function header(): BelongsTo
    {
        return $this->belongsTo(GRGIHeader::class, 'header_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }

    // Accessors
    public function getItemNameAttribute()
    {
        return $this->item ? $this->item->name : 'Unknown Item';
    }

    public function getItemCodeAttribute()
    {
        return $this->item ? $this->item->code : 'N/A';
    }

    public function getUnitOfMeasureAttribute()
    {
        return $this->item ? $this->item->unit_of_measure : 'PCS';
    }

    // Methods
    public function calculateTotalAmount()
    {
        $this->total_amount = $this->quantity * $this->unit_price;
        return $this->total_amount;
    }
}
