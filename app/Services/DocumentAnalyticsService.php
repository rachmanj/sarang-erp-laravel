<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class DocumentAnalyticsService
{
    protected const ANALYTICS_CACHE_TTL = 3600; // 1 hour

    /**
     * Track document navigation usage.
     */
    public function trackNavigationUsage(string $documentType, int $documentId, string $action, $user = null): void
    {
        $data = [
            'document_type' => $documentType,
            'document_id' => $documentId,
            'action' => $action,
            'user_id' => $user ? $user->id : null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now(),
        ];

        // Store in database
        DB::table('document_analytics')->insert($data);

        // Update real-time cache
        $this->updateRealTimeCache($documentType, $documentId, $action);
    }

    /**
     * Get navigation analytics for a document.
     */
    public function getDocumentAnalytics(string $documentType, int $documentId, int $days = 30): array
    {
        $cacheKey = "analytics_{$documentType}_{$documentId}_{$days}";

        return Cache::remember($cacheKey, self::ANALYTICS_CACHE_TTL, function () use ($documentType, $documentId, $days) {
            $startDate = now()->subDays($days);

            $analytics = DB::table('document_analytics')
                ->where('document_type', $documentType)
                ->where('document_id', $documentId)
                ->where('timestamp', '>=', $startDate)
                ->selectRaw('
                    action,
                    COUNT(*) as count,
                    COUNT(DISTINCT user_id) as unique_users,
                    COUNT(DISTINCT ip_address) as unique_ips,
                    DATE(timestamp) as date
                ')
                ->groupBy('action', 'date')
                ->orderBy('date', 'desc')
                ->get();

            return [
                'document_type' => $documentType,
                'document_id' => $documentId,
                'period_days' => $days,
                'total_views' => $analytics->sum('count'),
                'unique_users' => $analytics->max('unique_users'),
                'unique_ips' => $analytics->max('unique_ips'),
                'daily_breakdown' => $analytics->groupBy('date'),
                'action_breakdown' => $analytics->groupBy('action'),
            ];
        });
    }

    /**
     * Get system-wide analytics.
     */
    public function getSystemAnalytics(int $days = 30): array
    {
        $cacheKey = "system_analytics_{$days}";

        return Cache::remember($cacheKey, self::ANALYTICS_CACHE_TTL, function () use ($days) {
            $startDate = now()->subDays($days);

            $analytics = DB::table('document_analytics')
                ->where('timestamp', '>=', $startDate)
                ->selectRaw('
                    document_type,
                    action,
                    COUNT(*) as count,
                    COUNT(DISTINCT user_id) as unique_users,
                    COUNT(DISTINCT document_id) as unique_documents
                ')
                ->groupBy('document_type', 'action')
                ->orderBy('count', 'desc')
                ->get();

            $dailyStats = DB::table('document_analytics')
                ->where('timestamp', '>=', $startDate)
                ->selectRaw('
                    DATE(timestamp) as date,
                    COUNT(*) as total_views,
                    COUNT(DISTINCT user_id) as unique_users,
                    COUNT(DISTINCT document_id) as unique_documents
                ')
                ->groupBy('date')
                ->orderBy('date', 'desc')
                ->get();

            return [
                'period_days' => $days,
                'total_views' => $analytics->sum('count'),
                'unique_users' => $analytics->max('unique_users'),
                'unique_documents' => $analytics->max('unique_documents'),
                'document_type_breakdown' => $analytics->groupBy('document_type'),
                'action_breakdown' => $analytics->groupBy('action'),
                'daily_stats' => $dailyStats,
                'top_documents' => $this->getTopDocuments($startDate),
                'user_activity' => $this->getUserActivity($startDate),
            ];
        });
    }

    /**
     * Get top documents by usage.
     */
    public function getTopDocuments(Carbon $startDate, int $limit = 10): array
    {
        return DB::table('document_analytics')
            ->where('timestamp', '>=', $startDate)
            ->selectRaw('
                document_type,
                document_id,
                COUNT(*) as view_count,
                COUNT(DISTINCT user_id) as unique_users
            ')
            ->groupBy('document_type', 'document_id')
            ->orderBy('view_count', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get user activity patterns.
     */
    public function getUserActivity(Carbon $startDate): array
    {
        $hourlyActivity = DB::table('document_analytics')
            ->where('timestamp', '>=', $startDate)
            ->selectRaw('HOUR(timestamp) as hour, COUNT(*) as count')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        $dailyActivity = DB::table('document_analytics')
            ->where('timestamp', '>=', $startDate)
            ->selectRaw('DAYOFWEEK(timestamp) as day_of_week, COUNT(*) as count')
            ->groupBy('day_of_week')
            ->orderBy('day_of_week')
            ->get();

        return [
            'hourly_pattern' => $hourlyActivity,
            'daily_pattern' => $dailyActivity,
        ];
    }

    /**
     * Get performance metrics.
     */
    public function getPerformanceMetrics(): array
    {
        $cacheKey = 'performance_metrics';

        return Cache::remember($cacheKey, self::ANALYTICS_CACHE_TTL, function () {
            $metrics = [
                'cache_hit_rate' => $this->getCacheHitRate(),
                'average_response_time' => $this->getAverageResponseTime(),
                'error_rate' => $this->getErrorRate(),
                'memory_usage' => $this->getMemoryUsage(),
                'database_performance' => $this->getDatabasePerformance(),
            ];

            return $metrics;
        });
    }

    /**
     * Generate analytics report.
     */
    public function generateAnalyticsReport(int $days = 30): array
    {
        $report = [
            'generated_at' => now(),
            'period_days' => $days,
            'system_overview' => $this->getSystemAnalytics($days),
            'performance_metrics' => $this->getPerformanceMetrics(),
            'recommendations' => $this->getRecommendations($days),
        ];

        return $report;
    }

    /**
     * Update real-time cache.
     */
    protected function updateRealTimeCache(string $documentType, int $documentId, string $action): void
    {
        $cacheKey = "realtime_{$documentType}_{$documentId}";
        $current = Cache::get($cacheKey, []);

        $current[$action] = ($current[$action] ?? 0) + 1;
        $current['last_updated'] = now();

        Cache::put($cacheKey, $current, 300); // 5 minutes
    }

    /**
     * Get cache hit rate.
     */
    protected function getCacheHitRate(): float
    {
        // This would require cache driver that supports hit/miss statistics
        // For now, return a placeholder
        return 0.85; // 85% cache hit rate
    }

    /**
     * Get average response time.
     */
    protected function getAverageResponseTime(): float
    {
        // This would require response time logging
        // For now, return a placeholder
        return 150.5; // 150.5ms average response time
    }

    /**
     * Get error rate.
     */
    protected function getErrorRate(): float
    {
        // This would require error logging
        // For now, return a placeholder
        return 0.02; // 2% error rate
    }

    /**
     * Get memory usage.
     */
    protected function getMemoryUsage(): array
    {
        return [
            'current' => memory_get_usage(true),
            'peak' => memory_get_peak_usage(true),
            'limit' => ini_get('memory_limit'),
        ];
    }

    /**
     * Get database performance metrics.
     */
    protected function getDatabasePerformance(): array
    {
        // This would require database performance monitoring
        return [
            'connection_count' => 'N/A',
            'slow_queries' => 'N/A',
            'query_time' => 'N/A',
        ];
    }

    /**
     * Get optimization recommendations.
     */
    protected function getRecommendations(int $days): array
    {
        $recommendations = [];

        $systemAnalytics = $this->getSystemAnalytics($days);

        // High usage documents should be cached
        if ($systemAnalytics['total_views'] > 1000) {
            $recommendations[] = 'Consider implementing aggressive caching for high-traffic documents';
        }

        // Check for performance issues
        $performanceMetrics = $this->getPerformanceMetrics();
        if ($performanceMetrics['average_response_time'] > 500) {
            $recommendations[] = 'Average response time is high, consider query optimization';
        }

        if ($performanceMetrics['cache_hit_rate'] < 0.8) {
            $recommendations[] = 'Cache hit rate is low, consider increasing cache TTL or improving cache keys';
        }

        return $recommendations;
    }

    /**
     * Clean up old analytics data.
     */
    public function cleanupOldData(int $daysToKeep = 90): void
    {
        $cutoffDate = now()->subDays($daysToKeep);

        $deleted = DB::table('document_analytics')
            ->where('timestamp', '<', $cutoffDate)
            ->delete();

        $this->command->info("Cleaned up {$deleted} old analytics records");
    }

    /**
     * Export analytics data.
     */
    public function exportAnalyticsData(int $days = 30, string $format = 'json'): string
    {
        $data = $this->getSystemAnalytics($days);

        switch ($format) {
            case 'json':
                return json_encode($data, JSON_PRETTY_PRINT);
            case 'csv':
                return $this->convertToCsv($data);
            default:
                throw new \InvalidArgumentException('Unsupported export format');
        }
    }

    /**
     * Convert data to CSV format.
     */
    protected function convertToCsv(array $data): string
    {
        $csv = '';

        // This is a simplified CSV conversion
        // In a real implementation, you'd want a more robust CSV library
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $csv .= $key . ',' . json_encode($value) . "\n";
            } else {
                $csv .= $key . ',' . $value . "\n";
            }
        }

        return $csv;
    }
}
