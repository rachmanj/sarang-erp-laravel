<?php

namespace App\Http\Controllers;

use App\Exports\AuditLogExport;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\AuditLogExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class AuditLogController extends Controller
{
    protected $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    /**
     * Display recent audit logs.
     */
    public function index(Request $request)
    {
        // Get statistics
        $stats = [
            'total' => AuditLog::count(),
            'today' => AuditLog::whereDate('created_at', today())->count(),
            'by_action' => AuditLog::selectRaw('action, count(*) as count')
                ->groupBy('action')
                ->orderBy('count', 'desc')
                ->get(),
            'by_entity' => AuditLog::selectRaw('entity_type, count(*) as count')
                ->groupBy('entity_type')
                ->orderBy('count', 'desc')
                ->get(),
        ];

        // Get filter options
        $entityTypes = AuditLog::distinct()->pluck('entity_type')->sort()->values();
        $actions = AuditLog::distinct()->pluck('action')->sort()->values();
        $users = User::whereHas('auditLogs')->orderBy('name')->get(['id', 'name']);

        return view('audit-logs.index', compact('stats', 'entityTypes', 'actions', 'users'));
    }

    /**
     * Display audit trail for a specific entity.
     */
    public function show(Request $request, string $entityType, int $entityId)
    {
        $auditTrail = $this->auditLogService->getAuditTrail($entityType, $entityId);

        return view('audit-logs.show', compact('auditTrail', 'entityType', 'entityId'));
    }

    /**
     * Display audit logs by user.
     */
    public function byUser(Request $request, int $userId)
    {
        $user = User::findOrFail($userId);
        $days = $request->get('days', 30);
        $auditLogs = $this->auditLogService->getActivityByUser($userId, $days);

        // Get user statistics
        $stats = [
            'total' => AuditLog::where('user_id', $userId)->count(),
            'this_week' => AuditLog::where('user_id', $userId)
                ->where('created_at', '>=', now()->startOfWeek())
                ->count(),
            'most_common_action' => AuditLog::where('user_id', $userId)
                ->selectRaw('action, count(*) as count')
                ->groupBy('action')
                ->orderBy('count', 'desc')
                ->first(),
            'most_modified_entity' => AuditLog::where('user_id', $userId)
                ->selectRaw('entity_type, count(*) as count')
                ->groupBy('entity_type')
                ->orderBy('count', 'desc')
                ->first(),
        ];

        $firstActivity = AuditLog::where('user_id', $userId)->orderBy('created_at', 'asc')->first();
        $lastActivity = AuditLog::where('user_id', $userId)->orderBy('created_at', 'desc')->first();

        return view('audit-logs.by-user', compact('auditLogs', 'user', 'userId', 'days', 'stats', 'firstActivity', 'lastActivity'));
    }

    /**
     * Display audit logs by action.
     */
    public function byAction(Request $request, string $action)
    {
        $days = $request->get('days', 30);
        $auditLogs = $this->auditLogService->getActivityByAction($action, $days);

        // Get action statistics
        $stats = [
            'total' => AuditLog::where('action', $action)->count(),
            'this_week' => AuditLog::where('action', $action)
                ->where('created_at', '>=', now()->startOfWeek())
                ->count(),
            'most_affected_entities' => AuditLog::where('action', $action)
                ->selectRaw('entity_type, count(*) as count')
                ->groupBy('entity_type')
                ->orderBy('count', 'desc')
                ->limit(5)
                ->get(),
        ];

        return view('audit-logs.by-action', compact('auditLogs', 'action', 'days', 'stats'));
    }

    /**
     * Get audit logs for AJAX requests (DataTables).
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
        } elseif ($request->filled('entity_type')) {
            $query->where('entity_type', $request->entity_type);
        }

        // Action filter (multiple)
        if ($request->filled('actions')) {
            $actions = is_array($request->actions) 
                ? $request->actions 
                : explode(',', $request->actions);
            $query->whereIn('action', $actions);
        } elseif ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        // User filter (multiple)
        if ($request->filled('user_ids')) {
            $userIds = is_array($request->user_ids) 
                ? $request->user_ids 
                : explode(',', $request->user_ids);
            $query->whereIn('user_id', $userIds);
        } elseif ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
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
                return view('audit-logs.partials.action-badge', compact('log'))->render();
            })
            ->editColumn('entity_type', function($log) {
                return ucwords(str_replace('_', ' ', $log->entity_type));
            })
            ->editColumn('description', function($log) {
                return Str::limit($log->description ?? '', 100);
            })
            ->addColumn('actions', function($log) {
                return view('audit-logs.partials.action-buttons', compact('log'))->render();
            })
            ->rawColumns(['action', 'actions'])
            ->make(true);
    }

    /**
     * Get users for filter dropdown (AJAX).
     */
    public function getUsers(Request $request)
    {
        $search = $request->get('q');
        $users = User::whereHas('auditLogs')
            ->when($search, function($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->limit(50)
            ->get(['id', 'name', 'email']);

        return response()->json($users);
    }

    /**
     * Get entity types for filter dropdown (AJAX).
     */
    public function getEntityTypes(Request $request)
    {
        $entityTypes = AuditLog::distinct()
            ->pluck('entity_type')
            ->sort()
            ->map(function($type) {
                return [
                    'id' => $type,
                    'text' => ucwords(str_replace('_', ' ', $type))
                ];
            })
            ->values();

        return response()->json($entityTypes);
    }

    /**
     * Get change details for a specific audit log entry.
     */
    public function getChanges($id)
    {
        $log = AuditLog::with('user')->findOrFail($id);

        $changes = [];
        if ($log->old_values && $log->new_values) {
            foreach ($log->new_values as $key => $newValue) {
                $oldValue = $log->old_values[$key] ?? null;
                if ($oldValue !== $newValue) {
                    $changes[] = [
                        'field' => $key,
                        'old_value' => $oldValue,
                        'new_value' => $newValue,
                    ];
                }
            }
        } elseif ($log->action === 'created' && $log->new_values) {
            foreach ($log->new_values as $key => $newValue) {
                $changes[] = [
                    'field' => $key,
                    'old_value' => null,
                    'new_value' => $newValue,
                ];
            }
        } elseif ($log->action === 'deleted' && $log->old_values) {
            foreach ($log->old_values as $key => $oldValue) {
                $changes[] = [
                    'field' => $key,
                    'old_value' => $oldValue,
                    'new_value' => null,
                ];
            }
        }

        if (request()->wantsJson()) {
            return response()->json([
                'id' => $log->id,
                'action' => ucfirst($log->action),
                'created_at' => $log->created_at->format('Y-m-d H:i:s'),
                'user' => $log->user ? $log->user->name : 'System',
                'changes' => $changes,
                'old_values' => $log->old_values,
                'new_values' => $log->new_values,
                'ip_address' => $log->ip_address,
                'user_agent' => $log->user_agent,
            ]);
        }

        return view('audit-logs.partials.change-details', [
            'log' => $log,
            'changes' => $changes,
        ]);
    }

    /**
     * Get filter presets for current user.
     */
    public function getFilterPresets()
    {
        $presets = auth()->user()->audit_log_filter_presets ?? [];

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
            return ($preset['id'] ?? null) !== $presetId;
        });

        $user->update(['audit_log_filter_presets' => array_values($presets)]);

        return response()->json(['success' => true]);
    }

    /**
     * Get a specific filter preset.
     */
    public function getFilterPreset($presetId)
    {
        $user = auth()->user();
        $presets = $user->audit_log_filter_presets ?? [];
        
        $preset = collect($presets)->firstWhere('id', $presetId);

        if (!$preset) {
            return response()->json(['error' => 'Preset not found'], 404);
        }

        return response()->json(['preset' => $preset]);
    }

    /**
     * Export audit logs in various formats.
     */
    public function export(Request $request, $format)
    {
        $filters = $request->all();
        $exportService = app(AuditLogExportService::class);

        switch ($format) {
            case 'excel':
                return $exportService->exportToExcel($filters);
            case 'pdf':
                return $exportService->exportToPdf($filters);
            case 'csv':
                return $exportService->exportToCsv($filters);
            default:
                abort(404, 'Export format not supported');
        }
    }

    /**
     * Export compliance report.
     */
    public function exportCompliance(Request $request, $type)
    {
        $filters = $request->all();
        $exportService = app(AuditLogExportService::class);
        
        return $exportService->generateComplianceReport($type, $filters);
    }
}
