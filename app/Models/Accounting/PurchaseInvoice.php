<?php

namespace App\Models\Accounting;

use App\Models\GoodsReceiptPO;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\DB;

class PurchaseInvoice extends Model
{
    protected $table = 'purchase_invoices';

    protected $fillable = [
        'invoice_no',
        'date',
        'business_partner_id', // Changed from vendor_id to match controller
        'currency_id',
        'exchange_rate',
        'company_entity_id',
        'purchase_order_id',
        'goods_receipt_id',
        'description',
        'total_amount',
        'total_amount_foreign',
        'discount_amount',
        'discount_percentage',
        'status',
        'posted_at',
        'terms_days',
        'due_date',
        'payment_method',
        'is_direct_purchase',
        'is_opening_balance',
        'cash_account_id',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'due_date' => 'date',
        'posted_at' => 'datetime',
        'total_amount' => 'float',
        'discount_amount' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'is_direct_purchase' => 'boolean',
        'is_opening_balance' => 'boolean',
    ];

    protected $auditLogIgnore = ['updated_at', 'created_at'];

    protected $auditEntityType = 'purchase_invoice';

    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseInvoiceLine::class, 'invoice_id');
    }

    public function businessPartner(): BelongsTo
    {
        return $this->belongsTo(\App\Models\BusinessPartner::class, 'business_partner_id');
    }

    public function companyEntity(): BelongsTo
    {
        return $this->belongsTo(\App\Models\CompanyEntity::class, 'company_entity_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->businessPartner();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function inventoryTransactions(): MorphMany
    {
        return $this->morphMany(\App\Models\InventoryTransaction::class, 'reference', 'reference_type', 'reference_id');
    }

    /**
     * Goods Receipt PO documents combined into this invoice (one PI, many GRPOs).
     *
     * @return BelongsToMany<GoodsReceiptPO, PurchaseInvoice>
     */
    public function grpos(): BelongsToMany
    {
        return $this->belongsToMany(GoodsReceiptPO::class, 'goods_receipt_po_purchase_invoice', 'purchase_invoice_id', 'grpo_id')
            ->withTimestamps();
    }

    /**
     * Invoice is tied to inventory receipt flow (single legacy column and/or pivot GRPOs).
     */
    public function isLinkedToGoodsReceiptPo(): bool
    {
        if ($this->goods_receipt_id) {
            return true;
        }

        if ($this->relationLoaded('grpos')) {
            return $this->grpos->isNotEmpty();
        }

        if (! $this->exists) {
            return false;
        }

        return DB::table('goods_receipt_po_purchase_invoice')
            ->where('purchase_invoice_id', $this->id)
            ->exists();
    }

    /**
     * Distinct GRPO ids for this invoice (pivot + legacy column).
     *
     * @return list<int>
     */
    public function linkedGoodsReceiptPoIds(): array
    {
        $fromPivot = DB::table('goods_receipt_po_purchase_invoice')
            ->where('purchase_invoice_id', $this->id)
            ->pluck('grpo_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($this->goods_receipt_id) {
            $fromPivot[] = (int) $this->goods_receipt_id;
        }

        return array_values(array_unique($fromPivot));
    }

    public function paymentAllocations(): HasMany
    {
        return $this->hasMany(PurchasePaymentAllocation::class, 'invoice_id');
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Accounting\Journal::class, 'journal_id');
    }

    /**
     * Check if invoice can be unposted
     */
    public function canBeUnposted(): bool
    {
        // Must be posted
        if ($this->status !== 'posted') {
            return false;
        }

        // Check if there are any payment allocations
        $totalAllocated = $this->paymentAllocations()->sum('amount');
        if ($totalAllocated > 0) {
            return false; // Cannot unpost if payments have been allocated
        }

        // Check if invoice has been closed
        if ($this->closure_status === 'closed') {
            return false;
        }

        return true;
    }

    /**
     * Get total allocated amount from payments
     */
    public function getTotalAllocatedAttribute(): float
    {
        return (float) $this->paymentAllocations()->sum('amount');
    }

    /**
     * Get remaining balance
     */
    public function getRemainingBalanceAttribute(): float
    {
        return $this->total_amount - $this->total_allocated;
    }
}
