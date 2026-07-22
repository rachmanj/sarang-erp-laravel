<?php

namespace App\Models\Bank;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankReconciliation extends Model
{
    public const STATUS_PROCESSING = 'processing';

    public const STATUS_IN_REVIEW = 'in_review';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const SOURCE_AI = 'ai';

    public const SOURCE_MANUAL = 'manual';

    protected $fillable = [
        'bank_account_id',
        'bank_statement_id',
        'periode',
        'period_start',
        'period_end',
        'statement_opening',
        'statement_closing',
        'book_balance',
        'status',
        'source_mode',
        'opening_balance_bank',
        'closing_balance_bank',
        'opening_balance_book',
        'closing_balance_book',
        'notes',
        'created_by',
        'finalized_by',
        'finalized_at',
        'company_entity_id',
    ];

    protected function casts(): array
    {
        return [
            'periode' => 'date',
            'period_start' => 'date',
            'period_end' => 'date',
            'statement_opening' => 'decimal:2',
            'statement_closing' => 'decimal:2',
            'book_balance' => 'decimal:2',
            'opening_balance_bank' => 'decimal:2',
            'closing_balance_bank' => 'decimal:2',
            'opening_balance_book' => 'decimal:2',
            'closing_balance_book' => 'decimal:2',
            'finalized_at' => 'datetime',
        ];
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function statement(): BelongsTo
    {
        return $this->belongsTo(BankStatement::class, 'bank_statement_id');
    }

    public function bankLines(): HasMany
    {
        return $this->hasMany(BankStatementLine::class);
    }

    public function bookLines(): HasMany
    {
        return $this->hasMany(BankBookLine::class);
    }

    public function matchGroups(): HasMany
    {
        return $this->hasMany(ReconciliationMatchGroup::class);
    }

    public function matchAudits(): HasMany
    {
        return $this->hasMany(ReconciliationMatchAudit::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function finalizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    public function isLockedForEditing(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function periodStartDate(): \Illuminate\Support\Carbon
    {
        return $this->periode->copy()->startOfMonth();
    }

    public function periodEndDate(): \Illuminate\Support\Carbon
    {
        return $this->periode->copy()->endOfMonth();
    }
}
