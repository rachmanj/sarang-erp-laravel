<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'audit_log_filter_presets',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'audit_log_filter_presets' => 'array',
        ];
    }

    protected $auditLogIgnore = ['updated_at', 'created_at', 'email_verified_at'];
    protected $auditLogSensitive = ['password', 'remember_token'];
    protected $auditEntityType = 'user';

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'user_id');
    }

    public function legacyRoles(): HasMany
    {
        return $this->hasMany(UserRole::class);
    }

    public function hasLegacyRole(string $roleName): bool
    {
        return $this->legacyRoles()
            ->where('role_name', $roleName)
            ->where('is_active', true)
            ->exists();
    }

    public function getActiveLegacyRoles(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->legacyRoles()
            ->where('is_active', true)
            ->pluck('role_name');
    }
}
