<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserRole extends Model
{
    protected $fillable = [
        'user_id',
        'role_name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function getUsersByRole(string $roleName): \Illuminate\Support\Collection
    {
        return static::where('role_name', $roleName)
            ->where('is_active', true)
            ->with('user')
            ->get()
            ->pluck('user');
    }
}
