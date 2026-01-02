<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesQuotation extends Model
{
    protected $fillable = [
        'quotation_no',
        'reference_no',
        'date',
        'valid_until_date',
        'business_partner_id',
        'company_entity_id',
        'currency_id',
        'exchange_rate',
        'warehouse_id',
        'description',
        'notes',
        'terms_conditions',
        'payment_terms',
        'delivery_method',
        'total_amount',
        'total_amount_foreign',
        'freight_cost',
        'handling_cost',
        'insurance_cost',
        'discount_amount',
        'discount_percentage',
        'net_amount',
        'order_type',
        'status',
        'approval_status',
        'converted_to_sales_order_id',
        'converted_at',
        'approved_by',
        'approved_at',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'date' => 'date',
        'valid_until_date' => 'date',
        'converted_at' => 'datetime',
        'approved_at' => 'datetime',
        'exchange_rate' => 'decimal:6',
        'total_amount' => 'decimal:2',
        'total_amount_foreign' => 'decimal:2',
        'freight_cost' => 'decimal:2',
        'handling_cost' => 'decimal:2',
        'insurance_cost' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'net_amount' => 'decimal:2',
    ];

    protected $auditLogIgnore = ['updated_at', 'created_at'];
    protected $auditEntityType = 'sales_quotation';

    // Relationships
    public function lines(): HasMany
    {
        return $this->hasMany(SalesQuotationLine::class, 'quotation_id');
    }

    public function businessPartner(): BelongsTo
    {
        return $this->belongsTo(BusinessPartner::class, 'business_partner_id');
    }

    public function companyEntity(): BelongsTo
    {
        return $this->belongsTo(CompanyEntity::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function customer(): BelongsTo
    {
        return $this->businessPartner();
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(SalesQuotationApproval::class, 'sales_quotation_id');
    }

    public function convertedToSalesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'converted_to_sales_order_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('approval_status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('approval_status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('approval_status', 'rejected');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    public function scopeRejectedStatus($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }

    public function scopeConverted($query)
    {
        return $query->where('status', 'converted');
    }

    public function scopeNotExpired($query)
    {
        return $query->where('valid_until_date', '>=', now()->toDateString())
            ->orWhere('status', 'converted');
    }

    // Accessors
    public function getNetAmountAttribute()
    {
        return $this->total_amount - $this->discount_amount;
    }

    public function getTotalCostAttribute()
    {
        return $this->total_amount + $this->freight_cost + $this->handling_cost + $this->insurance_cost;
    }

    public function getIsExpiredAttribute()
    {
        if ($this->status === 'converted' || $this->status === 'rejected') {
            return false;
        }

        return $this->valid_until_date < now()->toDateString();
    }

    public function getIsExpiringSoonAttribute()
    {
        if ($this->is_expired || $this->status === 'converted') {
            return false;
        }

        return $this->valid_until_date <= now()->addDays(3)->toDateString();
    }

    public function getGrossProfitAttribute()
    {
        return $this->net_amount - $this->total_cost;
    }

    public function getGrossProfitMarginAttribute()
    {
        if ($this->net_amount == 0) {
            return 0;
        }

        return round(($this->gross_profit / $this->net_amount) * 100, 2);
    }

    // Methods
    public function canBeSent()
    {
        return $this->status === 'draft' && $this->approval_status === 'approved';
    }

    public function canBeAccepted()
    {
        return $this->status === 'sent' && !$this->is_expired;
    }

    public function canBeRejected()
    {
        return in_array($this->status, ['sent', 'draft']) && !$this->is_expired;
    }

    public function canBeConverted()
    {
        return $this->status === 'accepted' && !$this->is_expired;
    }

    public function canBeApproved()
    {
        return $this->approval_status === 'pending' && $this->status === 'draft';
    }

    public function isConverted()
    {
        return $this->status === 'converted' && $this->converted_to_sales_order_id !== null;
    }

    public function getApprovalProgress()
    {
        $totalApprovals = $this->approvals()->count();
        $completedApprovals = $this->approvals()->where('status', 'approved')->count();

        if ($totalApprovals === 0) {
            return 0;
        }

        return round(($completedApprovals / $totalApprovals) * 100);
    }

    public function getCustomerPricingTier()
    {
        $discountDetail = $this->businessPartner->getDetailBySection('financial', 'discount_percentage');
        $discountPercentage = $discountDetail ? (float) $discountDetail->field_value : 0;

        return (object) [
            'discount_percentage' => $discountPercentage,
            'tier_name' => 'Default'
        ];
    }

    public function validateOrderTypeConsistency()
    {
        $lineTypes = $this->lines()->with('inventoryItem')->get()
            ->pluck('inventoryItem.item_type')->unique();

        if ($lineTypes->count() > 1) {
            throw new \Exception('Quotation lines must be of the same type (item or service)');
        }

        if ($lineTypes->first() !== $this->order_type) {
            throw new \Exception('Order type must match line item types');
        }
    }
}
