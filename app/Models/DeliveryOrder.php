<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DeliveryOrder extends Model
{
    protected $fillable = [
        'do_number',
        'sales_order_id',
        'business_partner_id',
        'delivery_address',
        'delivery_contact_person',
        'delivery_phone',
        'planned_delivery_date',
        'actual_delivery_date',
        'delivery_method',
        'delivery_instructions',
        'logistics_cost',
        'status',
        'approval_status',
        'approved_by',
        'approved_at',
        'created_by',
        'notes',
    ];

    protected $casts = [
        'planned_delivery_date' => 'date',
        'actual_delivery_date' => 'date',
        'logistics_cost' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    // Relationships
    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function businessPartner(): BelongsTo
    {
        return $this->belongsTo(\App\Models\BusinessPartner::class);
    }

    // Backward compatibility method
    public function customer(): BelongsTo
    {
        return $this->businessPartner();
    }

    public function lines(): HasMany
    {
        return $this->hasMany(DeliveryOrderLine::class, 'delivery_order_id');
    }

    public function tracking(): HasOne
    {
        return $this->hasOne(DeliveryTracking::class, 'delivery_order_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Accessors
    public function getTotalAmountAttribute()
    {
        return $this->lines->sum('amount');
    }

    public function getTotalQuantityAttribute()
    {
        return $this->lines->sum('delivered_qty');
    }

    public function getDeliveryProgressAttribute()
    {
        $totalLines = $this->lines->count();
        if ($totalLines == 0) return 0;

        $deliveredLines = $this->lines->where('status', 'delivered')->count();
        return round(($deliveredLines / $totalLines) * 100);
    }

    public function getIsFullyDeliveredAttribute()
    {
        return $this->lines->every(function ($line) {
            return $line->status === 'delivered';
        });
    }

    public function getIsPartiallyDeliveredAttribute()
    {
        return $this->lines->some(function ($line) {
            return $line->status === 'delivered';
        }) && !$this->is_fully_delivered;
    }

    // Methods
    public function updateStatus($status)
    {
        $this->status = $status;

        if ($status === 'delivered') {
            $this->actual_delivery_date = now();
        }

        $this->save();
    }

    public function canBeDelivered()
    {
        return in_array($this->status, ['ready', 'in_transit']);
    }

    public function canBeCancelled()
    {
        return in_array($this->status, ['draft', 'picking', 'packed']);
    }
}
