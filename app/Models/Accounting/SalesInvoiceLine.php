<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesInvoiceLine extends Model
{
    protected $table = 'sales_invoice_lines';

    protected $fillable = [
        'invoice_id',
        'inventory_item_id',
        'item_code',
        'item_name',
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

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function taxCode(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Master\TaxCode::class, 'tax_code_id');
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(\App\Models\InventoryItem::class, 'inventory_item_id');
    }
}
