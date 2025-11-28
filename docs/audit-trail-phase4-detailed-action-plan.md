# Phase 4: Enhanced Features - Detailed Action Plan

**Priority**: MEDIUM  
**Estimated Effort**: 6-10 days  
**Dependencies**: Phase 1 (UI), Phase 3 (Module Integration)

---

## Overview

Phase 4 focuses on enhanced features that provide advanced functionality, better user experience, and compliance capabilities. This includes an activity dashboard, advanced filtering, export/reporting capabilities, and inline audit trail widgets.

---

## Detailed Task Breakdown

### Task 4.1: Activity Dashboard

**Objective**: Create a centralized activity monitoring dashboard with real-time feed, statistics, and visualizations.

#### 4.1.1 Create Activity Dashboard Data Service

**File**: `app/Services/ActivityDashboardDataService.php`

**Purpose**: Centralized service to aggregate activity data for the dashboard.

```php
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
    private const CACHE_TTL = 60; // 1 minute cache for real-time feel

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

    /**
     * Build overall statistics.
     */
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

    /**
     * Get recent activity (last 24 hours).
     */
    private function getRecentActivity(int $limit = 50): array
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

    /**
     * Get activity breakdown by user.
     */
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

    /**
     * Get activity breakdown by module (entity type).
     */
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

    /**
     * Get activity breakdown by action.
     */
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

    /**
     * Get top active users.
     */
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
                    'percentage' => 0, // Will be calculated in frontend
                ];
            })
            ->toArray();
    }

    /**
     * Get most modified entities.
     */
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

    /**
     * Get activity trends (last 30 days).
     */
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

    /**
     * Get hourly activity (last 24 hours).
     */
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

    /**
     * Calculate percentage change.
     */
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

    /**
     * Get entity name for display.
     */
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
            // Model not found or error
        }

        return "#{$entityId}";
    }

    /**
     * Get model class from entity type.
     */
    private function getModelClass(string $entityType): ?string
    {
        $className = str_replace('_', '', ucwords($entityType, '_'));
        $modelClass = "App\\Models\\{$className}";
        
        return class_exists($modelClass) ? $modelClass : null;
    }
}
```

#### 4.1.2 Create Activity Dashboard Controller

**File**: `app/Http/Controllers/ActivityDashboardController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Services\ActivityDashboardDataService;
use Illuminate\Http\Request;

class ActivityDashboardController extends Controller
{
    protected $dashboardDataService;

    public function __construct(ActivityDashboardDataService $dashboardDataService)
    {
        $this->dashboardDataService = $dashboardDataService;
    }

    /**
     * Display activity dashboard.
     */
    public function index(Request $request)
    {
        $dashboardData = $this->dashboardDataService->getDashboardData(
            $request->boolean('refresh')
        );

        return view('activity-dashboard.index', compact('dashboardData'));
    }

    /**
     * Get recent activity feed (AJAX).
     */
    public function recentActivity(Request $request)
    {
        $limit = $request->get('limit', 20);
        $since = $request->get('since');

        $dataService = app(ActivityDashboardDataService::class);
        $activity = $dataService->getRecentActivity($limit);

        return response()->json([
            'activity' => $activity,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
```

#### 4.1.3 Create Activity Dashboard View

**File**: `resources/views/activity-dashboard/index.blade.php`

**Layout Components**:
- AdminLTE layout integration
- Breadcrumb navigation
- Page header with refresh button
- Statistics cards (6 cards)
- Real-time activity feed
- Charts section (3 charts)
- Top active users table
- Most modified entities table

**Statistics Cards**:
1. **Total Logs** - All time count
2. **Today's Activity** - Count with % change from yesterday
3. **This Week** - Count with % change from last week
4. **This Month** - Count with % change from last month
5. **Active Users** - Unique users in last 7 days
6. **Entity Types** - Unique entity types tracked

**Charts**:
1. **Activity Trends** - Line chart (last 30 days)
2. **Activity by Module** - Pie/Donut chart
3. **Hourly Activity** - Bar chart (last 24 hours)

**Real-time Activity Feed**:
- Auto-refresh every 30 seconds
- Shows last 20 activities
- Color-coded by action type
- Click to view details

#### 4.1.4 Add Routes

**File**: `routes/web.php`

