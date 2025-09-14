<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Master\TaxCode;

class TaxTransaction extends Model
{
    protected $fillable = [
        'transaction_no',
        'transaction_date',
        'transaction_type',
        'tax_type',
        'tax_category',
        'reference_id',
        'reference_type',
        'vendor_id',
        'customer_id',
        'tax_number',
        'tax_name',
        'tax_address',
        'taxable_amount',
        'tax_rate',
        'tax_amount',
        'total_amount',
        'status',
        'due_date',
        'paid_date',
        'payment_method',
        'payment_reference',
        'notes',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'due_date' => 'date',
        'paid_date' => 'date',
        'taxable_amount' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    // Relationships
    public function taxCode(): BelongsTo
    {
        return $this->belongsTo(TaxCode::class, 'tax_code_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Master\Vendor::class, 'vendor_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Master\Customer::class, 'customer_id');
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

    public function scopeRefunded($query)
    {
        return $query->where('status', 'refunded');
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('transaction_type', $type);
    }

    public function scopeByTaxType($query, $taxType)
    {
        return $query->where('tax_type', $taxType);
    }

    public function scopeByTaxCategory($query, $taxCategory)
    {
        return $query->where('tax_category', $taxCategory);
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now()->toDateString())
            ->where('status', '!=', 'paid');
    }

    // Accessors
    public function getIsOverdueAttribute()
    {
        return $this->due_date &&
            $this->due_date < now()->toDateString() &&
            $this->status !== 'paid';
    }

    public function getDaysOverdueAttribute()
    {
        if (!$this->is_overdue) {
            return 0;
        }

        return now()->diffInDays($this->due_date);
    }

    public function getFormattedTaxAmountAttribute()
    {
        return 'Rp ' . number_format($this->tax_amount, 2);
    }

    public function getFormattedTotalAmountAttribute()
    {
        return 'Rp ' . number_format($this->total_amount, 2);
    }

    // Methods
    public function markAsPaid($paymentMethod = null, $paymentReference = null)
    {
        $this->update([
            'status' => 'paid',
            'paid_date' => now()->toDateString(),
            'payment_method' => $paymentMethod,
            'payment_reference' => $paymentReference,
        ]);
    }

    public function markAsRefunded()
    {
        $this->update(['status' => 'refunded']);
    }

    public function calculateTax()
    {
        $this->tax_amount = ($this->taxable_amount * $this->tax_rate) / 100;
        $this->total_amount = $this->taxable_amount + $this->tax_amount;
        $this->save();
    }

    public static function generateTransactionNumber($taxType, $taxCategory)
    {
        $prefix = strtoupper($taxType . '_' . $taxCategory);
        $year = date('Y');
        $month = date('m');

        $lastTransaction = self::where('transaction_no', 'like', $prefix . '_' . $year . $month . '%')
            ->orderBy('transaction_no', 'desc')
            ->first();

        $sequence = $lastTransaction ?
            (int)substr($lastTransaction->transaction_no, -6) + 1 : 1;

        return $prefix . '_' . $year . $month . str_pad($sequence, 6, '0', STR_PAD_LEFT);
    }
}
