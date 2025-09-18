<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoodsReceipt extends Model
{
    protected $fillable = [
        'grn_no',
        'date',
        'vendor_id',
        'purchase_order_id',
        'source_po_id',
        'source_type',
        'description',
        'total_amount',
        'status'
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(GoodsReceiptLine::class, 'grn_id');
    }
}
