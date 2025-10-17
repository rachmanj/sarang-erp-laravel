<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class DocumentPerformanceOptimizationService
{
    protected const QUERY_CACHE_TTL = 1800; // 30 minutes
    protected const RELATIONSHIP_CACHE_TTL = 3600; // 1 hour

    /**
     * Optimize document queries with eager loading and caching.
     */
    public function getOptimizedDocument(string $documentType, int $documentId): ?Model
    {
        $cacheKey = "optimized_doc_{$documentType}_{$documentId}";

        return Cache::remember($cacheKey, self::QUERY_CACHE_TTL, function () use ($documentType, $documentId) {
            $modelClass = $this->getModelClass($documentType);

            if (!$modelClass) {
                return null;
            }

            // Define relationships to eager load based on document type
            $relationships = $this->getRequiredRelationships($documentType);

            return $modelClass::with($relationships)->find($documentId);
        });
    }

    /**
     * Optimize bulk document queries.
     */
    public function getOptimizedDocuments(string $documentType, array $documentIds): \Illuminate\Database\Eloquent\Collection
    {
        $cacheKey = "optimized_docs_{$documentType}_" . md5(implode(',', $documentIds));

        return Cache::remember($cacheKey, self::QUERY_CACHE_TTL, function () use ($documentType, $documentIds) {
            $modelClass = $this->getModelClass($documentType);

            if (!$modelClass) {
                return collect();
            }

            $relationships = $this->getRequiredRelationships($documentType);

            return $modelClass::with($relationships)
                ->whereIn('id', $documentIds)
                ->get();
        });
    }

    /**
     * Optimize relationship queries with batch loading.
     */
    public function getOptimizedRelationships(array $documents, string $relationshipType): array
    {
        $cacheKey = "optimized_rels_{$relationshipType}_" . md5(serialize($documents));

        return Cache::remember($cacheKey, self::RELATIONSHIP_CACHE_TTL, function () use ($documents, $relationshipType) {
            $results = [];

            // Group documents by type for batch processing
            $groupedDocs = collect($documents)->groupBy(function ($doc) {
                return $doc['document_type'];
            });

            foreach ($groupedDocs as $documentType => $docs) {
                $modelClass = $this->getModelClass($documentType);

                if (!$modelClass) {
                    continue;
                }

                $documentIds = $docs->pluck('document_id')->toArray();

                // Batch load relationships
                $relationships = $this->batchLoadRelationships($modelClass, $documentIds, $relationshipType);

                foreach ($relationships as $docId => $rels) {
                    $results["{$documentType}_{$docId}"] = $rels;
                }
            }

            return $results;
        });
    }

    /**
     * Optimize database queries with query optimization.
     */
    public function optimizeQuery(Builder $query, array $options = []): Builder
    {
        // Add indexes hints if needed
        if (isset($options['use_index'])) {
            $query->from(DB::raw($query->getModel()->getTable() . ' USE INDEX (' . $options['use_index'] . ')'));
        }

        // Optimize joins
        if (isset($options['optimize_joins'])) {
            $query = $this->optimizeJoins($query);
        }

        // Add query caching
        if (isset($options['cache']) && $options['cache']) {
            $cacheKey = 'query_' . md5($query->toSql() . serialize($query->getBindings()));
            $query->remember($options['cache_ttl'] ?? self::QUERY_CACHE_TTL, $cacheKey);
        }

        return $query;
    }

    /**
     * Get database performance statistics.
     */
    public function getPerformanceStats(): array
    {
        $stats = [
            'cache_hits' => $this->getCacheHitStats(),
            'query_performance' => $this->getQueryPerformanceStats(),
            'memory_usage' => $this->getMemoryUsageStats(),
            'optimization_suggestions' => $this->getOptimizationSuggestions(),
        ];

        return $stats;
    }

    /**
     * Warm up frequently accessed data.
     */
    public function warmUpFrequentData(): void
    {
        $this->command->info('Warming up frequently accessed document data...');

        // Warm up recent documents
        $this->warmUpRecentDocuments();

        // Warm up relationship data
        $this->warmUpRelationshipData();

        // Warm up user permissions
        $this->warmUpUserPermissions();

        $this->command->info('Data warming completed!');
    }

    /**
     * Clear performance caches.
     */
    public function clearPerformanceCaches(): void
    {
        Cache::tags(['document_queries', 'document_relationships'])->flush();
        $this->command->info('Performance caches cleared!');
    }

    /**
     * Get model class for document type.
     */
    protected function getModelClass(string $documentType): ?string
    {
        $modelMap = [
            'purchase-order' => \App\Models\PurchaseOrder::class,
            'goods-receipt-po' => \App\Models\GoodsReceiptPO::class,
            'purchase-invoice' => \App\Models\Accounting\PurchaseInvoice::class,
            'purchase-payment' => \App\Models\Accounting\PurchasePayment::class,
            'sales-order' => \App\Models\SalesOrder::class,
            'delivery-order' => \App\Models\DeliveryOrder::class,
            'sales-invoice' => \App\Models\Accounting\SalesInvoice::class,
            'sales-receipt' => \App\Models\Accounting\SalesReceipt::class,
        ];

        return $modelMap[$documentType] ?? null;
    }

    /**
     * Get required relationships for document type.
     */
    protected function getRequiredRelationships(string $documentType): array
    {
        $relationshipMap = [
            'purchase-order' => ['businessPartner', 'lines.item'],
            'goods-receipt-po' => ['businessPartner', 'lines.item', 'purchaseOrder'],
            'purchase-invoice' => ['businessPartner', 'lines', 'purchaseOrder', 'goodsReceipt'],
            'purchase-payment' => ['businessPartner', 'lines'],
            'sales-order' => ['customer', 'lines.item'],
            'delivery-order' => ['customer', 'salesOrder', 'lines.item'],
            'sales-invoice' => ['businessPartner', 'lines', 'salesOrder'],
            'sales-receipt' => ['businessPartner', 'lines'],
        ];

        return $relationshipMap[$documentType] ?? ['businessPartner'];
    }

    /**
     * Batch load relationships for multiple documents.
     */
    protected function batchLoadRelationships(string $modelClass, array $documentIds, string $relationshipType): array
    {
        $results = [];

        // Use a single query to load all relationships
        $documents = $modelClass::whereIn('id', $documentIds)->get();

        foreach ($documents as $document) {
            $relationships = $this->loadDocumentRelationships($document, $relationshipType);
            $results[$document->id] = $relationships;
        }

        return $results;
    }

    /**
     * Load relationships for a specific document.
     */
    protected function loadDocumentRelationships(Model $document, string $relationshipType): array
    {
        $relationships = [];

        if ($relationshipType === 'base') {
            $relationships = $this->getBaseRelationships($document);
        } elseif ($relationshipType === 'target') {
            $relationships = $this->getTargetRelationships($document);
        }

        return $relationships;
    }

    /**
     * Get base relationships for a document.
     */
    protected function getBaseRelationships(Model $document): array
    {
        // Implementation would depend on your relationship logic
        return [];
    }

    /**
     * Get target relationships for a document.
     */
    protected function getTargetRelationships(Model $document): array
    {
        // Implementation would depend on your relationship logic
        return [];
    }

    /**
     * Optimize joins in query.
     */
    protected function optimizeJoins(Builder $query): Builder
    {
        // Add join optimization logic here
        return $query;
    }

    /**
     * Get cache hit statistics.
     */
    protected function getCacheHitStats(): array
    {
        // This would depend on your cache driver
        return [
            'cache_driver' => config('cache.default'),
            'cache_prefix' => config('cache.prefix'),
        ];
    }

    /**
     * Get query performance statistics.
     */
    protected function getQueryPerformanceStats(): array
    {
        // This would require query logging to be enabled
        return [
            'slow_query_threshold' => '2 seconds',
            'total_queries' => 'N/A (requires query logging)',
        ];
    }

    /**
     * Get memory usage statistics.
     */
    protected function getMemoryUsageStats(): array
    {
        return [
            'current_memory' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
            'memory_limit' => ini_get('memory_limit'),
        ];
    }

    /**
     * Get optimization suggestions.
     */
    protected function getOptimizationSuggestions(): array
    {
        $suggestions = [];

        // Check for missing indexes
        $suggestions[] = 'Consider adding database indexes for frequently queried columns';

        // Check cache configuration
        if (config('cache.default') === 'file') {
            $suggestions[] = 'Consider using Redis or Memcached for better cache performance';
        }

        // Check query optimization
        $suggestions[] = 'Enable query logging to identify slow queries';

        return $suggestions;
    }

    /**
     * Warm up recent documents.
     */
    protected function warmUpRecentDocuments(): void
    {
        $models = [
            \App\Models\PurchaseOrder::class,
            \App\Models\GoodsReceiptPO::class,
            \App\Models\Accounting\PurchaseInvoice::class,
            \App\Models\Accounting\PurchasePayment::class,
            \App\Models\SalesOrder::class,
            \App\Models\DeliveryOrder::class,
            \App\Models\Accounting\SalesInvoice::class,
            \App\Models\Accounting\SalesReceipt::class,
        ];

        foreach ($models as $modelClass) {
            $model = new $modelClass;
            $dateField = $this->getDateField($model);

            $recent = $model->where($dateField, '>=', now()->subDays(7))
                ->limit(20)
                ->get();

            foreach ($recent as $document) {
                $this->getOptimizedDocument($this->getDocumentType($modelClass), $document->id);
            }
        }
    }

    /**
     * Warm up relationship data.
     */
    protected function warmUpRelationshipData(): void
    {
        // Implementation for warming up relationship data
    }

    /**
     * Warm up user permissions.
     */
    protected function warmUpUserPermissions(): void
    {
        // Implementation for warming up user permissions
    }

    /**
     * Get date field for model.
     */
    protected function getDateField(Model $model): string
    {
        $dateFields = ['date', 'created_at', 'updated_at'];

        foreach ($dateFields as $field) {
            if (in_array($field, $model->getFillable()) || in_array($field, $model->getDates())) {
                return $field;
            }
        }

        return 'created_at';
    }

    /**
     * Get document type from model class.
     */
    protected function getDocumentType(string $modelClass): string
    {
        $typeMap = [
            \App\Models\PurchaseOrder::class => 'purchase-order',
            \App\Models\GoodsReceiptPO::class => 'goods-receipt-po',
            \App\Models\Accounting\PurchaseInvoice::class => 'purchase-invoice',
            \App\Models\Accounting\PurchasePayment::class => 'purchase-payment',
            \App\Models\SalesOrder::class => 'sales-order',
            \App\Models\DeliveryOrder::class => 'delivery-order',
            \App\Models\Accounting\SalesInvoice::class => 'sales-invoice',
            \App\Models\Accounting\SalesReceipt::class => 'sales-receipt',
        ];

        return $typeMap[$modelClass] ?? 'unknown';
    }
}
