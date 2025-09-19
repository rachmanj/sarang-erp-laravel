<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoodsReceipt extends Model
{
    protected $fillable = [
        'grn_no',
        'date',
        'business_partner_id',
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

    public function businessPartner()
    {
        return $this->belongsTo(BusinessPartner::class, 'business_partner_id');
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }
}
