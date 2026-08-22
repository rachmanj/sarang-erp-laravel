<?php

namespace App\Models\Audit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditResult extends Model
{
    protected $fillable = [
        'audit_run_id',
        'check_key',
        'check_name',
        'status',
        'issue_count',
        'details',
    ];

    protected function casts(): array
    {
        return [
            'audit_run_id' => 'integer',
            'issue_count' => 'integer',
        ];
    }

    public function auditRun(): BelongsTo
    {
        return $this->belongsTo(AuditRun::class);
    }
}
