<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsReceiptLine extends Model
{
    protected $fillable = [
        'grn_id',
        'account_id',
        'description',
        'qty',
        'unit_price',
        'amount',
        'tax_code_id'
    ];

    public function grn(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class, 'grn_id');
    }
}
