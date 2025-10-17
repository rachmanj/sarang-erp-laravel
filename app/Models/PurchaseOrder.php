<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PurchaseOrder extends Model
{
    protected $fillable = [
        'order_no',
        'reference_no',
        'date',
        'expected_delivery_date',
        'actual_delivery_date',
        'business_partner_id',
        'warehouse_id',
        'currency_id',
        'exchange_rate',
        'description',
        'notes',
        'terms_conditions',
        'payment_terms',
        'delivery_method',
        'total_amount',
        'total_amount_foreign',
        'freight_cost',
        'freight_cost_foreign',
        'handling_cost',
        'handling_cost_foreign',
        'insurance_cost',
        'insurance_cost_foreign',
        'total_cost',
        'total_cost_foreign',
        'order_type',
        'status',
        'approval_status',
        'approved_by',
        'approved_at',
        'created_by',
        'updated_by',
        'closure_status',
        'closed_by_document_type',
        'closed_by_document_id',
        'closed_at',
        'closed_by_user_id'
    ];

    protected $casts = [
        'date' => 'date',
        'expected_delivery_date' => 'date',
        'actual_delivery_date' => 'date',
        'approved_at' => 'datetime',
        'closed_at' => 'datetime',
        'exchange_rate' => 'decimal:6',
        'total_amount' => 'decimal:2',
        'total_amount_foreign' => 'decimal:2',
        'freight_cost' => 'decimal:2',
        'freight_cost_foreign' => 'decimal:2',
        'handling_cost' => 'decimal:2',
        'handling_cost_foreign' => 'decimal:2',
        'insurance_cost' => 'decimal:2',
        'insurance_cost_foreign' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'total_cost_foreign' => 'decimal:2',
    ];

    // Relationships
    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseOrderLine::class, 'order_id');
    }

    public function businessPartner(): BelongsTo
    {
        return $this->belongsTo(\App\Models\BusinessPartner::class, 'business_partner_id');
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
    public function vendor(): BelongsTo
    {
        return $this->businessPartner();
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(PurchaseOrderApproval::class, 'purchase_order_id');
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

    public function scopeOrdered($query)
    {
        return $query->where('status', 'ordered');
    }

    public function scopeReceived($query)
    {
        return $query->where('status', 'received');
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    // Accessors
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
            $this->status !== 'received' &&
            $this->status !== 'closed';
    }

    public function getDeliveryStatusAttribute()
    {
        if ($this->status === 'received' || $this->status === 'closed') {
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

    // Methods
    public function canBeApproved()
    {
        return $this->approval_status === 'pending' && $this->status === 'draft';
    }

    public function canBeReceived()
    {
        return $this->approval_status === 'approved' && $this->status === 'ordered';
    }

    public function canBeClosed()
    {
        return $this->status === 'received';
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

    public function canCopyToGRPO()
    {
        return $this->order_type === 'item' && $this->status === 'approved';
    }

    public function canCopyToPurchaseInvoice()
    {
        return $this->order_type === 'service' && $this->status === 'approved';
    }

    // Closure status methods
    public function isOpen()
    {
        return $this->closure_status === 'open';
    }

    public function isClosed()
    {
        return $this->closure_status === 'closed';
    }

    public function getClosureInfo()
    {
        return [
            'status' => $this->closure_status,
            'closed_by_document_type' => $this->closed_by_document_type,
            'closed_by_document_id' => $this->closed_by_document_id,
            'closed_at' => $this->closed_at,
            'closed_by_user_id' => $this->closed_by_user_id,
        ];
    }

    public function getClosureHistory()
    {
        $history = [];

        if ($this->closure_status === 'closed') {
            $history[] = [
                'action' => 'closed',
                'document_type' => $this->closed_by_document_type,
                'document_id' => $this->closed_by_document_id,
                'closed_at' => $this->closed_at,
                'closed_by_user_id' => $this->closed_by_user_id,
            ];
        }

        return $history;
    }

    public function closedByUser()
    {
        return $this->belongsTo(User::class, 'closed_by_user_id');
    }
}
