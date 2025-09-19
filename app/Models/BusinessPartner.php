<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessPartner extends Model
{
    protected $fillable = [
        'code',
        'name',
        'partner_type',
        'status',
        'account_id',
        'registration_number',
        'tax_id',
        'website',
        'notes',
    ];

    protected $casts = [
        'partner_type' => 'string',
        'status' => 'string',
    ];

    // Relationships
    public function account(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Accounting\Account::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(BusinessPartnerContact::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(BusinessPartnerAddress::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(BusinessPartnerDetail::class);
    }

    public function primaryContact(): HasOne
    {
        return $this->hasOne(BusinessPartnerContact::class)->where('is_primary', true);
    }

    public function primaryAddress(): HasOne
    {
        return $this->hasOne(BusinessPartnerAddress::class)->where('is_primary', true);
    }

    // Business relationships
    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function salesOrders(): HasMany
    {
        return $this->hasMany(SalesOrder::class);
    }

    public function deliveryOrders(): HasMany
    {
        return $this->hasMany(DeliveryOrder::class);
    }

    public function purchaseInvoices(): HasMany
    {
        return $this->hasMany(\App\Models\Accounting\PurchaseInvoice::class);
    }

    public function salesInvoices(): HasMany
    {
        return $this->hasMany(\App\Models\Accounting\SalesInvoice::class);
    }

    public function purchasePayments(): HasMany
    {
        return $this->hasMany(\App\Models\Accounting\PurchasePayment::class);
    }

    public function salesReceipts(): HasMany
    {
        return $this->hasMany(\App\Models\Accounting\SalesReceipt::class);
    }

    public function goodsReceipts(): HasMany
    {
        return $this->hasMany(GoodsReceipt::class);
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    public function accountStatements(): HasMany
    {
        return $this->hasMany(\App\Models\Accounting\AccountStatement::class);
    }

    // Scopes
    public function scopeCustomers($query)
    {
        return $query->where('partner_type', 'customer');
    }

    public function scopeSuppliers($query)
    {
        return $query->where('partner_type', 'supplier');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('partner_type', $type);
    }

    // Accessors
    public function getIsCustomerAttribute()
    {
        return $this->partner_type === 'customer';
    }

    public function getIsSupplierAttribute()
    {
        return $this->partner_type === 'supplier';
    }

    public function getDisplayNameAttribute()
    {
        return "{$this->code} - {$this->name}";
    }

    // Helper methods
    public function getContactByType($type)
    {
        return $this->contacts()->where('contact_type', $type)->first();
    }

    public function getAddressByType($type)
    {
        return $this->addresses()->where('address_type', $type)->first();
    }

    public function getDetailBySection($section, $field = null)
    {
        $query = $this->details()->where('section_type', $section);

        if ($field) {
            return $query->where('field_name', $field)->first();
        }

        return $query->get();
    }

    public function setDetail($section, $field, $value, $type = 'text')
    {
        return $this->details()->updateOrCreate(
            [
                'business_partner_id' => $this->id,
                'section_type' => $section,
                'field_name' => $field,
            ],
            [
                'field_value' => $value,
                'field_type' => $type,
            ]
        );
    }

    // Account management methods
    public function getDefaultAccount()
    {
        if ($this->partner_type === 'customer') {
            // Find Accounts Receivable account
            return \App\Models\Accounting\Account::where('code', 'like', '1100%')
                ->where('name', 'like', '%receivable%')
                ->first();
        } elseif ($this->partner_type === 'supplier') {
            // Find Accounts Payable account
            return \App\Models\Accounting\Account::where('code', 'like', '2100%')
                ->where('name', 'like', '%payable%')
                ->first();
        }

        return null;
    }

    public function getAccountOrDefault()
    {
        return $this->account ?? $this->getDefaultAccount();
    }
}
