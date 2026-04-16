<?php

namespace App\Models\Accounting;

use App\Models\DeliveryOrderLine;
use App\Models\InventoryItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesCreditMemoLine extends Model
{
    protected $table = 'sales_credit_memo_lines';

    protected $fillable = [
        'credit_memo_id',
        'account_id',
        'delivery_order_line_id',
        'inventory_item_id',
        'item_code',
        'item_name',
        'description',
        'qty',
        'unit_price',
        'amount',
        'tax_code_id',
        'project_id',
        'dept_id',
    ];

    protected $casts = [
        'qty' => 'float',
        'unit_price' => 'float',
        'amount' => 'float',
    ];

    public function creditMemo(): BelongsTo
    {
        return $this->belongsTo(SalesCreditMemo::class, 'credit_memo_id');
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
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function deliveryOrderLine(): BelongsTo
    {
        return $this->belongsTo(DeliveryOrderLine::class, 'delivery_order_line_id');
    }
}
