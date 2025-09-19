<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessPartnerAddress extends Model
{
    protected $fillable = [
        'business_partner_id',
        'address_type',
        'address_line_1',
        'address_line_2',
        'city',
        'state_province',
        'postal_code',
        'country',
        'is_primary',
        'notes',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    // Relationships
    public function businessPartner(): BelongsTo
    {
        return $this->belongsTo(BusinessPartner::class);
    }

    // Scopes
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('address_type', $type);
    }

    // Accessors
    public function getFullAddressAttribute()
    {
        $parts = [];
        if ($this->address_line_1) $parts[] = $this->address_line_1;
        if ($this->address_line_2) $parts[] = $this->address_line_2;
        if ($this->city) $parts[] = $this->city;
        if ($this->state_province) $parts[] = $this->state_province;
        if ($this->postal_code) $parts[] = $this->postal_code;
        if ($this->country) $parts[] = $this->country;

        return implode(', ', $parts);
    }

    public function getShortAddressAttribute()
    {
        $parts = [];
        if ($this->address_line_1) $parts[] = $this->address_line_1;
        if ($this->city) $parts[] = $this->city;
        if ($this->country) $parts[] = $this->country;

        return implode(', ', $parts);
    }
}
