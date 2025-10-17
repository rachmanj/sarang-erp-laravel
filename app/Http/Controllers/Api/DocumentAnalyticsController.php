<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DocumentAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DocumentAnalyticsController extends Controller
{
    protected DocumentAnalyticsService $analyticsService;

    public function __construct(DocumentAnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Track document navigation usage.
     */
    public function trackNavigation(Request $request): JsonResponse
    {
        $request->validate([
            'document_type' => 'required|string',
            'document_id' => 'required|integer',
            'action' => 'required|string',
        ]);

        try {
            $this->analyticsService->trackNavigationUsage(
                $request->document_type,
                $request->document_id,
                $request->action,
                $request->user()
            );

            return response()->json([
                'success' => true,
                'message' => 'Navigation usage tracked successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to track navigation usage: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get analytics for a specific document.
     */
    public function getDocumentAnalytics(Request $request, string $documentType, int $documentId): JsonResponse
    {
        $request->validate([
            'days' => 'integer|min:1|max:365'
        ]);

        $days = $request->input('days', 30);

        try {
            $analytics = $this->analyticsService->getDocumentAnalytics($documentType, $documentId, $days);

            return response()->json([
                'success' => true,
                'data' => $analytics
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve document analytics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get system-wide analytics.
     */
    public function getSystemAnalytics(Request $request): JsonResponse
    {
        $request->validate([
            'days' => 'integer|min:1|max:365'
        ]);

        $days = $request->input('days', 30);

        try {
            $analytics = $this->analyticsService->getSystemAnalytics($days);

            return response()->json([
                'success' => true,
                'data' => $analytics
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve system analytics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get performance metrics.
     */
    public function getPerformanceMetrics(): JsonResponse
    {
        try {
            $metrics = $this->analyticsService->getPerformanceMetrics();

            return response()->json([
                'success' => true,
                'data' => $metrics
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve performance metrics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate analytics report.
     */
    public function generateReport(Request $request): JsonResponse
    {
        $request->validate([
            'days' => 'integer|min:1|max:365'
        ]);

        $days = $request->input('days', 30);

        try {
            $report = $this->analyticsService->generateAnalyticsReport($days);

            return response()->json([
                'success' => true,
                'data' => $report
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate analytics report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export analytics data.
     */
    public function exportData(Request $request): JsonResponse
    {
        $request->validate([
            'days' => 'integer|min:1|max:365',
            'format' => 'string|in:json,csv'
        ]);

        $days = $request->input('days', 30);
        $format = $request->input('format', 'json');

        try {
            $data = $this->analyticsService->exportAnalyticsData($days, $format);

            return response()->json([
                'success' => true,
                'data' => $data,
                'format' => $format,
                'download_url' => $this->generateDownloadUrl($data, $format)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export analytics data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate download URL for exported data.
     */
    protected function generateDownloadUrl(string $data, string $format): string
    {
        // In a real implementation, you'd save the file and return a download URL
        // For now, we'll return a placeholder
        return route('analytics.download', [
            'format' => $format,
            'token' => md5($data)
        ]);
    }
}
