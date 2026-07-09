<?php

namespace App\Models\Bank;

use App\Models\Accounting\JournalLine;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BankBookLine extends Model
{
    public const MATCH_UNMATCHED = 'unmatched';

    public const MATCH_MATCHED = 'matched';

    public const MATCH_MANUAL = 'manual';

    public const MATCH_EXCLUDED = 'excluded';

    protected $fillable = [
        'bank_reconciliation_id',
        'journal_line_id',
        'doc_date',
        'posting_date',
        'doc_num',
        'ref_doc_num',
        'transaction_id',
        'description',
        'project_code',
        'debit',
        'credit',
        'match_status',
        'exclude_reason',
        'line_notes',
    ];

    protected function casts(): array
    {
        return [
            'doc_date' => 'date',
            'posting_date' => 'date',
            'debit' => 'decimal:2',
            'credit' => 'decimal:2',
        ];
    }

    public function reconciliation(): BelongsTo
    {
        return $this->belongsTo(BankReconciliation::class, 'bank_reconciliation_id');
    }

    public function journalLine(): BelongsTo
    {
        return $this->belongsTo(JournalLine::class);
    }

    public function matchGroupLink(): HasOne
    {
        return $this->hasOne(MatchGroupBookLine::class);
    }

    public function netAmount(): float
    {
        return round((float) $this->debit - (float) $this->credit, 2);
    }

    public function isAvailableForMatching(): bool
    {
        return $this->match_status === self::MATCH_UNMATCHED;
    }
}
