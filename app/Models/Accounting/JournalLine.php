<?php

namespace App\Models\Accounting;

use App\Models\Currency;
use App\Models\Dimensions\Department;
use App\Models\Dimensions\Project;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalLine extends Model
{
    protected $auditLogIgnore = ['updated_at', 'created_at'];

    protected $auditEntityType = 'journal_line';

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function dept(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'dept_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }
}
