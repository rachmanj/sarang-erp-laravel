<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;

class PurchasePayment extends Model
{
    protected $fillable = [
        'payment_no',
        'date',
        'business_partner_id',
        'description',
        'total_amount',
        'status',
        'posted_at',
    ];

    protected $casts = [
        'date' => 'date',
        'posted_at' => 'datetime',
        'total_amount' => 'float',
    ];

    public function lines()
    {
        return $this->hasMany(PurchasePaymentLine::class, 'payment_id');
    }

    public function businessPartner()
    {
        return $this->belongsTo(\App\Models\BusinessPartner::class, 'business_partner_id');
    }

    public function allocations()
    {
        return $this->hasMany(PurchasePaymentAllocation::class, 'payment_id');
    }
}
