<?php

namespace App\Models\Bank;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BankStatementLine extends Model
{
    public const MATCH_UNMATCHED = 'unmatched';

    public const MATCH_MATCHED = 'matched';

    public const MATCH_MANUAL = 'manual';

    public const MATCH_EXCLUDED = 'excluded';

    public const MATCH_OUTSTANDING = 'outstanding';

    protected $fillable = [
        'bank_reconciliation_id',
        'bank_statement_id',
        'posting_date',
        'value_date',
        'description',
        'reference_no',
        'amount',
        'direction',
        'debit',
        'credit',
        'running_balance',
        'match_status',
        'exclude_reason',
        'line_notes',
        'line_order',
        'is_ai_extracted',
        'ai_confidence',
        'line_hash',
        'ai_meta',
        'adjusting_journal_id',
        'is_carried_forward',
        'carried_from_bank_line_id',
        'origin_reconciliation_id',
    ];

    protected function casts(): array
    {
        return [
            'posting_date' => 'date',
            'value_date' => 'date',
            'amount' => 'decimal:2',
            'debit' => 'decimal:2',
            'credit' => 'decimal:2',
            'running_balance' => 'decimal:2',
            'is_ai_extracted' => 'boolean',
            'ai_meta' => 'array',
            'is_carried_forward' => 'boolean',
        ];
    }

    public function reconciliation(): BelongsTo
    {
        return $this->belongsTo(BankReconciliation::class, 'bank_reconciliation_id');
    }

    public function statement(): BelongsTo
    {
        return $this->belongsTo(BankStatement::class, 'bank_statement_id');
    }

    public function matchGroupLink(): HasOne
    {
        return $this->hasOne(MatchGroupBankLine::class);
    }

    public function carriedFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'carried_from_bank_line_id');
    }

    public function originReconciliation(): BelongsTo
    {
        return $this->belongsTo(BankReconciliation::class, 'origin_reconciliation_id');
    }

    public function netAmount(): float
    {
        return round((float) $this->debit - (float) $this->credit, 2);
    }

    public function signedAmount(): float
    {
        return $this->netAmount();
    }

    public function isAvailableForMatching(): bool
    {
        return $this->match_status === self::MATCH_UNMATCHED;
    }

    public function canMarkOutstanding(): bool
    {
        return in_array($this->match_status, [self::MATCH_UNMATCHED, self::MATCH_OUTSTANDING], true);
    }
}
