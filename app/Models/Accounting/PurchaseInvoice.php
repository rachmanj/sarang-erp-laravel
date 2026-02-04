<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class PurchaseInvoice extends Model
{
    protected $table = 'purchase_invoices';

    protected $fillable = [
        'invoice_no',
        'date',
        'business_partner_id', // Changed from vendor_id to match controller
        'company_entity_id',
        'purchase_order_id',
        'goods_receipt_id',
        'description',
        'total_amount',
        'status',
        'posted_at',
        'terms_days',
        'due_date',
        'payment_method',
        'is_direct_purchase',
        'is_opening_balance',
        'cash_account_id',
    ];

    protected $casts = [
        'date' => 'date',
        'posted_at' => 'datetime',
        'total_amount' => 'float',
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

    public function inventoryTransactions(): MorphMany
    {
        return $this->morphMany(\App\Models\InventoryTransaction::class, 'reference', 'reference_type', 'reference_id');
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
