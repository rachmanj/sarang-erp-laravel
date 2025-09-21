<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GRGIPurpose extends Model
{
    protected $table = 'gr_gi_purposes';

    protected $fillable = [
        'code',
        'name',
        'description',
        'type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relationships
    public function headers(): HasMany
    {
        return $this->hasMany(GRGIHeader::class, 'purpose_id');
    }

    public function accountMappings(): HasMany
    {
        return $this->hasMany(GRGIAccountMapping::class, 'purpose_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeGoodsReceipt($query)
    {
        return $query->where('type', 'goods_receipt');
    }

    public function scopeGoodsIssue($query)
    {
        return $query->where('type', 'goods_issue');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }
}
