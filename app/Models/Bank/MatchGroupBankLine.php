<?php

namespace App\Models\Bank;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchGroupBankLine extends Model
{
    protected $fillable = [
        'reconciliation_match_group_id',
        'bank_statement_line_id',
    ];

    public function matchGroup(): BelongsTo
    {
        return $this->belongsTo(ReconciliationMatchGroup::class, 'reconciliation_match_group_id');
    }

    public function bankStatementLine(): BelongsTo
    {
        return $this->belongsTo(BankStatementLine::class);
    }
}
