<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesReceiptLine extends Model
{
    protected $table = 'sales_receipt_lines';

    protected $fillable = [
        'receipt_id',
        'account_id',
        'description',
        'amount',
        'project_id',
        'fund_id',
        'dept_id',
    ];

    protected $casts = [
        'amount' => 'float',
    ];

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(SalesReceipt::class, 'receipt_id');
    }
}
