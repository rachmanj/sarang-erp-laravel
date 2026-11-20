<?php

namespace App\Models\Accounting;

use App\Models\BusinessPartner;
use App\Models\CompanyEntity;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseAdvance extends Model
{
    protected $fillable = [
        'advance_no',
        'date',
        'business_partner_id',
        'company_entity_id',
        'purchase_order_id',
        'currency_id',
        'exchange_rate',
        'total_amount',
        'description',
        'status',
        'posted_at',
        'journal_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'posted_at' => 'datetime',
            'total_amount' => 'float',
            'exchange_rate' => 'float',
        ];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseAdvanceLine::class, 'advance_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(PurchaseAdvanceApplication::class, 'advance_id');
    }

    public function businessPartner(): BelongsTo
    {
        return $this->belongsTo(BusinessPartner::class, 'business_partner_id');
    }

    public function companyEntity(): BelongsTo
    {
        return $this->belongsTo(CompanyEntity::class, 'company_entity_id');
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
