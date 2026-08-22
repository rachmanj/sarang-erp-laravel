<?php

namespace App\Models\Audit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AuditRun extends Model
{
    protected $fillable = [
        'status',
        'started_at',
        'finished_at',
        'triggered_by',
        'total_checks',
        'passed_checks',
        'failed_checks',
        'total_issues',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'total_checks' => 'integer',
            'passed_checks' => 'integer',
            'failed_checks' => 'integer',
            'total_issues' => 'integer',
        ];
    }

    public function results(): HasMany
    {
        return $this->hasMany(AuditResult::class);
    }
}
