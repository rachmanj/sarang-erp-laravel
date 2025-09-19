<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $fillable = [
        'entity_type',
        'entity_id',
        'action',
        'old_values',
        'new_values',
        'description',
        'user_id',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Scopes
    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }

    public function scopeByEntity($query, $entityType, $entityId = null)
    {
        $query = $query->where('entity_type', $entityType);

        if ($entityId) {
            $query = $query->where('entity_id', $entityId);
        }

        return $query;
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // Accessors
    public function getActionColorAttribute()
    {
        switch ($this->action) {
            case 'created':
                return 'success';
            case 'updated':
                return 'info';
            case 'deleted':
                return 'danger';
            case 'approved':
                return 'success';
            case 'rejected':
                return 'danger';
            case 'transferred':
                return 'warning';
            case 'adjusted':
                return 'primary';
            default:
                return 'secondary';
        }
    }

    public function getFormattedChangesAttribute()
    {
        if (!$this->old_values || !$this->new_values) {
            return [];
        }

        $changes = [];

        foreach ($this->new_values as $key => $newValue) {
            $oldValue = $this->old_values[$key] ?? null;

            if ($oldValue !== $newValue) {
                $changes[] = [
                    'field' => $key,
                    'old_value' => $oldValue,
                    'new_value' => $newValue,
                ];
            }
        }

        return $changes;
    }

    // Static methods
    public static function log($action, $entityType, $entityId, $oldValues = null, $newValues = null, $description = null, $userId = null)
    {
        return self::create([
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'description' => $description,
            'user_id' => $userId ?? auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public static function logInventoryItem($action, $itemId, $oldValues = null, $newValues = null, $description = null)
    {
        return self::log($action, 'inventory_item', $itemId, $oldValues, $newValues, $description);
    }

    public static function logInventoryTransaction($action, $transactionId, $oldValues = null, $newValues = null, $description = null)
    {
        return self::log($action, 'inventory_transaction', $transactionId, $oldValues, $newValues, $description);
    }

    public static function logWarehouse($action, $warehouseId, $oldValues = null, $newValues = null, $description = null)
    {
        return self::log($action, 'warehouse', $warehouseId, $oldValues, $newValues, $description);
    }

    public static function getAuditTrail($entityType, $entityId)
    {
        return self::byEntity($entityType, $entityId)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public static function getRecentActivity($days = 7, $limit = 50)
    {
        return self::recent($days)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public static function getActivityByUser($userId, $days = 30)
    {
        return self::byUser($userId)
            ->recent($days)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public static function getActivityByAction($action, $days = 30)
    {
        return self::byAction($action)
            ->recent($days)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