```php
// Activity Dashboard
Route::prefix('admin')->middleware(['permission:admin.view'])->group(function () {
    Route::get('/activity-dashboard', [ActivityDashboardController::class, 'index'])->name('activity-dashboard.index');
    Route::get('/activity-dashboard/recent-activity', [ActivityDashboardController::class, 'recentActivity'])->name('activity-dashboard.recent-activity');
});
```

#### 4.1.5 Add Sidebar Menu Item

**File**: `resources/views/layouts/partials/sidebar.blade.php`

```blade
@can('admin.view')
    <li class="nav-item">
        <a href="{{ route('activity-dashboard.index') }}" 
           class="nav-link {{ request()->routeIs('activity-dashboard.*') ? 'active' : '' }}">
            <i class="nav-icon fas fa-chart-line"></i>
            <p>Activity Dashboard</p>
        </a>
    </li>
@endcan
```

---

### Task 4.2: Advanced Filtering & Search

**Objective**: Implement powerful filtering and search capabilities with saved presets.

#### 4.2.1 Enhance AuditLogController with Advanced Filtering

**File**: `app/Http/Controllers/AuditLogController.php`

**New Methods**:

```php
/**
 * Get filter presets for current user.
 */
public function getFilterPresets()
{
    $presets = auth()->user()->auditLogFilterPresets ?? [];

    return response()->json([
        'presets' => $presets,
    ]);
}

/**
 * Save filter preset.
 */
public function saveFilterPreset(Request $request)
{
    $request->validate([
        'name' => 'required|string|max:255',
        'filters' => 'required|array',
    ]);

    $user = auth()->user();
    $presets = $user->audit_log_filter_presets ?? [];
    
    $presets[] = [
        'id' => uniqid(),
        'name' => $request->name,
        'filters' => $request->filters,
        'created_at' => now()->toIso8601String(),
    ];

    $user->update(['audit_log_filter_presets' => $presets]);

    return response()->json([
        'success' => true,
        'preset' => end($presets),
    ]);
}

/**
 * Delete filter preset.
 */
public function deleteFilterPreset($presetId)
{
    $user = auth()->user();
    $presets = $user->audit_log_filter_presets ?? [];
    
    $presets = array_filter($presets, function ($preset) use ($presetId) {
        return $preset['id'] !== $presetId;
    });

    $user->update(['audit_log_filter_presets' => array_values($presets)]);

    return response()->json(['success' => true]);
}

/**
 * Enhanced data endpoint with advanced filtering.
 */
public function data(Request $request)
{
    $query = AuditLog::with('user');

    // Date range filter
    if ($request->filled('date_from')) {
        $query->whereDate('created_at', '>=', $request->date_from);
    }
    if ($request->filled('date_to')) {
        $query->whereDate('created_at', '<=', $request->date_to);
    }

    // Entity type filter (multiple)
    if ($request->filled('entity_types')) {
        $entityTypes = is_array($request->entity_types) 
            ? $request->entity_types 
            : explode(',', $request->entity_types);
        $query->whereIn('entity_type', $entityTypes);
    }

    // Action filter (multiple)
    if ($request->filled('actions')) {
        $actions = is_array($request->actions) 
            ? $request->actions 
            : explode(',', $request->actions);
        $query->whereIn('action', $actions);
    }

    // User filter (multiple)
    if ($request->filled('user_ids')) {
        $userIds = is_array($request->user_ids) 
            ? $request->user_ids 
            : explode(',', $request->user_ids);
        $query->whereIn('user_id', $userIds);
    }

    // IP address filter
    if ($request->filled('ip_address')) {
        $query->where('ip_address', 'like', "%{$request->ip_address}%");
    }

    // Full-text search in description
    if ($request->filled('search')) {
        $search = $request->search;
        $query->where(function($q) use ($search) {
            $q->where('description', 'like', "%{$search}%")
              ->orWhere('entity_type', 'like', "%{$search}%")
              ->orWhere('action', 'like', "%{$search}%");
        });
    }

    // Amount range filter (for entities with amounts)
    if ($request->filled('amount_min') || $request->filled('amount_max')) {
        $query->whereHas('entity', function($q) use ($request) {
            if ($request->filled('amount_min')) {
                $q->where('total_amount', '>=', $request->amount_min);
            }
            if ($request->filled('amount_max')) {
                $q->where('total_amount', '<=', $request->amount_max);
            }
        });
    }

    // Sort
    $sortColumn = $request->get('sort_column', 'created_at');
    $sortDirection = $request->get('sort_direction', 'desc');
    $query->orderBy($sortColumn, $sortDirection);

    return DataTables::of($query)
        ->editColumn('created_at', function($log) {
            return $log->created_at->format('Y-m-d H:i:s');
        })
        ->editColumn('user', function($log) {
            return $log->user ? $log->user->name : 'System';
        })
        ->editColumn('action', function($log) {
            return view('audit-logs.partials.action-badge', compact('log'));
        })
        ->editColumn('entity_type', function($log) {
            return ucwords(str_replace('_', ' ', $log->entity_type));
        })
        ->editColumn('description', function($log) {
            return Str::limit($log->description, 100);
        })
        ->addColumn('actions', function($log) {
            return view('audit-logs.partials.action-buttons', compact('log'));
        })
        ->rawColumns(['action', 'actions'])
        ->make(true);
}
```

