<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessPartnerContact extends Model
{
    protected $fillable = [
        'business_partner_id',
        'contact_type',
        'name',
        'position',
        'email',
        'phone',
        'mobile',
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
        return $query->where('contact_type', $type);
    }

    // Accessors
    public function getFullContactAttribute()
    {
        $parts = [];
        if ($this->name) $parts[] = $this->name;
        if ($this->position) $parts[] = "({$this->position})";
        if ($this->email) $parts[] = $this->email;
        if ($this->phone) $parts[] = $this->phone;

        return implode(' - ', $parts);
    }

    public function getDisplayPhoneAttribute()
    {
        if ($this->mobile) {
            return $this->mobile;
        }
        return $this->phone;
    }
}
