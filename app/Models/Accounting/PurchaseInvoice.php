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
        'vendor_id',
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
        return $this->hasMany(PurchaseInvoiceLine::class, 'invoice_id');
    }
}
