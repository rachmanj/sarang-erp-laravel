<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoodsReceiptPO extends Model
{
    protected $table = 'goods_receipt_po';

    protected $fillable = [
        'grn_no',
        'date',
        'business_partner_id',
        'company_entity_id',
        'created_by',
        'warehouse_id',
        'purchase_order_id',
        'source_po_id',
        'source_type',
        'description',
        'total_amount',
        'status',
        'journal_id',
        'journal_posted_at',
        'journal_posted_by',
    ];

    protected $casts = [
        'date' => 'date',
        'total_amount' => 'decimal:2',
        'journal_posted_at' => 'datetime',
    ];

    protected $auditLogIgnore = ['updated_at', 'created_at'];

    protected $auditEntityType = 'goods_receipt_po';

    public function lines(): HasMany
    {
        return $this->hasMany(GoodsReceiptPOLine::class, 'grpo_id');
    }

    /**
     * @return BelongsToMany<\App\Models\Accounting\PurchaseInvoice, GoodsReceiptPO>
     */
    public function purchaseInvoices(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Accounting\PurchaseInvoice::class, 'goods_receipt_po_purchase_invoice', 'grpo_id', 'purchase_invoice_id')
            ->withTimestamps();
    }

    public function businessPartner(): BelongsTo
    {
        return $this->belongsTo(BusinessPartner::class, 'business_partner_id');
    }

    public function companyEntity(): BelongsTo
    {
        return $this->belongsTo(CompanyEntity::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
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
        return ! is_null($this->journal_id);
    }

    /**
     * Check if GRPO can be journalized
     */
    public function canBeJournalized(): bool
    {
        return $this->status === 'received' && ! $this->isJournalized();
    }
}
