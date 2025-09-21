<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GRGIJournalEntry extends Model
{
    protected $table = 'gr_gi_journal_entries';

    protected $fillable = [
        'header_id',
        'line_id',
        'gr_gi_type',
        'journal_entry_id',
    ];

    // Relationships
    public function header(): BelongsTo
    {
        return $this->belongsTo(GRGIHeader::class, 'header_id');
    }

    public function line(): BelongsTo
    {
        return $this->belongsTo(GRGILine::class, 'line_id');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(Journal::class, 'journal_entry_id');
    }

    // Scopes
    public function scopeGoodsReceipt($query)
    {
        return $query->where('gr_gi_type', 'goods_receipt');
    }

    public function scopeGoodsIssue($query)
    {
        return $query->where('gr_gi_type', 'goods_issue');
    }

    public function scopeForHeader($query, $headerId)
    {
        return $query->where('header_id', $headerId);
    }
}
