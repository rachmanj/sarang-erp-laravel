<?php

namespace App\Models\Bank;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BankStatementLine extends Model
{
    protected $fillable = [
        'bank_statement_id',
        'posting_date',
        'value_date',
        'description',
        'reference_no',
        'amount',
        'direction',
        'running_balance',
        'match_status',
        'line_hash',
        'ai_meta',
    ];

    protected function casts(): array
    {
        return [
            'posting_date' => 'date',
            'value_date' => 'date',
            'amount' => 'decimal:2',
            'running_balance' => 'decimal:2',
            'ai_meta' => 'array',
        ];
    }

    public function statement(): BelongsTo
    {
        return $this->belongsTo(BankStatement::class, 'bank_statement_id');
    }

    public function match(): HasOne
    {
        return $this->hasOne(BankReconciliationMatch::class);
    }

    public function signedAmount(): float
    {
        return $this->direction === 'credit'
            ? (float) $this->amount
            : -1 * (float) $this->amount;
    }
}
