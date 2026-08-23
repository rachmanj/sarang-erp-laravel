<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashExpense extends Model
{
    protected $fillable = [
        'expense_no',
        'date',
        'description',
        'cash_account_id',
        'total_amount',
        'status',
        'created_by',
        'company_entity_id',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
        ];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(CashExpenseLine::class);
    }

    public function cashAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'cash_account_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function companyEntity(): BelongsTo
    {
        return $this->belongsTo(\App\Models\CompanyEntity::class, 'company_entity_id');
    }
}