#### 4.2.2 Add Filter Presets Migration

**File**: `database/migrations/YYYY_MM_DD_HHMMSS_add_audit_log_filter_presets_to_users_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('audit_log_filter_presets')->nullable()->after('remember_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('audit_log_filter_presets');
        });
    }
};
```

#### 4.2.3 Enhance Index View with Advanced Filters

**File**: `resources/views/audit-logs/index.blade.php`

**Enhanced Filter Section**:
- Multi-select for entity types
- Multi-select for actions
- Multi-select for users
- IP address filter
- Amount range filters (min/max)
- Full-text search with highlighting
- Saved presets dropdown
- Save current filters as preset button
- Quick filter buttons (Today, This Week, This Month, Last 30 Days)

**JavaScript Enhancements**:

```javascript
// Filter preset management
function loadFilterPreset(presetId) {
    $.ajax({
        url: '/audit-logs/filter-presets/' + presetId,
        method: 'GET',
        success: function(preset) {
            // Apply filters from preset
            applyFilters(preset.filters);
        }
    });
}

function saveCurrentFilters() {
    const filters = {
        date_from: $('#filter_date_from').val(),
        date_to: $('#filter_date_to').val(),
        entity_types: $('#filter_entity_types').val(),
        actions: $('#filter_actions').val(),
        user_ids: $('#filter_user_ids').val(),
        ip_address: $('#filter_ip_address').val(),
        search: $('#filter_search').val(),
    };

    const presetName = prompt('Enter preset name:');
    if (presetName) {
        $.ajax({
            url: '/audit-logs/filter-presets',
            method: 'POST',
            data: {
                name: presetName,
                filters: filters,
            },
            success: function(response) {
                // Reload presets dropdown
                loadFilterPresets();
                toastr.success('Filter preset saved');
            }
        });
    }
}
```

---

### Task 4.3: Export & Reporting

**Objective**: Implement comprehensive export and reporting capabilities.

#### 4.3.1 Create Export Service

**File**: `app/Services/AuditLogExportService.php`

