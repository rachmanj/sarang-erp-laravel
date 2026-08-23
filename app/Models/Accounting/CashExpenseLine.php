<?php

namespace App\Models\Accounting;

use App\Models\Dimensions\Department;
use App\Models\Dimensions\Project;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashExpenseLine extends Model
{
    protected $fillable = [
        'cash_expense_id',
        'account_id',
        'amount',
        'description',
        'project_id',
        'dept_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function cashExpense(): BelongsTo
    {
        return $this->belongsTo(CashExpense::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'dept_id');
    }
}
