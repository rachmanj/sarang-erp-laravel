<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesReceiptAllocation extends Model
{
    protected $table = 'sales_receipt_allocations';

    protected $fillable = [
        'receipt_id',
        'invoice_id',
        'amount',
        'description',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    /**
     * Get the sales receipt that owns the allocation.
     */
    public function salesReceipt(): BelongsTo
    {
        return $this->belongsTo(SalesReceipt::class, 'receipt_id');
    }

    /**
     * Get the sales invoice that is allocated.
     */
    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class, 'invoice_id');
    }
}
