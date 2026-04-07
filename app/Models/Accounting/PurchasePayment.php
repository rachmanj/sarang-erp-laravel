<?php

namespace App\Models\Accounting;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchasePayment extends Model
{
    protected $fillable = [
        'payment_no',
        'date',
        'business_partner_id',
        'company_entity_id',
        'created_by',
        'description',
        'total_amount',
        'status',
        'posted_at',
    ];

    protected $casts = [
        'date' => 'date',
        'posted_at' => 'datetime',
        'total_amount' => 'float',
    ];

    protected $auditLogIgnore = ['updated_at', 'created_at'];

    protected $auditEntityType = 'purchase_payment';

    public function lines()
    {
        return $this->hasMany(PurchasePaymentLine::class, 'payment_id');
    }

    public function businessPartner()
    {
        return $this->belongsTo(\App\Models\BusinessPartner::class, 'business_partner_id');
    }

    public function companyEntity()
    {
        return $this->belongsTo(\App\Models\CompanyEntity::class, 'company_entity_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function allocations()
    {
        return $this->hasMany(PurchasePaymentAllocation::class, 'payment_id');
    }
}
