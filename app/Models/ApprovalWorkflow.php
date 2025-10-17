<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApprovalWorkflow extends Model
{
    protected $fillable = [
        'document_type',
        'workflow_name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function steps(): HasMany
    {
        return $this->hasMany(ApprovalWorkflowStep::class, 'workflow_id')
            ->orderBy('step_order');
    }

    public static function getActiveWorkflow(string $documentType): ?self
    {
        return static::where('document_type', $documentType)
            ->where('is_active', true)
            ->with('steps')
            ->first();
    }
}
