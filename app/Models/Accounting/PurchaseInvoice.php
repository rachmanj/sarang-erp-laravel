<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseInvoice extends Model
{
    protected $table = 'purchase_invoices';

    protected $fillable = [
        'invoice_no',
        'date',
        'business_partner_id', // Changed from vendor_id to match controller
        'purchase_order_id',
        'goods_receipt_id',
        'description',
        'total_amount',
        'status',
        'posted_at',
        'terms_days',
        'due_date',
    ];

    protected $casts = [
        'date' => 'date',
        'posted_at' => 'datetime',
        'total_amount' => 'float',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseInvoiceLine::class, 'invoice_id');
    }
}
