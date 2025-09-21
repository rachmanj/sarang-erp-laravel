<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class UnitOfMeasure extends Model
{
    protected $table = 'units_of_measure';

    protected $fillable = [
        'code',
        'name',
        'description',
        'unit_type',
        'is_base_unit',
        'is_active',
    ];

    protected $casts = [
        'is_base_unit' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function fromConversions(): HasMany
    {
        return $this->hasMany(UnitConversion::class, 'from_unit_id');
    }

    public function toConversions(): HasMany
    {
        return $this->hasMany(UnitConversion::class, 'to_unit_id');
    }

    public function inventoryItemUnits(): HasMany
    {
        return $this->hasMany(InventoryItemUnit::class, 'unit_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeBaseUnits($query)
    {
        return $query->where('is_base_unit', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('unit_type', $type);
    }

    // Helper methods
    public function canConvertTo($targetUnitId): bool
    {
        return $this->fromConversions()
            ->where('to_unit_id', $targetUnitId)
            ->where('is_active', true)
            ->exists();
    }

    public function getConversionFactorTo($targetUnitId): ?float
    {
        $conversion = $this->fromConversions()
            ->where('to_unit_id', $targetUnitId)
            ->where('is_active', true)
            ->first();

        return $conversion ? $conversion->conversion_factor : null;
    }

    public function getConvertedQuantity($quantity, $targetUnitId): ?float
    {
        $factor = $this->getConversionFactorTo($targetUnitId);
        return $factor ? $quantity * $factor : null;
    }

    public function isSameTypeAs($otherUnitId): bool
    {
        $otherUnit = self::find($otherUnitId);
        return $otherUnit && $this->unit_type === $otherUnit->unit_type;
    }

    // Accessors
    public function getDisplayNameAttribute(): string
    {
        return "{$this->name} ({$this->code})";
    }
}
