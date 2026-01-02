<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesQuotationApproval extends Model
{
    protected $fillable = [
        'sales_quotation_id',
        'user_id',
        'approval_level',
        'status',
        'comments',
        'approved_at'
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    // Relationships
    public function salesQuotation(): BelongsTo
    {
        return $this->belongsTo(SalesQuotation::class, 'sales_quotation_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Methods
    public function approve($comments = null)
    {
        $this->update([
            'status' => 'approved',
            'comments' => $comments,
            'approved_at' => now(),
        ]);
    }

    public function reject($comments = null)
    {
        $this->update([
            'status' => 'rejected',
            'comments' => $comments,
            'approved_at' => now(),
        ]);
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isApproved()
    {
        return $this->status === 'approved';
    }

    public function isRejected()
    {
        return $this->status === 'rejected';
    }
}
