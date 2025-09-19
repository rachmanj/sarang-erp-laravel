<?php

namespace App\Models;

use App\Models\Accounting\Account;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SubsidiaryLedgerAccount extends Model
{
    protected $fillable = [
        'control_account_id',
        'subsidiary_type',
        'subsidiary_id',
        'account_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relationships
    public function controlAccount(): BelongsTo
    {
        return $this->belongsTo(ControlAccount::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function subsidiary(): MorphTo
    {
        return $this->morphTo();
    }

    // Helper methods to get the actual subsidiary entity
    public function getSubsidiaryEntity()
    {
        switch ($this->subsidiary_type) {
            case 'business_partner':
                return \App\Models\BusinessPartner::find($this->subsidiary_id);
            case 'inventory_item':
                return \App\Models\InventoryItem::find($this->subsidiary_id);
            case 'fixed_asset':
                return \App\Models\Asset::find($this->subsidiary_id);
            default:
                return null;
        }
    }

    public function getDisplayNameAttribute()
    {
        $subsidiary = $this->getSubsidiaryEntity();
        if ($subsidiary) {
            return "{$this->account->code} - {$subsidiary->name} ({$this->subsidiary_type})";
        }
        return "{$this->account->code} - Unknown ({$this->subsidiary_type})";
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('subsidiary_type', $type);
    }

    public function scopeByControlAccount($query, $controlAccountId)
    {
        return $query->where('control_account_id', $controlAccountId);
    }
}
