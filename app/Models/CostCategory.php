<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CostCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function costAllocations(): HasMany
    {
        return $this->hasMany(CostAllocation::class);
    }

    public function costHistories(): HasMany
    {
        return $this->hasMany(CostHistory::class);
    }

    public function costAllocationRules(): HasMany
    {
        return $this->hasMany(CostAllocationRule::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public static function getDefaultCategories()
    {
        return [
            [
                'name' => 'Direct Material Cost',
                'code' => 'DMC',
                'description' => 'Direct material costs',
                'type' => 'direct',
            ],
            [
                'name' => 'Direct Labor Cost',
                'code' => 'DLC',
                'description' => 'Direct labor costs',
                'type' => 'direct',
            ],
            [
                'name' => 'Freight Cost',
                'code' => 'FRT',
                'description' => 'Freight and shipping costs',
                'type' => 'indirect',
            ],
            [
                'name' => 'Handling Cost',
                'code' => 'HDL',
                'description' => 'Handling and processing costs',
                'type' => 'indirect',
            ],
            [
                'name' => 'Storage Cost',
                'code' => 'STR',
                'description' => 'Storage and warehousing costs',
                'type' => 'overhead',
            ],
            [
                'name' => 'Administrative Overhead',
                'code' => 'ADM',
                'description' => 'Administrative overhead costs',
                'type' => 'overhead',
            ],
        ];
    }
}
