<?php

namespace App\Models;

use App\Models\Accounting\PurchaseInvoice;
use App\Models\Accounting\PurchaseInvoiceLine;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Pagination\LengthAwarePaginator;

class InventoryTransaction extends Model
{
    protected $fillable = [
        'item_id',
        'warehouse_id',
        'transaction_type',
        'quantity',
        'unit_cost',
        'total_cost',
        'reference_type',
        'reference_id',
        'purchase_invoice_line_id',
        'transfer_status',
        'transfer_out_id',
        'transfer_in_id',
        'transfer_notes',
        'transit_date',
        'received_date',
        'transaction_date',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'transaction_date' => 'date',
        'transit_date' => 'datetime',
        'received_date' => 'datetime',
    ];

    // Relationships
    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function purchaseInvoiceLine(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoiceLine::class, 'purchase_invoice_line_id');
    }

    /**
     * Commercial unit price from the source document: purchase line (harga beli) or DO/SO line (harga jual).
     * This is distinct from inventory valuation unit_cost (FIFO/WAC layer cost).
     */
    public function documentUnitPrice(): ?float
    {
        if ($this->transaction_type === 'purchase') {
            if ($this->purchaseInvoiceLine) {
                $line = $this->purchaseInvoiceLine;
                if ((float) $line->net_amount > 0 && (float) $line->qty > 0) {
                    return round(((float) $line->net_amount) / ((float) $line->qty), 2);
                }

                return (float) $line->unit_price;
            }

            if ($this->relationLoaded('goodsReceiptPoLine') && $this->goodsReceiptPoLine) {
                return (float) $this->goodsReceiptPoLine->unit_price;
            }

            if ($this->reference_type === 'goods_receipt_po' && $this->reference_id) {
                $line = GoodsReceiptPOLine::query()
                    ->where('grpo_id', $this->reference_id)
                    ->where('item_id', $this->item_id)
                    ->orderBy('id')
                    ->first();

                return $line ? (float) $line->unit_price : null;
            }

            return null;
        }

        if ($this->transaction_type === 'sale') {
            if ($this->reference_type === 'delivery_order_line' && $this->reference_id) {
                $line = $this->relationLoaded('saleDeliveryOrderLine')
                    ? $this->saleDeliveryOrderLine
                    : DeliveryOrderLine::query()->find($this->reference_id);

                return $line ? (float) $line->unit_price : null;
            }

            if ($this->reference_type === 'sales_order' && $this->reference_id) {
                $line = $this->relationLoaded('salesOrderLine')
                    ? $this->salesOrderLine
                    : SalesOrderLine::query()
                        ->where('order_id', $this->reference_id)
                        ->where('inventory_item_id', $this->item_id)
                        ->first();

                return $line ? (float) $line->unit_price : null;
            }

            return null;
        }

        return null;
    }

    /**
     * Batch-load human-readable document numbers (PI, DO, etc.) onto each transaction as
     * attribute {@see $reference_document_label} for list views (avoids N+1 queries).
     */
    public static function hydrateReferenceDocumentLabels(LengthAwarePaginator $paginator): void
    {
        $collection = $paginator->getCollection();
        if ($collection->isEmpty()) {
            return;
        }

        $purchaseInvoiceIds = $collection
            ->where('reference_type', 'purchase_invoice')
            ->pluck('reference_id')
            ->filter()
            ->unique();
        $invoiceNoById = $purchaseInvoiceIds->isNotEmpty()
            ? PurchaseInvoice::query()->whereIn('id', $purchaseInvoiceIds)->pluck('invoice_no', 'id')
            : collect();

        $deliveryOrderLineIds = $collection
            ->where('reference_type', 'delivery_order_line')
            ->pluck('reference_id')
            ->filter()
            ->unique();
        $doNumberByLineId = collect();
        if ($deliveryOrderLineIds->isNotEmpty()) {
            $lines = DeliveryOrderLine::query()
                ->whereIn('id', $deliveryOrderLineIds)
                ->with('deliveryOrder:id,do_number')
                ->get(['id', 'delivery_order_id']);
            foreach ($lines as $line) {
                $doNumberByLineId[$line->id] = $line->deliveryOrder?->do_number;
            }
        }

        $grpoIds = $collection
            ->where('reference_type', 'goods_receipt_po')
            ->pluck('reference_id')
            ->filter()
            ->unique();
        $grnNoById = $grpoIds->isNotEmpty()
            ? GoodsReceiptPO::query()->whereIn('id', $grpoIds)->pluck('grn_no', 'id')
            : collect();

        $grGiHeaderIds = $collection
            ->where('reference_type', 'gr_gi')
            ->pluck('reference_id')
            ->filter()
            ->unique();
        $grGiDocById = $grGiHeaderIds->isNotEmpty()
            ? GRGIHeader::query()->whereIn('id', $grGiHeaderIds)->pluck('document_number', 'id')
            : collect();

        $salesOrderIds = $collection
            ->where('reference_type', 'sales_order')
            ->pluck('reference_id')
            ->filter()
            ->unique();
        $soNoById = $salesOrderIds->isNotEmpty()
            ? SalesOrder::query()->whereIn('id', $salesOrderIds)->pluck('order_no', 'id')
            : collect();

        foreach ($collection as $transaction) {
            $label = null;
            $type = $transaction->reference_type;
            $rid = $transaction->reference_id;
            if (! $type || ! $rid) {
                $transaction->setAttribute('reference_document_label', null);

                continue;
            }
            switch ($type) {
                case 'purchase_invoice':
                    $no = $invoiceNoById[$rid] ?? null;
                    $label = $no ? 'PI '.$no : null;
                    break;
                case 'delivery_order_line':
                    $no = $doNumberByLineId[$rid] ?? null;
                    $label = $no ? 'DO '.$no : null;
                    break;
                case 'goods_receipt_po':
                    $no = $grnNoById[$rid] ?? null;
                    $label = $no ? 'GRPO '.$no : null;
                    break;
                case 'gr_gi':
                    $no = $grGiDocById[$rid] ?? null;
                    $label = $no ? 'GR/GI '.$no : null;
                    break;
                case 'sales_order':
                    $no = $soNoById[$rid] ?? null;
                    $label = $no ? 'SO '.$no : null;
                    break;
                default:
                    $label = null;
            }
            $transaction->setAttribute('reference_document_label', $label);
        }
    }

    // Scopes
    public function scopePurchase($query)
    {
        return $query->where('transaction_type', 'purchase');
    }

    public function scopeSale($query)
    {
        return $query->where('transaction_type', 'sale');
    }

    public function scopeAdjustment($query)
    {
        return $query->where('transaction_type', 'adjustment');
    }

    public function scopeTransfer($query)
    {
        return $query->where('transaction_type', 'transfer');
    }

    // Transfer status scopes
    public function scopePendingTransfer($query)
    {
        return $query->where('transfer_status', 'pending');
    }

    public function scopeInTransit($query)
    {
        return $query->where('transfer_status', 'in_transit');
    }

    public function scopeReceived($query)
    {
        return $query->where('transfer_status', 'received');
    }

    public function scopeCompletedTransfer($query)
    {
        return $query->where('transfer_status', 'completed');
    }

    public function scopeCancelledTransfer($query)
    {
        return $query->where('transfer_status', 'cancelled');
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }
}
