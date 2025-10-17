<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use App\Services\DocumentRelationshipService;
use App\Services\DocumentRelationshipCacheService;
use App\Http\Controllers\Api\JournalPreviewController;

class DocumentBulkOperationService
{
    protected DocumentRelationshipService $relationshipService;
    protected DocumentRelationshipCacheService $cacheService;
    protected JournalPreviewController $journalPreviewController;

    public function __construct(
        DocumentRelationshipService $relationshipService,
        DocumentRelationshipCacheService $cacheService,
        JournalPreviewController $journalPreviewController
    ) {
        $this->relationshipService = $relationshipService;
        $this->cacheService = $cacheService;
        $this->journalPreviewController = $journalPreviewController;
    }

    /**
     * Get bulk navigation data for multiple documents.
     */
    public function getBulkNavigationData(Collection $documents, $user): array
    {
        $results = [];

        foreach ($documents as $document) {
            try {
                $navigationData = $this->cacheService->getCachedNavigationData($document, $user);
                $results[] = [
                    'document_id' => $document->id,
                    'document_type' => $document->getMorphClass(),
                    'navigation_data' => $navigationData,
                    'success' => true,
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'document_id' => $document->id,
                    'document_type' => $document->getMorphClass(),
                    'error' => $e->getMessage(),
                    'success' => false,
                ];
            }
        }

        return $results;
    }

    /**
     * Get bulk journal previews for multiple documents.
     */
    public function getBulkJournalPreviews(Collection $documents, string $actionType = 'post'): array
    {
        $results = [];

        foreach ($documents as $document) {
            try {
                $journalPreview = $this->generateJournalPreview($document, $actionType);
                $results[] = [
                    'document_id' => $document->id,
                    'document_type' => $document->getMorphClass(),
                    'journal_preview' => $journalPreview,
                    'success' => true,
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'document_id' => $document->id,
                    'document_type' => $document->getMorphClass(),
                    'error' => $e->getMessage(),
                    'success' => false,
                ];
            }
        }

        return $results;
    }

    /**
     * Get document workflow chains (complete document flows).
     */
    public function getDocumentWorkflowChains(Collection $documents, $user): array
    {
        $chains = [];

        foreach ($documents as $document) {
            try {
                $chain = $this->buildDocumentChain($document, $user);
                $chains[] = [
                    'document_id' => $document->id,
                    'document_type' => $document->getMorphClass(),
                    'workflow_chain' => $chain,
                    'success' => true,
                ];
            } catch (\Exception $e) {
                $chains[] = [
                    'document_id' => $document->id,
                    'document_type' => $document->getMorphClass(),
                    'error' => $e->getMessage(),
                    'success' => false,
                ];
            }
        }

        return $chains;
    }

    /**
     * Build a complete document chain from base to target.
     */
    protected function buildDocumentChain(Model $document, $user): array
    {
        $chain = [];
        $visited = [];

        // Build chain backwards (to base documents)
        $this->buildChainBackwards($document, $user, $chain, $visited);

        // Reverse to get proper order
        $chain = array_reverse($chain);

        // Build chain forwards (to target documents)
        $this->buildChainForwards($document, $user, $chain, $visited);

        return $chain;
    }

    /**
     * Build chain backwards to base documents.
     */
    protected function buildChainBackwards(Model $document, $user, array &$chain, array &$visited): void
    {
        $key = $document->getMorphClass() . '_' . $document->id;

        if (in_array($key, $visited)) {
            return; // Prevent infinite loops
        }

        $visited[] = $key;

        $baseDocuments = $this->cacheService->getCachedBaseDocuments($document, $user);

        foreach ($baseDocuments as $baseDoc) {
            $chain[] = [
                'id' => $baseDoc->id,
                'type' => $baseDoc->getMorphClass(),
                'number' => $this->getDocumentNumber($baseDoc),
                'status' => $baseDoc->status ?? 'N/A',
                'amount' => $baseDoc->total_amount ?? $baseDoc->amount ?? 0,
                'date' => $baseDoc->date ?? $baseDoc->created_at,
                'relationship_type' => 'base',
            ];

            // Recursively build chain for base document
            $this->buildChainBackwards($baseDoc, $user, $chain, $visited);
        }
    }

