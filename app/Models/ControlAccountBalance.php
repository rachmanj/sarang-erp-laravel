<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ControlAccountBalance extends Model
{
    protected $fillable = [
        'control_account_id',
        'project_id',
        'dept_id',
        'balance',
        'last_reconciled_at',
        'reconciled_balance',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'reconciled_balance' => 'decimal:2',
        'last_reconciled_at' => 'datetime',
    ];

    // Relationships
    public function controlAccount(): BelongsTo
    {
        return $this->belongsTo(ControlAccount::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Dimensions\Project::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Dimensions\Department::class, 'dept_id');
    }

    // Helper methods
    public function getReconciliationVarianceAttribute()
    {
        if ($this->reconciled_balance === null) {
            return null;
        }
        return $this->balance - $this->reconciled_balance;
    }

    public function isReconciled($tolerance = 0.01)
    {
        if ($this->reconciled_balance === null) {
            return false;
        }
        return abs($this->getReconciliationVarianceAttribute()) <= $tolerance;
    }

    public function getDisplayNameAttribute()
    {
        $parts = [$this->controlAccount->account->code];
        
        if ($this->project) {
            $parts[] = "Project: {$this->project->name}";
        }
        
        if ($this->department) {
            $parts[] = "Dept: {$this->department->name}";
        }
        
        return implode(' | ', $parts);
    }
}
