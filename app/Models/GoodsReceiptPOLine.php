<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsReceiptPOLine extends Model
{
    protected $table = 'goods_receipt_po_lines';
    
    protected $fillable = [
        'grpo_id',
        'account_id',
        'description',
        'qty',
        'unit_price',
        'amount',
        'tax_code_id'
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    public function grpo(): BelongsTo
    {
        return $this->belongsTo(GoodsReceiptPO::class, 'grpo_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Accounting\Account::class, 'account_id');
    }

    public function taxCode(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Master\TaxCode::class, 'tax_code_id');
    }
}
