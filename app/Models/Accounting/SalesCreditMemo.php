<?php

namespace App\Models\Accounting;

use App\Models\BusinessPartner;
use App\Models\BusinessPartnerProject;
use App\Models\CompanyEntity;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesCreditMemo extends Model
{
    protected $table = 'sales_credit_memos';

    protected $fillable = [
        'memo_no',
        'date',
        'sales_invoice_id',
        'business_partner_id',
        'business_partner_project_id',
        'company_entity_id',
        'description',
        'total_amount',
        'status',
        'posted_at',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'posted_at' => 'datetime',
        'total_amount' => 'float',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(SalesCreditMemoLine::class, 'credit_memo_id');
    }

    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id');
    }

    public function businessPartner(): BelongsTo
    {
        return $this->belongsTo(BusinessPartner::class, 'business_partner_id');
    }

    public function businessPartnerProject(): BelongsTo
    {
        return $this->belongsTo(BusinessPartnerProject::class, 'business_partner_project_id');
    }

    public function companyEntity(): BelongsTo
    {
        return $this->belongsTo(CompanyEntity::class, 'company_entity_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
