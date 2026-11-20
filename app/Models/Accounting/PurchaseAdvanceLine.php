<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseAdvanceLine extends Model
{
    protected $fillable = [
        'advance_id',
        'account_id',
        'description',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'float',
        ];
    }

    public function advance(): BelongsTo
    {
        return $this->belongsTo(PurchaseAdvance::class, 'advance_id');
    }
}
