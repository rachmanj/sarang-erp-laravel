<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesInvoiceGrpoCombination extends Model
{
    protected $fillable = [
        'sales_invoice_id',
        'goods_receipt_id',
    ];

    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Accounting\SalesInvoice::class, 'sales_invoice_id');
    }

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class, 'goods_receipt_id');
    }
}
