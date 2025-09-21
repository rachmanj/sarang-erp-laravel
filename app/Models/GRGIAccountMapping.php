<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Accounting\Account;

class GRGIAccountMapping extends Model
{
    protected $table = 'gr_gi_account_mappings';

    protected $fillable = [
        'purpose_id',
        'item_category_id',
        'debit_account_id',
        'credit_account_id',
    ];

    // Relationships
    public function purpose(): BelongsTo
    {
        return $this->belongsTo(GRGIPurpose::class, 'purpose_id');
    }

    public function itemCategory(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'item_category_id');
    }

    public function debitAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'debit_account_id');
    }

    public function creditAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'credit_account_id');
    }

    // Scopes
    public function scopeForPurpose($query, $purposeId)
    {
        return $query->where('purpose_id', $purposeId);
    }

    public function scopeForCategory($query, $categoryId)
    {
        return $query->where('item_category_id', $categoryId);
    }

    // Methods
    public function getAccountMapping($purposeId, $itemCategoryId)
    {
        return $this->where('purpose_id', $purposeId)
            ->where('item_category_id', $itemCategoryId)
            ->first();
    }
}
