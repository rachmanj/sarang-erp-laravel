<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashExpense extends Model
{
    protected $fillable = ['date', 'description', 'account_id', 'amount', 'status', 'created_by'];

    public function expenseAccount(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Accounting\Account::class, 'account_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Dimensions\Project::class, 'project_id');
    }

    public function fund(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Dimensions\Fund::class, 'fund_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Dimensions\Department::class, 'dept_id');
    }
}