```php
<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class AuditLogExportService
{
    /**
     * Export audit logs to Excel.
     */
    public function exportToExcel($filters = [], $filename = null)
    {
        $logs = $this->getFilteredLogs($filters);

        $filename = $filename ?? 'audit-logs-' . now()->format('Y-m-d-His') . '.xlsx';

        return Excel::download(new AuditLogExport($logs), $filename);
    }

    /**
     * Export audit logs to PDF.
     */
    public function exportToPdf($filters = [], $filename = null)
    {
        $logs = $this->getFilteredLogs($filters);
        $summary = $this->getExportSummary($logs);

        $filename = $filename ?? 'audit-logs-' . now()->format('Y-m-d-His') . '.pdf';

        $pdf = Pdf::loadView('audit-logs.exports.pdf', [
            'logs' => $logs,
            'summary' => $summary,
            'filters' => $filters,
            'generated_at' => now(),
        ]);

        return $pdf->download($filename);
    }

    /**
     * Export audit logs to CSV.
     */
    public function exportToCsv($filters = [], $filename = null)
    {
        $logs = $this->getFilteredLogs($filters);

        $filename = $filename ?? 'audit-logs-' . now()->format('Y-m-d-His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($logs) {
            $file = fopen('php://output', 'w');
            
            // Header row
            fputcsv($file, [
                'Timestamp',
                'User',
                'Action',
                'Entity Type',
                'Entity ID',
                'Description',
                'IP Address',
                'User Agent',
            ]);

            // Data rows
            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->created_at->format('Y-m-d H:i:s'),
                    $log->user ? $log->user->name : 'System',
                    $log->action,
                    $log->entity_type,
                    $log->entity_id,
                    $log->description,
                    $log->ip_address,
                    $log->user_agent,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Generate compliance report.
     */
    public function generateComplianceReport($reportType, $filters = [])
    {
        switch ($reportType) {
            case 'sox':
                return $this->generateSOXReport($filters);
            case 'iso':
                return $this->generateISOReport($filters);
            case 'gdpr':
                return $this->generateGDPRReport($filters);
            default:
                throw new \Exception("Unknown report type: {$reportType}");
        }
    }

    /**
     * Generate SOX compliance report.
     */
    private function generateSOXReport($filters)
    {
        $logs = $this->getFilteredLogs($filters);

        // Filter for SOX-relevant activities
        $soxLogs = $logs->filter(function($log) {
            $soxActions = ['created', 'updated', 'deleted', 'approved', 'posted'];
            $soxEntities = ['journal', 'account', 'purchase_invoice', 'sales_invoice'];
            
            return in_array($log->action, $soxActions) && 
                   in_array($log->entity_type, $soxEntities);
        });

        return $this->exportToPdf([
            'logs' => $soxLogs,
            'report_type' => 'SOX Compliance',
            'filters' => $filters,
        ], 'sox-compliance-report-' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Generate ISO compliance report.
     */
    private function generateISOReport($filters)
    {
        // Similar to SOX but with ISO-specific criteria
    }

    /**
     * Generate GDPR compliance report.
     */
    private function generateGDPRReport($filters)
    {
        // Focus on user data access and modifications
    }

    /**
     * Get filtered logs.
     */
    private function getFilteredLogs($filters): Collection
    {
        $query = AuditLog::with('user');

        // Apply filters (same as controller)
        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }
        // ... other filters

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get export summary.
     */
    private function getExportSummary(Collection $logs): array
    {
        return [
            'total_records' => $logs->count(),
            'date_range' => [
                'from' => $logs->min('created_at'),
                'to' => $logs->max('created_at'),
            ],
            'by_action' => $logs->groupBy('action')->map->count(),
            'by_entity' => $logs->groupBy('entity_type')->map->count(),
            'unique_users' => $logs->pluck('user_id')->unique()->count(),
        ];
    }
}
```

#### 4.3.2 Create Excel Export Class

**File**: `app/Exports/AuditLogExport.php`

```php
<?php

namespace App\Exports;

use App\Models\AuditLog;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class AuditLogExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    protected $logs;

    public function __construct($logs)
    {
        $this->logs = $logs;
    }

    public function collection()
    {
        return $this->logs;
    }

    public function headings(): array
    {
        return [
            'Timestamp',
            'User',
            'Action',
            'Entity Type',
            'Entity ID',
            'Description',
            'IP Address',
            'User Agent',
            'Old Values',
            'New Values',
        ];
    }

    public function map($log): array
    {
        return [
            $log->created_at->format('Y-m-d H:i:s'),
            $log->user ? $log->user->name : 'System',
            ucfirst($log->action),
            ucwords(str_replace('_', ' ', $log->entity_type)),
            $log->entity_id,
            $log->description,
            $log->ip_address,
            $log->user_agent,
            $log->old_values ? json_encode($log->old_values) : '',
            $log->new_values ? json_encode($log->new_values) : '',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4'],
                ],
                'font' => ['color' => ['rgb' => 'FFFFFF']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20, // Timestamp
            'B' => 20, // User
            'C' => 15, // Action
            'D' => 20, // Entity Type
            'E' => 15, // Entity ID
            'F' => 50, // Description
            'G' => 18, // IP Address
            'H' => 50, // User Agent
            'I' => 30, // Old Values
            'J' => 30, // New Values
        ];
    }
}
```

#### 4.3.3 Create PDF Export View

**File**: `resources/views/audit-logs/exports/pdf.blade.php`

