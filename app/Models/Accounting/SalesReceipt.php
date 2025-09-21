<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesReceipt extends Model
{
    protected $table = 'sales_receipts';

    protected $fillable = [
        'receipt_no',
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

    public function lines(): HasMany
    {
        return $this->hasMany(SalesReceiptLine::class, 'receipt_id');
    }
}
