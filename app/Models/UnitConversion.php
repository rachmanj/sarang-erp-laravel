<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnitConversion extends Model
{
    protected $table = 'unit_conversions';

    protected $fillable = [
        'from_unit_id',
        'to_unit_id',
        'conversion_factor',
        'is_active',
    ];

    protected $casts = [
        'conversion_factor' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function fromUnit(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'from_unit_id');
    }

    public function toUnit(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'to_unit_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFromUnit($query, $unitId)
    {
        return $query->where('from_unit_id', $unitId);
    }

    public function scopeToUnit($query, $unitId)
    {
        return $query->where('to_unit_id', $unitId);
    }

    // Helper methods
    public function convertQuantity($quantity): float
    {
        return $quantity * $this->conversion_factor;
    }

    public function reverseConvertQuantity($quantity): float
    {
        return $quantity / $this->conversion_factor;
    }

    // Validation
    public static function validateConversion($fromUnitId, $toUnitId): bool
    {
        $fromUnit = UnitOfMeasure::find($fromUnitId);
        $toUnit = UnitOfMeasure::find($toUnitId);

        if (!$fromUnit || !$toUnit) {
            return false;
        }

        // Check if units are of the same type
        if ($fromUnit->unit_type !== $toUnit->unit_type) {
            return false;
        }

        // Check if conversion exists
        return self::where('from_unit_id', $fromUnitId)
            ->where('to_unit_id', $toUnitId)
            ->where('is_active', true)
            ->exists();
    }

    // Accessors
    public function getDisplayTextAttribute(): string
    {
        return "1 {$this->fromUnit->name} = {$this->conversion_factor} {$this->toUnit->name}";
    }
}
