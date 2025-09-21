<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;

class PurchasePaymentAllocation extends Model
{
    protected $fillable = [
        'payment_id',
        'invoice_id',
        'amount',
    ];

    protected $casts = [
        'amount' => 'float',
    ];

    public function payment()
    {
        return $this->belongsTo(PurchasePayment::class, 'payment_id');
    }

    public function invoice()
    {
        return $this->belongsTo(PurchaseInvoice::class, 'invoice_id');
    }
}
