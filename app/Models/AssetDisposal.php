<?php

namespace App\Models;

use App\Models\Accounting\Journal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class AssetDisposal extends Model
{
    protected $fillable = [
        'asset_id',
        'disposal_date',
        'disposal_type',
        'disposal_proceeds',
        'book_value_at_disposal',
        'gain_loss_amount',
        'gain_loss_type',
        'disposal_reason',
        'disposal_method',
        'disposal_reference',
        'journal_id',
        'created_by',
        'posted_by',
        'posted_at',
        'status',
        'notes',
    ];

    protected $casts = [
        'disposal_date' => 'date',
        'disposal_proceeds' => 'decimal:2',
        'book_value_at_disposal' => 'decimal:2',
        'gain_loss_amount' => 'decimal:2',
        'posted_at' => 'datetime',
    ];

    // Relationships
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'asset_id');
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class, 'journal_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    // Scopes
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopePosted($query)
    {
        return $query->where('status', 'posted');
    }

    public function scopeReversed($query)
    {
        return $query->where('status', 'reversed');
    }

    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('disposal_date', [$startDate, $endDate]);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('disposal_type', $type);
    }

    // Helper methods
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isPosted(): bool
    {
        return $this->status === 'posted';
    }

    public function isReversed(): bool
    {
        return $this->status === 'reversed';
    }

    public function canBePosted(): bool
    {
        return $this->status === 'draft' && !is_null($this->gain_loss_amount);
    }

    public function canBeReversed(): bool
    {
        return $this->status === 'posted' && !is_null($this->journal_id);
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'draft' => '<span class="badge badge-warning">Draft</span>',
            'posted' => '<span class="badge badge-success">Posted</span>',
            'reversed' => '<span class="badge badge-danger">Reversed</span>',
            default => '<span class="badge badge-secondary">Unknown</span>',
        };
    }

    public function getDisposalTypeDisplayAttribute(): string
    {
        return match ($this->disposal_type) {
            'sale' => 'Sale',
            'scrap' => 'Scrap',
            'donation' => 'Donation',
            'trade_in' => 'Trade-in',
            'other' => 'Other',
            default => 'Unknown',
        };
    }

    public function getGainLossDisplayAttribute(): string
    {
        $amount = number_format(abs($this->gain_loss_amount), 2);
        $type = $this->gain_loss_type;

        return match ($type) {
            'gain' => "<span class='text-success'>Gain: {$amount}</span>",
            'loss' => "<span class='text-danger'>Loss: {$amount}</span>",
            'neutral' => "<span class='text-muted'>No Gain/Loss</span>",
            default => 'Unknown',
        };
    }

    // Calculate gain/loss based on disposal proceeds and book value
    public function calculateGainLoss(): array
    {
        $proceeds = $this->disposal_proceeds ?? 0;
        $bookValue = $this->book_value_at_disposal;

        $difference = $proceeds - $bookValue;

        if ($difference > 0) {
            return [
                'amount' => $difference,
                'type' => 'gain'
            ];
        } elseif ($difference < 0) {
            return [
                'amount' => abs($difference),
                'type' => 'loss'
            ];
        } else {
            return [
                'amount' => 0,
                'type' => 'neutral'
            ];
        }
    }

    // Boot method to auto-calculate gain/loss
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($disposal) {
            if ($disposal->disposal_proceeds !== null && $disposal->book_value_at_disposal !== null) {
                $gainLoss = $disposal->calculateGainLoss();
                $disposal->gain_loss_amount = $gainLoss['amount'];
                $disposal->gain_loss_type = $gainLoss['type'];
            }
        });
    }
}
