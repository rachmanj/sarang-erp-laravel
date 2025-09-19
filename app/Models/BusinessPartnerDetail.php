<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessPartnerDetail extends Model
{
    protected $fillable = [
        'business_partner_id',
        'section_type',
        'field_name',
        'field_value',
        'field_type',
        'is_required',
        'sort_order',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'sort_order' => 'integer',
    ];

    // Relationships
    public function businessPartner(): BelongsTo
    {
        return $this->belongsTo(BusinessPartner::class);
    }

    // Scopes
    public function scopeBySection($query, $section)
    {
        return $query->where('section_type', $section);
    }

    public function scopeByField($query, $field)
    {
        return $query->where('field_name', $field);
    }

    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('field_name');
    }

    // Accessors
    public function getTypedValueAttribute()
    {
        switch ($this->field_type) {
            case 'number':
                return (float) $this->field_value;
            case 'boolean':
                return (bool) $this->field_value;
            case 'date':
                return $this->field_value ? \Carbon\Carbon::parse($this->field_value) : null;
            case 'json':
                return json_decode($this->field_value, true);
            default:
                return $this->field_value;
        }
    }

    // Mutators
    public function setFieldValueAttribute($value)
    {
        if ($this->field_type === 'json' && is_array($value)) {
            $this->attributes['field_value'] = json_encode($value);
        } else {
            $this->attributes['field_value'] = $value;
        }
    }
}
