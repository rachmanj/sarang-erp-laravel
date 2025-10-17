<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\DocumentRelationship;
use Illuminate\Database\Eloquent\Model;

class DocumentRelationshipCacheService
{
    protected const CACHE_PREFIX = 'doc_rel_';
    protected const CACHE_TTL = 3600; // 1 hour

    /**
     * Get cached navigation data for a document.
     */
    public function getCachedNavigationData(Model $document, $user): array
    {
        $cacheKey = $this->generateCacheKey($document, $user);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($document, $user) {
            $relationshipService = app(DocumentRelationshipService::class);
            return $relationshipService->getNavigationData($document, $user);
        });
    }

    /**
     * Get cached base documents for a document.
     */
    public function getCachedBaseDocuments(Model $document, $user): array
    {
        $cacheKey = $this->generateBaseDocumentsCacheKey($document, $user);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($document, $user) {
            $relationshipService = app(DocumentRelationshipService::class);
            return $relationshipService->getBaseDocuments($document, $user);
        });
    }

    /**
     * Get cached target documents for a document.
     */
    public function getCachedTargetDocuments(Model $document, $user): array
    {
        $cacheKey = $this->generateTargetDocumentsCacheKey($document, $user);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($document, $user) {
            $relationshipService = app(DocumentRelationshipService::class);
            return $relationshipService->getTargetDocuments($document, $user);
        });
    }

    /**
     * Invalidate cache for a specific document.
     */
    public function invalidateDocumentCache(Model $document, $user = null): void
    {
        $cacheKeys = [
            $this->generateCacheKey($document, $user),
            $this->generateBaseDocumentsCacheKey($document, $user),
            $this->generateTargetDocumentsCacheKey($document, $user),
        ];

        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }

        // Also invalidate related documents' caches
        $this->invalidateRelatedDocumentsCache($document);
    }

    /**
     * Invalidate cache for related documents.
     */
    protected function invalidateRelatedDocumentsCache(Model $document): void
    {
        $relationships = DocumentRelationship::where(function ($query) use ($document) {
            $query->where('source_document_type', $document->getMorphClass())
                ->where('source_document_id', $document->id)
                ->orWhere(function ($q) use ($document) {
                    $q->where('target_document_type', $document->getMorphClass())
                        ->where('target_document_id', $document->id);
                });
        })->get();

        foreach ($relationships as $relationship) {
            // Invalidate cache for source document
            if (
                $relationship->source_document_type !== $document->getMorphClass() ||
                $relationship->source_document_id !== $document->id
            ) {
                $this->invalidateDocumentCache(
                    $relationship->sourceDocument,
                    null // Will invalidate for all users
                );
            }

            // Invalidate cache for target document
            if (
                $relationship->target_document_type !== $document->getMorphClass() ||
                $relationship->target_document_id !== $document->id
            ) {
                $this->invalidateDocumentCache(
                    $relationship->targetDocument,
                    null // Will invalidate for all users
                );
            }
        }
    }

    /**
     * Warm up cache for frequently accessed documents.
     */
    public function warmUpCache(): void
    {
        $this->command->info('Warming up document relationship cache...');

        // Get recent documents (last 30 days)
        $recentDocuments = $this->getRecentDocuments();

        foreach ($recentDocuments as $document) {
            try {
                $this->getCachedNavigationData($document, null);
                $this->command->info("Warmed cache for {$document->getMorphClass()} #{$document->id}");
            } catch (\Exception $e) {
                $this->command->error("Failed to warm cache for {$document->getMorphClass()} #{$document->id}: " . $e->getMessage());
            }
        }

        $this->command->info('Cache warming completed!');
    }

    /**
     * Get recent documents for cache warming.
     */
    protected function getRecentDocuments(): array
    {
        $documents = [];
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

            $recent = $model->where($dateField, '>=', now()->subDays(30))
                ->limit(50)
                ->get();

            $documents = array_merge($documents, $recent->toArray());
        }

        return $documents;
    }

    /**
     * Get the appropriate date field for a model.
     */
    protected function getDateField(Model $model): string
    {
        $dateFields = ['date', 'created_at', 'updated_at'];

        foreach ($dateFields as $field) {
            if (in_array($field, $model->getFillable()) || $model->getDates()) {
                return $field;
            }
        }

        return 'created_at';
    }

    /**
     * Generate cache key for navigation data.
     */
    protected function generateCacheKey(Model $document, $user): string
    {
        $userId = $user ? $user->id : 'guest';
        return self::CACHE_PREFIX . 'nav_' . $document->getMorphClass() . '_' . $document->id . '_' . $userId;
    }

    /**
     * Generate cache key for base documents.
     */
    protected function generateBaseDocumentsCacheKey(Model $document, $user): string
    {
        $userId = $user ? $user->id : 'guest';
        return self::CACHE_PREFIX . 'base_' . $document->getMorphClass() . '_' . $document->id . '_' . $userId;
    }

    /**
     * Generate cache key for target documents.
     */
    protected function generateTargetDocumentsCacheKey(Model $document, $user): string
    {
        $userId = $user ? $user->id : 'guest';
        return self::CACHE_PREFIX . 'target_' . $document->getMorphClass() . '_' . $document->id . '_' . $userId;
    }

    /**
     * Clear all document relationship caches.
     */
    public function clearAllCaches(): void
    {
        Cache::flush();
        $this->command->info('All document relationship caches cleared!');
    }

    /**
     * Get cache statistics.
     */
    public function getCacheStats(): array
    {
        // This would depend on your cache driver
        // For Redis, you could get more detailed stats
        return [
            'cache_prefix' => self::CACHE_PREFIX,
            'cache_ttl' => self::CACHE_TTL,
            'driver' => config('cache.default'),
        ];
    }
}
