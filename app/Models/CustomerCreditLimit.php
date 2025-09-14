<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerCreditLimit extends Model
{
    protected $fillable = [
        'customer_id',
        'credit_limit',
        'current_balance',
        'available_credit',
        'payment_terms_days',
        'credit_status',
        'notes'
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'available_credit' => 'decimal:2',
        'payment_terms_days' => 'integer',
    ];

    // Relationships
    public function customer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Master\Customer::class, 'customer_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('credit_status', 'active');
    }

    public function scopeSuspended($query)
    {
        return $query->where('credit_status', 'suspended');
    }

    public function scopeBlocked($query)
    {
        return $query->where('credit_status', 'blocked');
    }

    // Accessors
    public function getAvailableCreditAttribute()
    {
        return $this->credit_limit - $this->current_balance;
    }

    public function getCreditUtilizationAttribute()
    {
        if ($this->credit_limit == 0) {
            return 0;
        }

        return round(($this->current_balance / $this->credit_limit) * 100, 2);
    }

    public function getCreditStatusColorAttribute()
    {
        switch ($this->credit_status) {
            case 'active':
                return $this->credit_utilization > 90 ? 'warning' : 'success';
            case 'suspended':
                return 'warning';
            case 'blocked':
                return 'danger';
            default:
                return 'secondary';
        }
    }

    // Methods
    public function canExtendCredit($amount)
    {
        return $this->credit_status === 'active' &&
            ($this->current_balance + $amount) <= $this->credit_limit;
    }

    public function updateBalance($amount)
    {
        $this->current_balance += $amount;
        $this->available_credit = $this->credit_limit - $this->current_balance;
        $this->save();
    }

    public function suspend($reason = null)
    {
        $this->update([
            'credit_status' => 'suspended',
            'notes' => $reason ? ($this->notes . "\nSuspended: " . $reason) : $this->notes,
        ]);
    }

    public function block($reason = null)
    {
        $this->update([
            'credit_status' => 'blocked',
            'notes' => $reason ? ($this->notes . "\nBlocked: " . $reason) : $this->notes,
        ]);
    }

    public function activate($reason = null)
    {
        $this->update([
            'credit_status' => 'active',
            'notes' => $reason ? ($this->notes . "\nActivated: " . $reason) : $this->notes,
        ]);
    }
}
