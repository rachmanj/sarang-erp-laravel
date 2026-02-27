<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessPartnerProject extends Model
{
    protected $fillable = [
        'business_partner_id',
        'code',
        'name',
        'description',
        'status',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    protected $auditLogIgnore = ['updated_at', 'created_at'];
    protected $auditEntityType = 'business_partner_project';

    public function businessPartner(): BelongsTo
    {
        return $this->belongsTo(BusinessPartner::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByPartner($query, $businessPartnerId)
    {
        return $query->where('business_partner_id', $businessPartnerId);
    }

    public function getDisplayNameAttribute(): string
    {
        return "{$this->code} - {$this->name}";
    }
}
