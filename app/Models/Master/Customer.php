<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = ['code', 'name', 'email', 'phone'];

    public function pricingTiers(): HasMany
    {
        return $this->hasMany(\App\Models\CustomerPricingTier::class, 'customer_id');
    }
}
