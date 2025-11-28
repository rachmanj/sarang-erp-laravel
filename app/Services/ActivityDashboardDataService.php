<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ActivityDashboardDataService
{
    private const CACHE_KEY = 'activity:dashboard:data';
    private const CACHE_TTL = 60;

    public function getDashboardData(bool $refresh = false): array
    {
        if ($refresh) {
            Cache::forget(self::CACHE_KEY);
        }

        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return [
                'meta' => [
                    'generated_at' => now()->toIso8601String(),
                    'cache_ttl_seconds' => self::CACHE_TTL,
                ],
                'statistics' => $this->buildStatistics(),
                'recent_activity' => $this->getRecentActivity(),
                'activity_by_user' => $this->getActivityByUser(),
                'activity_by_module' => $this->getActivityByModule(),
                'activity_by_action' => $this->getActivityByAction(),
                'top_active_users' => $this->getTopActiveUsers(),
                'most_modified_entities' => $this->getMostModifiedEntities(),
                'activity_trends' => $this->getActivityTrends(),
                'hourly_activity' => $this->getHourlyActivity(),
            ];
        });
    }

    private function buildStatistics(): array
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();
        $thisWeek = Carbon::now()->startOfWeek();
        $lastWeek = Carbon::now()->subWeek()->startOfWeek();
        $thisMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();

        return [
            'total_logs' => AuditLog::count(),
            'today' => [
                'count' => AuditLog::whereDate('created_at', $today)->count(),
                'change' => $this->calculateChange(
                    AuditLog::whereDate('created_at', $yesterday)->count(),
                    AuditLog::whereDate('created_at', $today)->count()
                ),
            ],
            'this_week' => [
                'count' => AuditLog::whereBetween('created_at', [$thisWeek, now()])->count(),
                'change' => $this->calculateChange(
                    AuditLog::whereBetween('created_at', [$lastWeek, $lastWeek->copy()->endOfWeek()])->count(),
                    AuditLog::whereBetween('created_at', [$thisWeek, now()])->count()
                ),
            ],
            'this_month' => [
                'count' => AuditLog::whereBetween('created_at', [$thisMonth, now()])->count(),
                'change' => $this->calculateChange(
                    AuditLog::whereBetween('created_at', [$lastMonth, $lastMonth->copy()->endOfMonth()])->count(),
                    AuditLog::whereBetween('created_at', [$thisMonth, now()])->count()
                ),
            ],
            'unique_users' => AuditLog::distinct('user_id')->count('user_id'),
            'unique_entities' => AuditLog::distinct('entity_type')->count('entity_type'),
        ];
    }

    public function getRecentActivity(int $limit = 50): array
    {
        return AuditLog::with('user')
            ->where('created_at', '>=', Carbon::now()->subDay())
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'timestamp' => $log->created_at->toIso8601String(),
                    'user' => $log->user ? $log->user->name : 'System',
                    'action' => $log->action,
                    'entity_type' => $log->entity_type,
                    'entity_id' => $log->entity_id,
                    'description' => $log->description,
                    'action_color' => $log->action_color,
                ];
            })
            ->toArray();
    }

    private function getActivityByUser(int $limit = 10): array
    {
        return AuditLog::select('user_id', DB::raw('count(*) as count'))
            ->where('created_at', '>=', Carbon::now()->subWeek())
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->orderBy('count', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                $user = User::find($item->user_id);
                return [
                    'user_id' => $item->user_id,
                    'user_name' => $user ? $user->name : 'Unknown',
                    'count' => $item->count,
                ];
            })
            ->toArray();
    }

    private function getActivityByModule(int $limit = 10): array
    {
        return AuditLog::select('entity_type', DB::raw('count(*) as count'))
            ->where('created_at', '>=', Carbon::now()->subWeek())
            ->groupBy('entity_type')
            ->orderBy('count', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                return [
                    'entity_type' => $item->entity_type,
                    'entity_type_display' => ucwords(str_replace('_', ' ', $item->entity_type)),
                    'count' => $item->count,
                ];
            })
            ->toArray();
    }

    private function getActivityByAction(): array
    {
        return AuditLog::select('action', DB::raw('count(*) as count'))
            ->where('created_at', '>=', Carbon::now()->subWeek())
            ->groupBy('action')
            ->orderBy('count', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'action' => $item->action,
                    'action_display' => ucfirst($item->action),
                    'count' => $item->count,
                ];
            })
            ->toArray();
    }

    private function getTopActiveUsers(int $limit = 10): array
    {
        return AuditLog::select('user_id', DB::raw('count(*) as count'))
            ->where('created_at', '>=', Carbon::now()->subMonth())
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->orderBy('count', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                $user = User::find($item->user_id);
                return [
                    'user_id' => $item->user_id,
                    'user_name' => $user ? $user->name : 'Unknown',
                    'count' => $item->count,
                    'percentage' => 0,
                ];
            })
            ->toArray();
    }

    private function getMostModifiedEntities(int $limit = 10): array
    {
        return AuditLog::select('entity_type', 'entity_id', DB::raw('count(*) as count'))
            ->where('created_at', '>=', Carbon::now()->subMonth())
            ->groupBy('entity_type', 'entity_id')
            ->orderBy('count', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                return [
                    'entity_type' => $item->entity_type,
                    'entity_id' => $item->entity_id,
                    'count' => $item->count,
                    'entity_name' => $this->getEntityName($item->entity_type, $item->entity_id),
                ];
            })
            ->toArray();
    }

    private function getActivityTrends(): array
    {
        $startDate = Carbon::now()->subDays(30);
        $endDate = Carbon::now();

        $trends = [];
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $count = AuditLog::whereDate('created_at', $currentDate)->count();
            $trends[] = [
                'date' => $currentDate->format('Y-m-d'),
                'date_display' => $currentDate->format('M d'),
                'count' => $count,
            ];
            $currentDate->addDay();
        }

        return $trends;
    }

    private function getHourlyActivity(): array
    {
        $startDate = Carbon::now()->subDay();
        $endDate = Carbon::now();

        $hourly = [];
        $currentHour = $startDate->copy()->startOfHour();

        while ($currentHour <= $endDate) {
            $count = AuditLog::whereBetween('created_at', [
                $currentHour,
                $currentHour->copy()->addHour()
            ])->count();

            $hourly[] = [
                'hour' => $currentHour->format('H:00'),
                'count' => $count,
            ];

            $currentHour->addHour();
        }

        return $hourly;
    }

    private function calculateChange($old, $new): array
    {
        if ($old == 0) {
            return [
                'percentage' => $new > 0 ? 100 : 0,
                'direction' => $new > 0 ? 'up' : 'neutral',
            ];
        }

        $percentage = (($new - $old) / $old) * 100;

        return [
            'percentage' => round($percentage, 1),
            'direction' => $percentage > 0 ? 'up' : ($percentage < 0 ? 'down' : 'neutral'),
        ];
    }

    private function getEntityName(string $entityType, int $entityId): string
    {
        $modelClass = $this->getModelClass($entityType);
        
        if (!$modelClass || !class_exists($modelClass)) {
            return "#{$entityId}";
        }

        try {
            $model = $modelClass::find($entityId);
            if ($model) {
                if (isset($model->name)) return $model->name;
                if (isset($model->code)) return $model->code;
                if (isset($model->title)) return $model->title;
            }
        } catch (\Exception $e) {
        }

        return "#{$entityId}";
    }

    private function getModelClass(string $entityType): ?string
    {
        $className = str_replace('_', '', ucwords($entityType, '_'));
        $modelClass = "App\\Models\\{$className}";
        
        return class_exists($modelClass) ? $modelClass : null;
    }
}

