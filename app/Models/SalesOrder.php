<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesOrder extends Model
{
    protected $fillable = [
        'order_no',
        'reference_no',
        'date',
        'expected_delivery_date',
        'actual_delivery_date',
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
        'total_cost',
        'discount_amount',
        'discount_percentage',
        'net_amount',
        'order_type',
        'status',
        'approval_status',
        'approved_by',
        'approved_at',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'date' => 'date',
        'expected_delivery_date' => 'date',
        'actual_delivery_date' => 'date',
        'approved_at' => 'datetime',
        'exchange_rate' => 'decimal:6',
        'total_amount' => 'decimal:2',
        'total_amount_foreign' => 'decimal:2',
        'freight_cost' => 'decimal:2',
        'handling_cost' => 'decimal:2',
        'insurance_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'net_amount' => 'decimal:2',
    ];

    protected $auditLogIgnore = ['updated_at', 'created_at'];
    protected $auditEntityType = 'sales_order';

    // Relationships
    public function lines(): HasMany
    {
        return $this->hasMany(SalesOrderLine::class, 'order_id');
    }

    public function businessPartner(): BelongsTo
    {
        return $this->belongsTo(\App\Models\BusinessPartner::class, 'business_partner_id');
    }

    public function companyEntity(): BelongsTo
    {
        return $this->belongsTo(CompanyEntity::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Warehouse::class, 'warehouse_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Currency::class, 'currency_id');
    }

    // Backward compatibility method
    public function customer(): BelongsTo
    {
        return $this->businessPartner();
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(SalesOrderApproval::class, 'sales_order_id');
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(SalesCommission::class, 'sales_order_id');
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

    public function deliveryOrders(): HasMany
    {
        return $this->hasMany(DeliveryOrder::class, 'sales_order_id');
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

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopeDelivered($query)
    {
        return $query->where('status', 'delivered');
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
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

    public function getIsOverdueAttribute()
    {
        if (!$this->expected_delivery_date) {
            return false;
        }

        return $this->expected_delivery_date < now()->toDateString() &&
            $this->status !== 'delivered' &&
            $this->status !== 'closed';
    }

    public function getDeliveryStatusAttribute()
    {
        if ($this->status === 'delivered' || $this->status === 'closed') {
            return 'delivered';
        }

        if ($this->is_overdue) {
            return 'overdue';
        }

        if ($this->expected_delivery_date && $this->expected_delivery_date <= now()->addDays(3)->toDateString()) {
            return 'due_soon';
        }

        return 'on_track';
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
    public function canBeApproved()
    {
        return $this->approval_status === 'pending' && $this->status === 'draft';
    }

    public function canBeConfirmed()
    {
        return $this->approval_status === 'approved' && ($this->status === 'draft' || $this->status === 'ordered');
    }

    public function canBeDelivered()
    {
        return $this->status === 'confirmed';
    }

    public function canBeClosed()
    {
        return $this->status === 'delivered';
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

    public function checkCreditLimit()
    {
        // Get credit limit from business partner details
        $creditLimitDetail = $this->businessPartner->getDetailBySection('financial', 'credit_limit');
        $creditLimit = $creditLimitDetail ? (float) $creditLimitDetail->field_value : null;

        if (!$creditLimit) {
            return true; // No credit limit set
        }

        // Calculate current balance from unpaid invoices
        $currentBalance = $this->businessPartner->salesInvoices()
            ->where('status', '!=', 'paid')
            ->sum('total_amount');

        $orderAmount = $this->net_amount;

        return ($currentBalance + $orderAmount) <= $creditLimit;
    }

    public function getCustomerPricingTier()
    {
        // Get discount percentage from business partner details
        $discountDetail = $this->businessPartner->getDetailBySection('financial', 'discount_percentage');
        $discountPercentage = $discountDetail ? (float) $discountDetail->field_value : 0;

        // Create a simple pricing tier object
        return (object) [
            'discount_percentage' => $discountPercentage,
            'tier_name' => 'Default'
        ];
    }

    // Order type validation methods
    public function validateOrderTypeConsistency()
    {
        $lineTypes = $this->lines()->with('inventoryItem')->get()
            ->pluck('inventoryItem.item_type')->unique();

        if ($lineTypes->count() > 1) {
            throw new \Exception('Order lines must be of the same type (item or service)');
        }

        if ($lineTypes->first() !== $this->order_type) {
            throw new \Exception('Order type must match line item types');
        }
    }

    public function canCopyToDeliveryNote()
    {
        return $this->order_type === 'item' && $this->status === 'approved';
    }

    public function canCopyToSalesInvoice()
    {
        return $this->order_type === 'service' && $this->status === 'approved';
    }
}
