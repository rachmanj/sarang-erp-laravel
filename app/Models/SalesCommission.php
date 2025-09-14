<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesCommission extends Model
{
    protected $fillable = [
        'sales_order_id',
        'salesperson_id',
        'commission_rate',
        'commission_amount',
        'status',
        'payment_date',
        'notes'
    ];

    protected $casts = [
        'commission_rate' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    // Relationships
    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id');
    }

    public function salesperson(): BelongsTo
    {
        return $this->belongsTo(User::class, 'salesperson_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeForSalesperson($query, $salespersonId)
    {
        return $query->where('salesperson_id', $salespersonId);
    }

    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->whereHas('salesOrder', function ($q) use ($startDate, $endDate) {
            $q->whereBetween('date', [$startDate, $endDate]);
        });
    }

    // Accessors
    public function getFormattedCommissionAmountAttribute()
    {
        return 'Rp ' . number_format($this->commission_amount, 2);
    }

    public function getStatusColorAttribute()
    {
        switch ($this->status) {
            case 'pending':
                return 'warning';
            case 'approved':
                return 'info';
            case 'paid':
                return 'success';
            default:
                return 'secondary';
        }
    }

    // Methods
    public function approve()
    {
        $this->update(['status' => 'approved']);
    }

    public function pay($paymentDate = null)
    {
        $this->update([
            'status' => 'paid',
            'payment_date' => $paymentDate ?? now()->toDateString(),
        ]);
    }

    public function calculateCommission($orderAmount, $commissionRate = null)
    {
        $rate = $commissionRate ?? $this->commission_rate;
        return ($orderAmount * $rate) / 100;
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isApproved()
    {
        return $this->status === 'approved';
    }

    public function isPaid()
    {
        return $this->status === 'paid';
    }

    public static function getTotalCommissions($salespersonId, $startDate = null, $endDate = null)
    {
        $query = self::forSalesperson($salespersonId);

        if ($startDate && $endDate) {
            $query = $query->forPeriod($startDate, $endDate);
        }

        return $query->sum('commission_amount');
    }

    public static function getPendingCommissions($salespersonId)
    {
        return self::forSalesperson($salespersonId)->pending()->sum('commission_amount');
    }
}
