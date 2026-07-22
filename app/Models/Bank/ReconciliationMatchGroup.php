<?php

namespace App\Models\Bank;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReconciliationMatchGroup extends Model
{
    public const TYPE_AUTO_EXACT = 'auto_exact';

    public const TYPE_AUTO_FUZZY = 'auto_fuzzy';

    public const TYPE_AUTO_SPLIT = 'auto_split';

    public const TYPE_AUTO_REFERENCE = 'auto_reference';

    public const TYPE_MANUAL = 'manual';

    protected $fillable = [
        'bank_reconciliation_id',
        'match_type',
        'confidence_score',
        'bank_total',
        'book_total',
        'difference',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'bank_total' => 'decimal:2',
            'book_total' => 'decimal:2',
            'difference' => 'decimal:2',
        ];
    }

    public function reconciliation(): BelongsTo
    {
        return $this->belongsTo(BankReconciliation::class, 'bank_reconciliation_id');
    }

    public function bankLineLinks(): HasMany
    {
        return $this->hasMany(MatchGroupBankLine::class);
    }

    public function bookLineLinks(): HasMany
    {
        return $this->hasMany(MatchGroupBookLine::class);
    }

    public function bankLines()
    {
        return $this->hasManyThrough(
            BankStatementLine::class,
            MatchGroupBankLine::class,
            'reconciliation_match_group_id',
            'id',
            'id',
            'bank_statement_line_id',
        );
    }

    public function bookLines()
    {
        return $this->hasManyThrough(
            BankBookLine::class,
            MatchGroupBookLine::class,
            'reconciliation_match_group_id',
            'id',
            'id',
            'bank_book_line_id',
        );
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isAutoGroup(): bool
    {
        return in_array($this->match_type, [
            self::TYPE_AUTO_EXACT,
            self::TYPE_AUTO_FUZZY,
            self::TYPE_AUTO_SPLIT,
            self::TYPE_AUTO_REFERENCE,
        ], true);
    }
}
