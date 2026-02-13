<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesInvoice extends Model
{
    protected $table = 'sales_invoices';

    protected $fillable = [
        'invoice_no',
        'date',
        'due_date',
        'terms_days',
        'business_partner_id',
        'company_entity_id',
        'sales_order_id',
        'reference_no',
        'is_opening_balance',
        'description',
        'total_amount',
        'status',
        'posted_at',
    ];

    protected $casts = [
        'date' => 'date',
        'due_date' => 'date',
        'posted_at' => 'datetime',
        'total_amount' => 'float',
        'is_opening_balance' => 'boolean',
    ];

    protected $auditLogIgnore = ['updated_at', 'created_at'];
    protected $auditEntityType = 'sales_invoice';

    public function lines(): HasMany
    {
        return $this->hasMany(SalesInvoiceLine::class, 'invoice_id');
    }

    public function businessPartner(): BelongsTo
    {
        return $this->belongsTo(\App\Models\BusinessPartner::class, 'business_partner_id');
    }

    public function companyEntity(): BelongsTo
    {
        return $this->belongsTo(\App\Models\CompanyEntity::class, 'company_entity_id');
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(\App\Models\SalesOrder::class, 'sales_order_id');
    }

    public function deliveryOrders(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\DeliveryOrder::class, 'delivery_order_sales_invoice', 'sales_invoice_id', 'delivery_order_id')
            ->withTimestamps();
    }

    public function getDeliveryOrderIdAttribute(): ?int
    {
        return $this->deliveryOrders()->first()?->id;
    }

    public function customer(): BelongsTo
    {
        return $this->businessPartner();
    }
}
