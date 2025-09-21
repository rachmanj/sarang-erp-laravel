<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;

class PurchasePaymentLine extends Model
{
    protected $fillable = [
        'payment_id',
        'account_id',
        'amount',
        'description',
    ];

    protected $casts = [
        'amount' => 'float',
    ];
}
