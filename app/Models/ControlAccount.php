<?php

namespace App\Models;

use App\Models\Accounting\Account;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ControlAccount extends Model
{
    protected $fillable = [
        'account_id',
        'control_type',
        'is_active',
        'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relationships
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function subsidiaryAccounts(): HasMany
    {
        return $this->hasMany(SubsidiaryLedgerAccount::class);
    }

    public function balances(): HasMany
    {
        return $this->hasMany(ControlAccountBalance::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('control_type', $type);
    }

    // Helper methods
    public function getDisplayNameAttribute()
    {
        return "{$this->account->code} - {$this->account->name} ({$this->control_type})";
    }

    public function getCurrentBalance($projectId = null, $deptId = null)
    {
        $balance = $this->balances()
            ->where('project_id', $projectId)
            ->where('dept_id', $deptId)
            ->first();

        return $balance ? $balance->balance : 0;
    }
}
