<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsReceiptPO extends Model
{
    protected $table = 'goods_receipt_po';

    protected $fillable = [
        'grn_no',
        'date',
        'business_partner_id',
        'warehouse_id',
        'purchase_order_id',
        'source_po_id',
        'source_type',
        'description',
        'total_amount',
        'status',
        'journal_id',
        'journal_posted_at',
        'journal_posted_by'
    ];

    protected $casts = [
        'date' => 'date',
        'total_amount' => 'decimal:2',
        'journal_posted_at' => 'datetime',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(GoodsReceiptPOLine::class, 'grpo_id');
    }

    public function businessPartner(): BelongsTo
    {
        return $this->belongsTo(BusinessPartner::class, 'business_partner_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Warehouse::class, 'warehouse_id');
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Accounting\Journal::class, 'journal_id');
    }

    public function journalPostedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'journal_posted_by');
    }

    public function journalEntries(): HasMany
    {
        return $this->hasMany(GRPOJournalEntry::class, 'grpo_id');
    }

    /**
     * Check if GRPO has been journalized
     */
    public function isJournalized(): bool
    {
        return !is_null($this->journal_id);
    }

    /**
     * Check if GRPO can be journalized
     */
    public function canBeJournalized(): bool
    {
        return $this->status === 'received' && !$this->isJournalized();
    }
}
