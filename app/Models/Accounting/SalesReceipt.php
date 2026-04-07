<?php

namespace App\Models\Accounting;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesReceipt extends Model
{
    protected $table = 'sales_receipts';

    protected $fillable = [
        'receipt_no',
        'date',
        'business_partner_id',
        'company_entity_id',
        'created_by',
        'currency_id',
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

    protected $auditEntityType = 'sales_receipt';

    public function lines(): HasMany
    {
        return $this->hasMany(SalesReceiptLine::class, 'receipt_id');
    }

    public function businessPartner(): BelongsTo
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
}
