<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Exports\AuditLogExport;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class AuditLogExportService
{
    public function exportToExcel($filters = [], $filename = null)
    {
        $logs = $this->getFilteredLogs($filters);

        $filename = $filename ?? 'audit-logs-' . now()->format('Y-m-d-His') . '.xlsx';

        return Excel::download(new AuditLogExport($logs), $filename);
    }

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

    private function generateSOXReport($filters)
    {
        $logs = $this->getFilteredLogs($filters);

        $soxLogs = $logs->filter(function($log) {
            $soxActions = ['created', 'updated', 'deleted', 'approved', 'posted'];
            $soxEntities = ['journal', 'account', 'purchase_invoice', 'sales_invoice'];
            
            return in_array($log->action, $soxActions) && 
                   in_array($log->entity_type, $soxEntities);
        });

        $summary = $this->getExportSummary($soxLogs);

        $pdf = Pdf::loadView('audit-logs.exports.compliance', [
            'logs' => $soxLogs,
            'summary' => $summary,
            'filters' => $filters,
            'report_type' => 'SOX Compliance',
            'generated_at' => now(),
        ]);

        return $pdf->download('sox-compliance-report-' . now()->format('Y-m-d') . '.pdf');
    }

    private function generateISOReport($filters)
    {
        $logs = $this->getFilteredLogs($filters);

        $summary = $this->getExportSummary($logs);

        $pdf = Pdf::loadView('audit-logs.exports.compliance', [
            'logs' => $logs,
            'summary' => $summary,
            'filters' => $filters,
            'report_type' => 'ISO Compliance',
            'generated_at' => now(),
        ]);

        return $pdf->download('iso-compliance-report-' . now()->format('Y-m-d') . '.pdf');
    }

    private function generateGDPRReport($filters)
    {
        $logs = $this->getFilteredLogs($filters);

        $gdprLogs = $logs->filter(function($log) {
            return $log->entity_type === 'user' || 
                   (isset($log->old_values['email']) || isset($log->new_values['email']));
        });

        $summary = $this->getExportSummary($gdprLogs);

        $pdf = Pdf::loadView('audit-logs.exports.compliance', [
            'logs' => $gdprLogs,
            'summary' => $summary,
            'filters' => $filters,
            'report_type' => 'GDPR Compliance',
            'generated_at' => now(),
        ]);

        return $pdf->download('gdpr-compliance-report-' . now()->format('Y-m-d') . '.pdf');
    }

    private function getFilteredLogs($filters): Collection
    {
        $query = AuditLog::with('user');

        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }
        if (isset($filters['entity_types'])) {
            $entityTypes = is_array($filters['entity_types']) 
                ? $filters['entity_types'] 
                : explode(',', $filters['entity_types']);
            $query->whereIn('entity_type', $entityTypes);
        } elseif (isset($filters['entity_type'])) {
            $query->where('entity_type', $filters['entity_type']);
        }
        if (isset($filters['actions'])) {
            $actions = is_array($filters['actions']) 
                ? $filters['actions'] 
                : explode(',', $filters['actions']);
            $query->whereIn('action', $actions);
        } elseif (isset($filters['action'])) {
            $query->where('action', $filters['action']);
        }
        if (isset($filters['user_ids'])) {
            $userIds = is_array($filters['user_ids']) 
                ? $filters['user_ids'] 
                : explode(',', $filters['user_ids']);
            $query->whereIn('user_id', $userIds);
        } elseif (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        if (isset($filters['ip_address'])) {
            $query->where('ip_address', 'like', "%{$filters['ip_address']}%");
        }
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('entity_type', 'like', "%{$search}%")
                  ->orWhere('action', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

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

