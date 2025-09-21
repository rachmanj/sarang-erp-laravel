<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GRGIHeader extends Model
{
    protected $table = 'gr_gi_headers';

    protected $fillable = [
        'document_number',
        'document_type',
        'purpose_id',
        'warehouse_id',
        'transaction_date',
        'reference_number',
        'notes',
        'total_amount',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
        'cancelled_by',
        'cancelled_at',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'total_amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    // Relationships
    public function purpose(): BelongsTo
    {
        return $this->belongsTo(GRGIPurpose::class, 'purpose_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(GRGILine::class, 'header_id');
    }

    public function journalEntries(): HasMany
    {
        return $this->hasMany(GRGIJournalEntry::class, 'header_id');
    }

    // Scopes
    public function scopeGoodsReceipt($query)
    {
        return $query->where('document_type', 'goods_receipt');
    }

    public function scopeGoodsIssue($query)
    {
        return $query->where('document_type', 'goods_issue');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopePendingApproval($query)
    {
        return $query->where('status', 'pending_approval');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    // Accessors
    public function getDocumentTypeNameAttribute()
    {
        return $this->document_type === 'goods_receipt' ? 'Goods Receipt' : 'Goods Issue';
    }

    public function getStatusBadgeAttribute()
    {
        $badges = [
            'draft' => 'badge-secondary',
            'pending_approval' => 'badge-warning',
            'approved' => 'badge-success',
            'cancelled' => 'badge-danger',
        ];

        return $badges[$this->status] ?? 'badge-secondary';
    }

    // Methods
    public function canBeEdited()
    {
        return in_array($this->status, ['draft', 'pending_approval']);
    }

    public function canBeApproved()
    {
        return $this->status === 'pending_approval';
    }

    public function canBeCancelled()
    {
        return in_array($this->status, ['draft', 'pending_approval']);
    }
}
