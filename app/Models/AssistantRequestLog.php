<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssistantRequestLog extends Model
{
    protected $fillable = [
        'user_id',
        'assistant_conversation_id',
        'status',
        'tools_invoked',
        'duration_ms',
        'error_summary',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'tools_invoked' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AssistantConversation::class, 'assistant_conversation_id');
    }
}
