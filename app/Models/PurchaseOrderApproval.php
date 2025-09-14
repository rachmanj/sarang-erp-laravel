<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderApproval extends Model
{
    protected $fillable = [
        'purchase_order_id',
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
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
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
