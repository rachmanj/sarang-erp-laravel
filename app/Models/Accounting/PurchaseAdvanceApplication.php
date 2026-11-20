<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseAdvanceApplication extends Model
{
    protected $fillable = [
        'advance_id',
        'invoice_id',
        'amount',
        'payment_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'float',
        ];
    }

    public function advance(): BelongsTo
    {
        return $this->belongsTo(PurchaseAdvance::class, 'advance_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class, 'invoice_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(PurchasePayment::class, 'payment_id');
    }
}
