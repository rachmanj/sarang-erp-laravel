<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesInvoiceLine extends Model
{
    protected $table = 'sales_invoice_lines';

    protected $fillable = [
        'invoice_id',
        'account_id',
        'description',
        'qty',
        'unit_price',
        'amount',
        'tax_code_id',
        'project_id',
        'fund_id',
        'dept_id',
    ];

    protected $casts = [
        'qty' => 'float',
        'unit_price' => 'float',
        'amount' => 'float',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class, 'invoice_id');
    }
}
