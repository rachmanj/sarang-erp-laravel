<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class CustomerApiKey extends Model
{
    protected $fillable = [
        'business_partner_id',
        'name',
        'token',
        'last_used_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function businessPartner(): BelongsTo
    {
        return $this->belongsTo(BusinessPartner::class, 'business_partner_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at instanceof Carbon && $this->expires_at->isPast();
    }

    /**
     * @return array{plain_text_token: string, api_key: self}
     */
    public static function createForPartner(BusinessPartner $partner, string $name, ?Carbon $expiresAt = null): array
    {
        $plain = Str::random(48);

        $model = static::query()->create([
            'business_partner_id' => $partner->id,
            'name' => $name,
            'token' => hash('sha256', $plain),
            'expires_at' => $expiresAt,
        ]);

        return [
            'plain_text_token' => $plain,
            'api_key' => $model,
        ];
    }
}