```blade
<!DOCTYPE html>
<html>
<head>
    <title>Audit Log Report</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 10px; }
        .header { text-align: center; margin-bottom: 20px; }
        .company-name { font-size: 18px; font-weight: bold; }
        .report-title { font-size: 14px; margin-top: 10px; }
        .summary { margin-bottom: 20px; }
        .summary table { width: 100%; border-collapse: collapse; }
        .summary td { padding: 5px; border: 1px solid #ddd; }
        .summary td:first-child { font-weight: bold; width: 30%; }
        .table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .table th, .table td { border: 1px solid #ddd; padding: 5px; text-align: left; }
        .table th { background-color: #4472C4; color: white; }
        .footer { margin-top: 20px; text-align: center; font-size: 8px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ config('app.name') }}</div>
        <div class="report-title">Audit Log Report</div>
        <div>Generated: {{ $generated_at->format('Y-m-d H:i:s') }}</div>
    </div>

    <div class="summary">
        <table>
            <tr>
                <td>Total Records</td>
                <td>{{ $summary['total_records'] }}</td>
            </tr>
            <tr>
                <td>Date Range</td>
                <td>{{ $summary['date_range']['from']->format('Y-m-d') }} to {{ $summary['date_range']['to']->format('Y-m-d') }}</td>
            </tr>
            <tr>
                <td>Unique Users</td>
                <td>{{ $summary['unique_users'] }}</td>
            </tr>
        </table>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Timestamp</th>
                <th>User</th>
                <th>Action</th>
                <th>Entity Type</th>
                <th>Entity ID</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            @foreach($logs as $log)
            <tr>
                <td>{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                <td>{{ $log->user ? $log->user->name : 'System' }}</td>
                <td>{{ ucfirst($log->action) }}</td>
                <td>{{ ucwords(str_replace('_', ' ', $log->entity_type)) }}</td>
                <td>{{ $log->entity_id }}</td>
                <td>{{ Str::limit($log->description, 50) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        This report was generated automatically by {{ config('app.name') }} Audit Log System.
    </div>
</body>
</html>
```

#### 4.3.4 Add Export Routes

**File**: `routes/web.php`

```php
Route::prefix('audit-logs')->middleware(['permission:admin.view'])->group(function () {
    // ... existing routes ...
    
    Route::get('/export/excel', [AuditLogController::class, 'exportExcel'])->name('audit-logs.export.excel');
    Route::get('/export/pdf', [AuditLogController::class, 'exportPdf'])->name('audit-logs.export.pdf');
    Route::get('/export/csv', [AuditLogController::class, 'exportCsv'])->name('audit-logs.export.csv');
    Route::get('/export/compliance/{type}', [AuditLogController::class, 'exportCompliance'])->name('audit-logs.export.compliance');
});
```

#### 4.3.5 Add Export Methods to Controller

**File**: `app/Http/Controllers/AuditLogController.php`

```php
use App\Services\AuditLogExportService;

public function exportExcel(Request $request)
{
    $filters = $request->all();
    $exportService = app(AuditLogExportService::class);
    
    return $exportService->exportToExcel($filters);
}

public function exportPdf(Request $request)
{
    $filters = $request->all();
    $exportService = app(AuditLogExportService::class);
    
    return $exportService->exportToPdf($filters);
}

public function exportCsv(Request $request)
{
    $filters = $request->all();
    $exportService = app(AuditLogExportService::class);
    
    return $exportService->exportToCsv($filters);
}

public function exportCompliance(Request $request, $type)
{
    $filters = $request->all();
    $exportService = app(AuditLogExportService::class);
    
    return $exportService->generateComplianceReport($type, $filters);
}
```

---

### Task 4.4: Audit Trail Widgets

**Objective**: Create inline audit trail widgets for entity show pages.

#### 4.4.1 Create Audit Trail Widget Component

**File**: `resources/views/components/audit-trail-widget.blade.php`

