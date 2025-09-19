<?php

namespace App\Http\Controllers;

use App\Services\AuditLogService;
use Illuminate\Http\Request;

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
        $days = $request->get('days', 7);
        $limit = $request->get('limit', 50);

        $auditLogs = $this->auditLogService->getRecentActivity($days, $limit);

        return view('audit-logs.index', compact('auditLogs', 'days', 'limit'));
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
        $days = $request->get('days', 30);
        $auditLogs = $this->auditLogService->getActivityByUser($userId, $days);

        return view('audit-logs.by-user', compact('auditLogs', 'userId', 'days'));
    }

    /**
     * Display audit logs by action.
     */
    public function byAction(Request $request, string $action)
    {
        $days = $request->get('days', 30);
        $auditLogs = $this->auditLogService->getActivityByAction($action, $days);

        return view('audit-logs.by-action', compact('auditLogs', 'action', 'days'));
    }

    /**
     * Get audit logs for AJAX requests.
     */
    public function data(Request $request)
    {
        $entityType = $request->get('entity_type');
        $entityId = $request->get('entity_id');
        $action = $request->get('action');
        $userId = $request->get('user_id');
        $days = $request->get('days', 30);

        $query = \App\Models\AuditLog::with('user');

        if ($entityType) {
            $query->where('entity_type', $entityType);
        }

        if ($entityId) {
            $query->where('entity_id', $entityId);
        }

        if ($action) {
            $query->where('action', $action);
        }

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $query->where('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at', 'desc');

        $auditLogs = $query->paginate(20);

        return response()->json([
            'data' => $auditLogs->items(),
            'pagination' => [
                'current_page' => $auditLogs->currentPage(),
                'last_page' => $auditLogs->lastPage(),
                'per_page' => $auditLogs->perPage(),
                'total' => $auditLogs->total(),
            ]
        ]);
    }
}
