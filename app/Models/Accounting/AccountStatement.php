<?php

namespace App\Models\Accounting;

use App\Models\BusinessPartner;
use App\Models\Project;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountStatement extends Model
{
    protected $fillable = [
        'statement_no',
        'statement_type',
        'account_id',
        'business_partner_id',
        'statement_date',
        'from_date',
        'to_date',
        'opening_balance',
        'closing_balance',
        'total_debits',
        'total_credits',
        'status',
        'notes',
        'created_by',
        'finalized_by',
        'finalized_at',
    ];

    protected $casts = [
        'statement_date' => 'date',
        'from_date' => 'date',
        'to_date' => 'date',
        'opening_balance' => 'decimal:2',
        'closing_balance' => 'decimal:2',
        'total_debits' => 'decimal:2',
        'total_credits' => 'decimal:2',
        'finalized_at' => 'datetime',
    ];

    // Relationships
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function businessPartner(): BelongsTo
    {
        return $this->belongsTo(BusinessPartner::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function finalizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(AccountStatementLine::class);
    }

    // Scopes
    public function scopeGlAccounts($query)
    {
        return $query->where('statement_type', 'gl_account');
    }

    public function scopeBusinessPartners($query)
    {
        return $query->where('statement_type', 'business_partner');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeFinalized($query)
    {
        return $query->where('status', 'finalized');
    }

    public function scopeByDateRange($query, $fromDate, $toDate)
    {
        return $query->whereBetween('statement_date', [$fromDate, $toDate]);
    }

    public function scopeForAccount($query, $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    public function scopeForBusinessPartner($query, $businessPartnerId)
    {
        return $query->where('business_partner_id', $businessPartnerId);
    }

    // Accessors
    public function getNetMovementAttribute()
    {
        return $this->total_debits - $this->total_credits;
    }

    public function getIsFinalizedAttribute()
    {
        return $this->status === 'finalized';
    }

    public function getIsDraftAttribute()
    {
        return $this->status === 'draft';
    }

    public function getDisplayNameAttribute()
    {
        if ($this->statement_type === 'gl_account' && $this->account) {
            return "{$this->account->code} - {$this->account->name}";
        } elseif ($this->statement_type === 'business_partner' && $this->businessPartner) {
            return "{$this->businessPartner->code} - {$this->businessPartner->name}";
        }
        return $this->statement_no;
    }

    // Helper methods
    public function canBeFinalized(): bool
    {
        return $this->status === 'draft' && $this->lines()->count() > 0;
    }

    public function finalize($userId = null): bool
    {
        if (!$this->canBeFinalized()) {
            return false;
        }

        $this->update([
            'status' => 'finalized',
            'finalized_by' => $userId,
            'finalized_at' => now(),
        ]);

        return true;
    }

    public function cancel(): bool
    {
        if ($this->status === 'finalized') {
            return false;
        }

        $this->update(['status' => 'cancelled']);
        return true;
    }

    public function recalculateBalances(): void
    {
        $lines = $this->lines()->orderBy('transaction_date')->orderBy('sort_order')->get();

        $runningBalance = $this->opening_balance;
        $totalDebits = 0;
        $totalCredits = 0;

        foreach ($lines as $line) {
            $runningBalance += $line->debit_amount - $line->credit_amount;
            $totalDebits += $line->debit_amount;
            $totalCredits += $line->credit_amount;

            $line->update(['running_balance' => $runningBalance]);
        }

        $this->update([
            'total_debits' => $totalDebits,
            'total_credits' => $totalCredits,
            'closing_balance' => $runningBalance,
        ]);
    }
}
