<?php

namespace App\Models\Bank;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchGroupBookLine extends Model
{
    protected $fillable = [
        'reconciliation_match_group_id',
        'bank_book_line_id',
    ];

    public function matchGroup(): BelongsTo
    {
        return $this->belongsTo(ReconciliationMatchGroup::class, 'reconciliation_match_group_id');
    }

    public function bankBookLine(): BelongsTo
    {
        return $this->belongsTo(BankBookLine::class);
    }
}
