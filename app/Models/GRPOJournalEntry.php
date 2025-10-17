<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GRPOJournalEntry extends Model
{
    protected $table = 'grpo_journal_entries';

    protected $fillable = [
        'grpo_id',
        'grpo_line_id',
        'journal_id',
        'journal_line_id',
        'amount',
        'account_type',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    // Relationships
    public function grpo(): BelongsTo
    {
        return $this->belongsTo(GoodsReceiptPO::class, 'grpo_id');
    }

    public function grpoLine(): BelongsTo
    {
        return $this->belongsTo(GoodsReceiptPOLine::class, 'grpo_line_id');
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Accounting\Journal::class, 'journal_id');
    }

    public function journalLine(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Accounting\JournalLine::class, 'journal_line_id');
    }

    // Scopes
    public function scopeInventory($query)
    {
        return $query->where('account_type', 'inventory');
    }

    public function scopeLiability($query)
    {
        return $query->where('account_type', 'liability');
    }

    public function scopeForGRPO($query, $grpoId)
    {
        return $query->where('grpo_id', $grpoId);
    }
}
