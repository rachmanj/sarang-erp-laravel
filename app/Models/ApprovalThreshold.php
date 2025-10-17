<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApprovalThreshold extends Model
{
    protected $fillable = [
        'document_type',
        'min_amount',
        'max_amount',
        'required_approvals',
    ];

    protected $casts = [
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'required_approvals' => 'array',
    ];

    public static function getRequiredApprovals(string $documentType, float $amount): array
    {
        $threshold = static::where('document_type', $documentType)
            ->where('min_amount', '<=', $amount)
            ->where('max_amount', '>=', $amount)
            ->orderBy('min_amount', 'desc')
            ->first();

        return $threshold ? $threshold->required_approvals : [];
    }
}
