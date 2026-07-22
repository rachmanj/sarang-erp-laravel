<?php

namespace App\Models\Bank;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReconciliationMatchAudit extends Model
{
    public const ACTION_MATCH = 'match';

    public const ACTION_UNMATCH = 'unmatch';

    public const ACTION_AUTO_MATCH = 'auto_match';

    public const ACTION_OUTSTANDING = 'outstanding';

    public const ACTION_ADJUSTMENT = 'adjustment';

    protected $fillable = [
        'bank_reconciliation_id',
        'reconciliation_match_group_id',
        'action',
        'match_type',
        'bank_total',
        'book_total',
        'bank_line_ids',
        'book_line_ids',
        'performed_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'bank_total' => 'decimal:2',
            'book_total' => 'decimal:2',
            'bank_line_ids' => 'array',
            'book_line_ids' => 'array',
        ];
    }

    public function reconciliation(): BelongsTo
    {
        return $this->belongsTo(BankReconciliation::class, 'bank_reconciliation_id');
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
