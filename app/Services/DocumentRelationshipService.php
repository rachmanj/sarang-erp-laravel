<?php

namespace App\Services;

use App\Models\DocumentRelationship;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DocumentRelationshipService
{
    /**
     * Cache duration in minutes
     */
    private const CACHE_DURATION = 60;

    /**
     * Get base documents for a given document
     */
    public function getBaseDocuments($document, User $user = null): Collection
    {
        $cacheKey = "base_documents_{$document->getMorphClass()}_{$document->id}";

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($document, $user) {
            $relationships = DocumentRelationship::where('target_document_type', $document->getMorphClass())
                ->where('target_document_id', $document->id)
                ->where('relationship_type', 'base')
                ->with(['sourceDocument'])
                ->get();

            $baseDocuments = $relationships->map(function ($relationship) {
                return $relationship->sourceDocument;
            })->filter();

            // Filter by user permissions if user is provided
            if ($user) {
                $baseDocuments = $this->filterByUserPermissions($baseDocuments, $user);
            }

            return $baseDocuments;
        });
    }

    /**
     * Get target documents for a given document
     */
    public function getTargetDocuments($document, User $user = null): Collection
    {
        $cacheKey = "target_documents_{$document->getMorphClass()}_{$document->id}";

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($document, $user) {
            $relationships = DocumentRelationship::where('source_document_type', $document->getMorphClass())
                ->where('source_document_id', $document->id)
                ->where('relationship_type', 'target')
                ->with(['targetDocument'])
                ->get();

            $targetDocuments = $relationships->map(function ($relationship) {
                return $relationship->targetDocument;
            })->filter();

            // Filter by user permissions if user is provided
            if ($user) {
                $targetDocuments = $this->filterByUserPermissions($targetDocuments, $user);
            }

            return $targetDocuments;
        });
    }

    /**
     * Get button state for document navigation
     */
    public function getButtonState(Collection $documents): string
    {
        $count = $documents->count();

        if ($count === 0) return 'disabled';
        if ($count === 1) return 'single';
        return 'multiple';
    }

    /**
     * Create a document relationship
     */
    public function createRelationship(
        string $sourceType,
        int $sourceId,
        string $targetType,
        int $targetId,
        string $relationshipType = 'related',
        string $notes = null
    ): DocumentRelationship {
        return DocumentRelationship::create([
            'source_document_type' => $sourceType,
            'source_document_id' => $sourceId,
            'target_document_type' => $targetType,
            'target_document_id' => $targetId,
            'relationship_type' => $relationshipType,
            'notes' => $notes,
        ]);
    }

    /**
     * Create base relationship (source -> target)
     */
    public function createBaseRelationship($sourceDocument, $targetDocument, string $notes = null): DocumentRelationship
    {
        return $this->createRelationship(
            $sourceDocument->getMorphClass(),
            $sourceDocument->id,
            $targetDocument->getMorphClass(),
            $targetDocument->id,
            'base',
            $notes
        );
    }

    /**
     * Create target relationship (source -> target)
     */
    public function createTargetRelationship($sourceDocument, $targetDocument, string $notes = null): DocumentRelationship
    {
        return $this->createRelationship(
            $sourceDocument->getMorphClass(),
            $sourceDocument->id,
            $targetDocument->getMorphClass(),
            $targetDocument->id,
            'target',
            $notes
        );
    }

    /**
     * Filter documents by user permissions
     */
    private function filterByUserPermissions(Collection $documents, User $user): Collection
    {
        return $documents->filter(function ($document) use ($user) {
            if (!$document) return false;

            $permission = DocumentRelationship::getDocumentPermission($document->getMorphClass());
            return $user->can($permission . '.view');
        });
    }

    /**
     * Clear cache for a document
     */
    public function clearDocumentCache($document): void
    {
        $baseCacheKey = "base_documents_{$document->getMorphClass()}_{$document->id}";
        $targetCacheKey = "target_documents_{$document->getMorphClass()}_{$document->id}";

        Cache::forget($baseCacheKey);
        Cache::forget($targetCacheKey);
    }

    /**
     * Get navigation data for a document
     */
    public function getNavigationData($document, User $user = null): array
    {
        $baseDocuments = $this->getBaseDocuments($document, $user);
        $targetDocuments = $this->getTargetDocuments($document, $user);

        return [
            'base_documents' => [
                'documents' => $baseDocuments->map(function ($doc) {
                    return [
                        'id' => $doc->id,
                        'type' => $doc->getMorphClass(),
                        'number' => $doc->order_no ?? $doc->grn_no ?? $doc->invoice_no ?? $doc->payment_no ?? $doc->receipt_no ?? $doc->do_number ?? 'N/A',
                        'status' => $doc->status ?? 'N/A',
                        'amount' => $doc->total_amount ?? $doc->amount ?? 0,
                        'date' => $doc->date ?? $doc->created_at,
                        'url' => $this->getDocumentUrl($doc),
                    ];
                }),
                'state' => $this->getButtonState($baseDocuments),
                'count' => $baseDocuments->count(),
            ],
            'target_documents' => [
                'documents' => $targetDocuments->map(function ($doc) {
                    return [
                        'id' => $doc->id,
                        'type' => $doc->getMorphClass(),
                        'number' => $doc->order_no ?? $doc->grn_no ?? $doc->invoice_no ?? $doc->payment_no ?? $doc->receipt_no ?? $doc->do_number ?? 'N/A',
                        'status' => $doc->status ?? 'N/A',
                        'amount' => $doc->total_amount ?? $doc->amount ?? 0,
                        'date' => $doc->date ?? $doc->created_at,
                        'url' => $this->getDocumentUrl($doc),
                    ];
                }),
                'state' => $this->getButtonState($targetDocuments),
                'count' => $targetDocuments->count(),
            ],
        ];
    }

    /**
     * Get URL for a document
     */
    private function getDocumentUrl($document): string
    {
        $type = $document->getMorphClass();
        $id = $document->id;

        $routes = [
            'App\Models\PurchaseOrder' => 'purchase-orders.show',
            'App\Models\GoodsReceiptPO' => 'goods-receipt-pos.show',
            'App\Models\PurchaseInvoice' => 'purchase-invoices.show',
            'App\Models\PurchasePayment' => 'purchase-payments.show',
            'App\Models\SalesOrder' => 'sales-orders.show',
            'App\Models\DeliveryOrder' => 'delivery-orders.show',
            'App\Models\SalesInvoice' => 'sales-invoices.show',
            'App\Models\SalesReceipt' => 'sales-receipts.show',
        ];

        $route = $routes[$type] ?? 'documents.show';
        return route($route, $id);
    }

    /**
     * Initialize relationships for existing documents
     */
    public function initializeExistingRelationships(): void
    {
        DB::transaction(function () {
            // Initialize GRPO -> PO relationships
            $this->initializeGRPORelationships();

            // Initialize PI -> GRPO relationships
            $this->initializePIRelationships();

            // Initialize PP -> PI relationships
            $this->initializePPRelationships();

            // Initialize DO -> SO relationships
            $this->initializeDORelationships();

            // Initialize SI -> DO relationships
            $this->initializeSIRelationships();

            // Initialize SR -> SI relationships
            $this->initializeSRRelationships();
        });
    }

    /**
     * Initialize GRPO -> PO relationships
     */
    private function initializeGRPORelationships(): void
    {
        $grpos = DB::table('goods_receipt_po')
            ->whereNotNull('purchase_order_id')
            ->get();

        foreach ($grpos as $grpo) {
            DocumentRelationship::updateOrCreate([
                'source_document_type' => 'App\Models\PurchaseOrder',
                'source_document_id' => $grpo->purchase_order_id,
                'target_document_type' => 'App\Models\GoodsReceiptPO',
                'target_document_id' => $grpo->id,
                'relationship_type' => 'target',
            ]);

            DocumentRelationship::updateOrCreate([
                'source_document_type' => 'App\Models\GoodsReceiptPO',
                'source_document_id' => $grpo->id,
                'target_document_type' => 'App\Models\PurchaseOrder',
                'target_document_id' => $grpo->purchase_order_id,
                'relationship_type' => 'base',
            ]);
        }
    }

    /**
     * Initialize PI -> GRPO relationships
     */
    private function initializePIRelationships(): void
    {
        $pis = DB::table('purchase_invoices')
            ->whereNotNull('goods_receipt_id')
            ->get();

        foreach ($pis as $pi) {
            DocumentRelationship::updateOrCreate([
                'source_document_type' => 'App\Models\GoodsReceiptPO',
                'source_document_id' => $pi->goods_receipt_id,
                'target_document_type' => 'App\Models\PurchaseInvoice',
                'target_document_id' => $pi->id,
                'relationship_type' => 'target',
            ]);

            DocumentRelationship::updateOrCreate([
                'source_document_type' => 'App\Models\GoodsReceiptPO',
                'source_document_id' => $pi->goods_receipt_id,
                'target_document_type' => 'App\Models\PurchaseInvoice',
                'target_document_id' => $pi->id,
                'relationship_type' => 'base',
            ]);
        }
    }

    /**
     * Initialize PP -> PI relationships
     */
    private function initializePPRelationships(): void
    {
        $allocations = DB::table('purchase_payment_allocations')
            ->join('purchase_payments', 'purchase_payment_allocations.payment_id', '=', 'purchase_payments.id')
            ->join('purchase_invoices', 'purchase_payment_allocations.invoice_id', '=', 'purchase_invoices.id')
            ->select('purchase_payments.id as payment_id', 'purchase_invoices.id as invoice_id')
            ->get();

        foreach ($allocations as $allocation) {
            DocumentRelationship::updateOrCreate([
                'source_document_type' => 'App\Models\PurchaseInvoice',
                'source_document_id' => $allocation->invoice_id,
                'target_document_type' => 'App\Models\PurchasePayment',
                'target_document_id' => $allocation->payment_id,
                'relationship_type' => 'target',
            ]);

            DocumentRelationship::updateOrCreate([
                'source_document_type' => 'App\Models\PurchaseInvoice',
                'source_document_id' => $allocation->invoice_id,
                'target_document_type' => 'App\Models\PurchasePayment',
                'target_document_id' => $allocation->payment_id,
                'relationship_type' => 'base',
            ]);
        }
    }

    /**
     * Initialize DO -> SO relationships
     */
    private function initializeDORelationships(): void
    {
        $dos = DB::table('delivery_orders')
            ->whereNotNull('sales_order_id')
            ->get();

        foreach ($dos as $do) {
            DocumentRelationship::updateOrCreate([
                'source_document_type' => 'App\Models\SalesOrder',
                'source_document_id' => $do->sales_order_id,
                'target_document_type' => 'App\Models\DeliveryOrder',
                'target_document_id' => $do->id,
                'relationship_type' => 'target',
            ]);

            DocumentRelationship::updateOrCreate([
                'source_document_type' => 'App\Models\SalesOrder',
                'source_document_id' => $do->sales_order_id,
                'target_document_type' => 'App\Models\DeliveryOrder',
                'target_document_id' => $do->id,
                'relationship_type' => 'base',
            ]);
        }
    }

    /**
     * Initialize SI -> GRPO relationships
     */
    private function initializeSIRelationships(): void
    {
        $combinations = DB::table('sales_invoice_grpo_combinations')
            ->join('sales_invoices', 'sales_invoice_grpo_combinations.sales_invoice_id', '=', 'sales_invoices.id')
            ->join('goods_receipt_po', 'sales_invoice_grpo_combinations.goods_receipt_id', '=', 'goods_receipt_po.id')
            ->select('sales_invoices.id as invoice_id', 'goods_receipt_po.id as grpo_id')
            ->get();

        foreach ($combinations as $combination) {
            DocumentRelationship::updateOrCreate([
                'source_document_type' => 'App\Models\GoodsReceiptPO',
                'source_document_id' => $combination->grpo_id,
                'target_document_type' => 'App\Models\SalesInvoice',
                'target_document_id' => $combination->invoice_id,
                'relationship_type' => 'target',
            ]);

            DocumentRelationship::updateOrCreate([
                'source_document_type' => 'App\Models\GoodsReceiptPO',
                'source_document_id' => $combination->grpo_id,
                'target_document_type' => 'App\Models\SalesInvoice',
                'target_document_id' => $combination->invoice_id,
                'relationship_type' => 'base',
            ]);
        }
    }

    /**
     * Initialize SR -> SI relationships
     */
    private function initializeSRRelationships(): void
    {
        $allocations = DB::table('sales_receipt_allocations')
            ->join('sales_receipts', 'sales_receipt_allocations.receipt_id', '=', 'sales_receipts.id')
            ->join('sales_invoices', 'sales_receipt_allocations.invoice_id', '=', 'sales_invoices.id')
            ->select('sales_receipts.id as receipt_id', 'sales_invoices.id as invoice_id')
            ->get();

        foreach ($allocations as $allocation) {
            DocumentRelationship::updateOrCreate([
                'source_document_type' => 'App\Models\SalesInvoice',
                'source_document_id' => $allocation->invoice_id,
                'target_document_type' => 'App\Models\SalesReceipt',
                'target_document_id' => $allocation->receipt_id,
                'relationship_type' => 'target',
            ]);

            DocumentRelationship::updateOrCreate([
                'source_document_type' => 'App\Models\SalesInvoice',
                'source_document_id' => $allocation->invoice_id,
                'target_document_type' => 'App\Models\SalesReceipt',
                'target_document_id' => $allocation->receipt_id,
                'relationship_type' => 'base',
            ]);
        }
    }
}
