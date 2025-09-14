<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CostAllocationMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function costAllocations(): HasMany
    {
        return $this->hasMany(CostAllocation::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public static function getDefaultMethods()
    {
        return [
            [
                'name' => 'Quantity Based',
                'code' => 'QUANTITY',
                'description' => 'Allocate costs based on quantity',
            ],
            [
                'name' => 'Value Based',
                'code' => 'VALUE',
                'description' => 'Allocate costs based on value',
            ],
            [
                'name' => 'Weight Based',
                'code' => 'WEIGHT',
                'description' => 'Allocate costs based on weight',
            ],
            [
                'name' => 'Volume Based',
                'code' => 'VOLUME',
                'description' => 'Allocate costs based on volume',
            ],
            [
                'name' => 'Fixed Amount',
                'code' => 'FIXED',
                'description' => 'Allocate fixed amount',
            ],
        ];
    }
}