```blade
@props(['entityType', 'entityId', 'limit' => 10, 'collapsible' => true])

@php
    $auditTrail = app(\App\Services\AuditLogService::class)
        ->getAuditTrail($entityType, $entityId)
        ->take($limit);
@endphp

<div class="card card-outline card-info">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-history mr-2"></i>
            Audit Trail
            <span class="badge badge-info ml-2">{{ $auditTrail->count() }}</span>
        </h3>
        @if($collapsible)
            <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                    <i class="fas fa-minus"></i>
                </button>
            </div>
        @endif
    </div>
    <div class="card-body p-0">
        @if($auditTrail->count() > 0)
            <div class="timeline">
                @foreach($auditTrail as $log)
                    <div class="time-label">
                        <span class="bg-{{ $log->action_color }}">
                            {{ $log->created_at->format('M d, Y') }}
                        </span>
                    </div>
                    <div>
                        <i class="fas fa-{{ $this->getActionIcon($log->action) }} bg-{{ $log->action_color }}"></i>
                        <div class="timeline-item">
                            <span class="time">
                                <i class="fas fa-clock"></i> {{ $log->created_at->format('H:i') }}
                            </span>
                            <h3 class="timeline-header">
                                <strong>{{ $log->user ? $log->user->name : 'System' }}</strong>
                                <span class="badge badge-{{ $log->action_color }} ml-2">
                                    {{ ucfirst($log->action) }}
                                </span>
                            </h3>
                            <div class="timeline-body">
                                {{ $log->description }}
                            </div>
                            @if($log->old_values || $log->new_values)
                                <div class="timeline-footer">
                                    <button class="btn btn-sm btn-primary view-changes-btn" 
                                            data-log-id="{{ $log->id }}">
                                        <i class="fas fa-exchange-alt"></i> View Changes
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
            @if($auditTrail->count() >= $limit)
                <div class="card-footer">
                    <a href="{{ route('audit-logs.show', [$entityType, $entityId]) }}" 
                       class="btn btn-sm btn-info">
                        <i class="fas fa-eye"></i> View Full Audit Trail
                    </a>
                </div>
            @endif
        @else
            <div class="p-3 text-center text-muted">
                <i class="fas fa-info-circle"></i> No audit trail available
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        $('.view-changes-btn').on('click', function() {
            var logId = $(this).data('log-id');
            // Load and show change comparison modal
            loadChangeModal(logId);
        });
    });
</script>
@endpush
```

#### 4.4.2 Create Recent Changes Widget

**File**: `resources/views/components/recent-changes-widget.blade.php`

```blade
@props(['entityType', 'entityId', 'limit' => 5])

@php
    $recentChanges = app(\App\Services\AuditLogService::class)
        ->getAuditTrail($entityType, $entityId)
        ->where('action', 'updated')
        ->take($limit);
@endphp

<div class="card card-outline card-warning">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-edit mr-2"></i>
            Recent Changes
        </h3>
    </div>
    <div class="card-body p-0">
        @if($recentChanges->count() > 0)
            <ul class="list-group list-group-flush">
                @foreach($recentChanges as $log)
                    <li class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>{{ $log->user ? $log->user->name : 'System' }}</strong>
                                <small class="text-muted ml-2">
                                    {{ $log->created_at->diffForHumans() }}
                                </small>
                                <div class="mt-1">
                                    <small>{{ Str::limit($log->description, 60) }}</small>
                                </div>
                            </div>
                            <span class="badge badge-info">{{ ucfirst($log->action) }}</span>
                        </div>
                    </li>
                @endforeach
            </ul>
        @else
            <div class="p-3 text-center text-muted">
                <i class="fas fa-info-circle"></i> No recent changes
            </div>
        @endif
    </div>
</div>
```

#### 4.4.3 Integrate Widgets into Entity Show Pages

**Example**: `resources/views/inventory/show.blade.php`

```blade
<!-- Add after main content -->
<div class="row">
    <div class="col-12">
        <x-audit-trail-widget 
            entity-type="inventory_item" 
            :entity-id="$item->id" 
            :limit="10" 
            :collapsible="true" />
    </div>
</div>

<!-- Or add recent changes widget -->
<div class="row">
    <div class="col-md-6">
        <x-recent-changes-widget 
            entity-type="inventory_item" 
            :entity-id="$item->id" 
            :limit="5" />
    </div>
</div>
```

#### 4.4.4 Create Change Comparison Modal Component

**File**: `resources/views/components/change-comparison-modal.blade.php`

