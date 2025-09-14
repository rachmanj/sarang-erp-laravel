<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxComplianceLog extends Model
{
    protected $fillable = [
        'action',
        'entity_type',
        'entity_id',
        'old_values',
        'new_values',
        'description',
        'user_id',
        'ip_address',
        'user_agent'
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
            case 'submitted':
                return 'primary';
            case 'approved':
                return 'success';
            case 'rejected':
                return 'danger';
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

    // Methods
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

    public static function logTaxTransaction($action, $transactionId, $oldValues = null, $newValues = null, $description = null)
    {
        return self::log($action, 'tax_transaction', $transactionId, $oldValues, $newValues, $description);
    }

    public static function logTaxReport($action, $reportId, $oldValues = null, $newValues = null, $description = null)
    {
        return self::log($action, 'tax_report', $reportId, $oldValues, $newValues, $description);
    }

    public static function logTaxPeriod($action, $periodId, $oldValues = null, $newValues = null, $description = null)
    {
        return self::log($action, 'tax_period', $periodId, $oldValues, $newValues, $description);
    }

    public static function logTaxSetting($action, $settingId, $oldValues = null, $newValues = null, $description = null)
    {
        return self::log($action, 'tax_setting', $settingId, $oldValues, $newValues, $description);
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

    public static function getComplianceSummary($days = 30)
    {
        $activities = self::recent($days)->get();

        return [
            'total_activities' => $activities->count(),
            'activities_by_action' => $activities->groupBy('action')->map->count(),
            'activities_by_entity' => $activities->groupBy('entity_type')->map->count(),
            'activities_by_user' => $activities->groupBy('user_id')->map->count(),
            'most_active_user' => $activities->groupBy('user_id')->sortDesc()->keys()->first(),
            'most_common_action' => $activities->groupBy('action')->sortDesc()->keys()->first(),
            'most_common_entity' => $activities->groupBy('entity_type')->sortDesc()->keys()->first(),
        ];
    }
}
