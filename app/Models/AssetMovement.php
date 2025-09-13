<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class AssetMovement extends Model
{
    protected $fillable = [
        'asset_id',
        'movement_date',
        'movement_type',
        'from_location',
        'to_location',
        'from_custodian',
        'to_custodian',
        'movement_reason',
        'notes',
        'reference_number',
        'created_by',
        'approved_by',
        'approved_at',
        'status',
    ];

    protected $casts = [
        'movement_date' => 'date',
        'approved_at' => 'datetime',
    ];

    // Relationships
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'asset_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Scopes
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('movement_date', [$startDate, $endDate]);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('movement_type', $type);
    }

    public function scopeByAsset($query, $assetId)
    {
        return $query->where('asset_id', $assetId);
    }

    // Helper methods
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function canBeApproved(): bool
    {
        return $this->status === 'draft';
    }

    public function canBeCompleted(): bool
    {
        return $this->status === 'approved';
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['draft', 'approved']);
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'draft' => '<span class="badge badge-warning">Draft</span>',
            'approved' => '<span class="badge badge-info">Approved</span>',
            'completed' => '<span class="badge badge-success">Completed</span>',
            'cancelled' => '<span class="badge badge-danger">Cancelled</span>',
            default => '<span class="badge badge-secondary">Unknown</span>',
        };
    }

    public function getMovementTypeDisplayAttribute(): string
    {
        return match ($this->movement_type) {
            'transfer' => 'Transfer',
            'relocation' => 'Relocation',
            'custodian_change' => 'Custodian Change',
            'maintenance' => 'Maintenance',
            'other' => 'Other',
            default => 'Unknown',
        };
    }

    public function getMovementSummaryAttribute(): string
    {
        $summary = '';

        if ($this->from_location && $this->to_location) {
            $summary .= "From: {$this->from_location} → To: {$this->to_location}";
        } elseif ($this->from_custodian && $this->to_custodian) {
            $summary .= "Custodian: {$this->from_custodian} → {$this->to_custodian}";
        } elseif ($this->to_location) {
            $summary .= "To: {$this->to_location}";
        } elseif ($this->to_custodian) {
            $summary .= "Custodian: {$this->to_custodian}";
        }

        return $summary;
    }

    public function getDaysSinceMovementAttribute(): int
    {
        return $this->movement_date->diffInDays(now());
    }

    // Approve movement
    public function approve(int $approvedBy): void
    {
        if (!$this->canBeApproved()) {
            throw new \InvalidArgumentException('Movement cannot be approved');
        }

        $this->update([
            'status' => 'approved',
            'approved_by' => $approvedBy,
            'approved_at' => now(),
        ]);
    }

    // Complete movement
    public function complete(): void
    {
        if (!$this->canBeCompleted()) {
            throw new \InvalidArgumentException('Movement cannot be completed');
        }

        $this->update([
            'status' => 'completed',
        ]);
    }

    // Cancel movement
    public function cancel(): void
    {
        if (!$this->canBeCancelled()) {
            throw new \InvalidArgumentException('Movement cannot be cancelled');
        }

        $this->update([
            'status' => 'cancelled',
        ]);
    }
}