```blade
<div class="modal fade" id="change-comparison-modal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">
                    <i class="fas fa-exchange-alt"></i> Change Comparison
                </h4>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="change-comparison-content">
                <div class="text-center">
                    <i class="fas fa-spinner fa-spin"></i> Loading...
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function loadChangeModal(logId) {
        $.ajax({
            url: '/audit-logs/' + logId + '/changes',
            method: 'GET',
            success: function(data) {
                $('#change-comparison-content').html(data.html);
                $('#change-comparison-modal').modal('show');
            },
            error: function() {
                toastr.error('Failed to load change details');
            }
        });
    }
</script>
@endpush
```

---

## Implementation Checklist

### Day 1-2: Activity Dashboard
- [ ] Create `ActivityDashboardDataService`
- [ ] Create `ActivityDashboardController`
- [ ] Create activity dashboard view
- [ ] Add routes
- [ ] Add sidebar menu item
- [ ] Implement real-time activity feed
- [ ] Add charts (trends, by module, hourly)
- [ ] Test dashboard functionality

### Day 3-4: Advanced Filtering
- [ ] Enhance `AuditLogController` with advanced filtering
- [ ] Create filter presets migration
- [ ] Add filter preset methods to controller
- [ ] Enhance index view with advanced filters
- [ ] Implement saved presets functionality
- [ ] Add quick filter buttons
- [ ] Test filtering functionality

### Day 5-7: Export & Reporting
- [ ] Create `AuditLogExportService`
- [ ] Create `AuditLogExport` Excel class
- [ ] Create PDF export view
- [ ] Add export methods to controller
- [ ] Add export routes
- [ ] Implement compliance reports (SOX, ISO, GDPR)
- [ ] Test all export formats
- [ ] Test compliance reports

### Day 8-10: Audit Trail Widgets
- [ ] Create audit trail widget component
- [ ] Create recent changes widget component
- [ ] Create change comparison modal component
- [ ] Integrate widgets into entity show pages
- [ ] Add JavaScript for modal functionality
- [ ] Test widget functionality
- [ ] Documentation

---

## Testing Checklist

### Activity Dashboard Testing
- [ ] Dashboard loads with statistics
- [ ] Real-time activity feed updates
- [ ] Charts render correctly
- [ ] Statistics calculations are accurate
- [ ] Top active users display correctly
- [ ] Most modified entities display correctly
- [ ] Activity trends chart shows data
- [ ] Hourly activity chart shows data
- [ ] Refresh button works
- [ ] Auto-refresh works (if implemented)

### Advanced Filtering Testing
- [ ] Multi-select filters work
- [ ] IP address filter works
- [ ] Amount range filters work
- [ ] Full-text search works
- [ ] Filter presets save correctly
- [ ] Filter presets load correctly
- [ ] Filter presets delete correctly
- [ ] Quick filter buttons work
- [ ] Combined filters work correctly

### Export & Reporting Testing
- [ ] Excel export works
- [ ] PDF export works
- [ ] CSV export works
- [ ] Exports include filtered data
- [ ] Excel formatting is correct
- [ ] PDF layout is correct
- [ ] SOX compliance report generates
- [ ] ISO compliance report generates
- [ ] GDPR compliance report generates
- [ ] Large dataset exports work

### Widget Testing
- [ ] Audit trail widget displays on entity pages
- [ ] Recent changes widget displays correctly
- [ ] Widgets are collapsible
- [ ] Change comparison modal opens
- [ ] Change comparison modal displays data correctly
- [ ] Widgets handle empty audit trails
- [ ] Widgets link to full audit trail page

---

## Success Criteria

Phase 4 is considered complete when:

1. ✅ Activity dashboard is fully functional
2. ✅ Advanced filtering works with all filter types
3. ✅ Filter presets save and load correctly
4. ✅ Excel export works with proper formatting
5. ✅ PDF export works with proper layout
6. ✅ CSV export works correctly
7. ✅ Compliance reports generate correctly
8. ✅ Audit trail widgets display on entity pages
9. ✅ Recent changes widget works
10. ✅ Change comparison modal works
11. ✅ All tests pass
12. ✅ Documentation is complete
13. ✅ No performance degradation
14. ✅ No critical bugs or errors

---

## Next Steps After Phase 4

Once Phase 4 is complete, proceed to:
- **Phase 5**: Performance optimization and archiving (log retention, database partitioning, caching)

---

**Document Version**: 1.0  
**Last Updated**: 2025-01-20  
**Estimated Completion**: 6-10 days