    /**
     * Build chain forwards to target documents.
     */
    protected function buildChainForwards(Model $document, $user, array &$chain, array &$visited): void
    {
        $key = $document->getMorphClass() . '_' . $document->id;

        if (in_array($key, $visited)) {
            return; // Prevent infinite loops
        }

        $visited[] = $key;

        // Add current document to chain
        $chain[] = [
            'id' => $document->id,
            'type' => $document->getMorphClass(),
            'number' => $this->getDocumentNumber($document),
            'status' => $document->status ?? 'N/A',
            'amount' => $document->total_amount ?? $document->amount ?? 0,
            'date' => $document->date ?? $document->created_at,
            'relationship_type' => 'current',
        ];

        $targetDocuments = $this->cacheService->getCachedTargetDocuments($document, $user);

        foreach ($targetDocuments as $targetDoc) {
            $chain[] = [
                'id' => $targetDoc->id,
                'type' => $targetDoc->getMorphClass(),
                'number' => $this->getDocumentNumber($targetDoc),
                'status' => $targetDoc->status ?? 'N/A',
                'amount' => $targetDoc->total_amount ?? $targetDoc->amount ?? 0,
                'date' => $targetDoc->date ?? $targetDoc->created_at,
                'relationship_type' => 'target',
            ];

            // Recursively build chain for target document
            $this->buildChainForwards($targetDoc, $user, $chain, $visited);
        }
    }

    /**
     * Generate journal preview for a document.
     */
    protected function generateJournalPreview(Model $document, string $actionType): array
    {
        // Use reflection to call the private method
        $reflection = new \ReflectionClass($this->journalPreviewController);
        $method = $reflection->getMethod('generateJournalPreview');
        $method->setAccessible(true);

        return $method->invoke($this->journalPreviewController, $document, $actionType);
    }

    /**
     * Get document number from various possible fields.
     */
    protected function getDocumentNumber(Model $document): string
    {
        $numberFields = [
            'document_number',
            'order_no',
            'invoice_no',
            'payment_no',
            'receipt_no',
            'grn_no',
            'do_number',
        ];

        foreach ($numberFields as $field) {
            if (isset($document->$field) && !empty($document->$field)) {
                return $document->$field;
            }
        }

        return '#' . $document->id;
    }

    /**
     * Get document statistics for a collection of documents.
     */
    public function getDocumentStatistics(Collection $documents, $user): array
    {
        $stats = [
            'total_documents' => $documents->count(),
            'by_type' => [],
            'by_status' => [],
            'total_amount' => 0,
            'date_range' => [
                'earliest' => null,
                'latest' => null,
            ],
        ];

        foreach ($documents as $document) {
            $type = $document->getMorphClass();
            $status = $document->status ?? 'unknown';
            $amount = $document->total_amount ?? $document->amount ?? 0;
            $date = $document->date ?? $document->created_at;

            // Count by type
            if (!isset($stats['by_type'][$type])) {
                $stats['by_type'][$type] = 0;
            }
            $stats['by_type'][$type]++;

            // Count by status
            if (!isset($stats['by_status'][$status])) {
                $stats['by_status'][$status] = 0;
            }
            $stats['by_status'][$status]++;

            // Sum amounts
            $stats['total_amount'] += $amount;

            // Track date range
            if ($date) {
                if (!$stats['date_range']['earliest'] || $date < $stats['date_range']['earliest']) {
                    $stats['date_range']['earliest'] = $date;
                }
                if (!$stats['date_range']['latest'] || $date > $stats['date_range']['latest']) {
                    $stats['date_range']['latest'] = $date;
                }
            }
        }

        return $stats;
    }
}
