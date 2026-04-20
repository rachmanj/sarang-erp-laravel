<?php

namespace App\Services;

use App\Models\Accounting\PurchaseInvoice;
use App\Models\Accounting\PurchasePayment;
use App\Models\Accounting\SalesCreditMemo;
use App\Models\Accounting\SalesInvoice;
use App\Models\Accounting\SalesReceipt;
use App\Models\DeliveryOrder;
use App\Models\DocumentRelationship;
use App\Models\GoodsReceiptPO;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\SalesQuotation;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
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
     * Max BFS depth when expanding the relationship map for sales documents (SO→DO→SI→SR, etc.).
     */
    private const SALES_MAP_MAX_DEPTH = 8;

    /**
     * Get base documents for a given document
     */
    public function getBaseDocuments($document, ?User $user = null): Collection
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
    public function getTargetDocuments($document, ?User $user = null): Collection
    {
        // v3: bust caches from morphTo name mismatch breaking eager-loaded targetDocument/sourceDocument
        $cacheKey = "target_documents_v3_{$document->getMorphClass()}_{$document->id}";

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($document, $user) {
            $relationships = DocumentRelationship::where('source_document_type', $document->getMorphClass())
                ->where('source_document_id', $document->id)
                ->whereIn('relationship_type', ['base', 'target'])
                ->with(['targetDocument'])
                ->get();

            $targetDocuments = $relationships->map(function ($relationship) {
                return $relationship->targetDocument;
            })->filter();

            $targetDocuments = $targetDocuments->unique(function ($doc) {
                return $doc->getMorphClass().'-'.$doc->getKey();
            })->values();

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

        if ($count === 0) {
            return 'disabled';
        }
        if ($count === 1) {
            return 'single';
        }

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
        ?string $notes = null
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
    public function createBaseRelationship($sourceDocument, $targetDocument, ?string $notes = null): DocumentRelationship
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
    public function createTargetRelationship($sourceDocument, $targetDocument, ?string $notes = null): DocumentRelationship
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
            if (! $document) {
                return false;
            }

            $permission = DocumentRelationship::getDocumentPermission($document->getMorphClass());

            return $user->can($permission.'.view');
        });
    }

    /**
     * Clear cache for a document
     */
    public function clearDocumentCache($document): void
    {
        $baseCacheKey = "base_documents_{$document->getMorphClass()}_{$document->id}";
        $targetCacheKeyLegacy = "target_documents_{$document->getMorphClass()}_{$document->id}";
        $targetCacheKeyV2 = "target_documents_v2_{$document->getMorphClass()}_{$document->id}";
        $targetCacheKey = "target_documents_v3_{$document->getMorphClass()}_{$document->id}";

        Cache::forget($baseCacheKey);
        Cache::forget($targetCacheKeyLegacy);
        Cache::forget($targetCacheKeyV2);
        Cache::forget($targetCacheKey);
    }

    public function syncGoodsReceiptPORelationships(GoodsReceiptPO $grpo): void
    {
        $morphGrpo = $grpo->getMorphClass();
        $morphPo = (new PurchaseOrder)->getMorphClass();

        DocumentRelationship::where(function ($q) use ($grpo, $morphGrpo, $morphPo) {
            $q->where(function ($q2) use ($grpo, $morphGrpo, $morphPo) {
                $q2->where('source_document_type', $morphPo)
                    ->where('target_document_type', $morphGrpo)
                    ->where('target_document_id', $grpo->id);
            })->orWhere(function ($q2) use ($grpo, $morphGrpo, $morphPo) {
                $q2->where('source_document_type', $morphGrpo)
                    ->where('source_document_id', $grpo->id)
                    ->where('target_document_type', $morphPo);
            });
        })->whereIn('relationship_type', ['base', 'target'])->delete();

        if ($grpo->purchase_order_id) {
            $po = PurchaseOrder::find($grpo->purchase_order_id);
            if ($po) {
                $this->createBaseRelationship($po, $grpo);
                $this->createTargetRelationship($po, $grpo);
                $this->clearDocumentCache($po);
            }
        }

        $this->clearDocumentCache($grpo);
    }

    public function syncPurchaseInvoiceRelationships(PurchaseInvoice $invoice): void
    {
        $morphPi = $invoice->getMorphClass();
        $morphPp = (new PurchasePayment)->getMorphClass();

        DocumentRelationship::where('target_document_type', $morphPi)
            ->where('target_document_id', $invoice->id)
            ->whereIn('source_document_type', [PurchaseOrder::class, GoodsReceiptPO::class])
            ->whereIn('relationship_type', ['base', 'target'])
            ->delete();

        DocumentRelationship::where('source_document_type', $morphPi)
            ->where('source_document_id', $invoice->id)
            ->where('target_document_type', $morphPp)
            ->whereIn('relationship_type', ['base', 'target'])
            ->delete();

        if ($invoice->goods_receipt_id) {
            $grpo = GoodsReceiptPO::find($invoice->goods_receipt_id);
            if ($grpo) {
                $this->createBaseRelationship($grpo, $invoice);
                $this->createTargetRelationship($grpo, $invoice);
                $this->clearDocumentCache($grpo);
            }
        } elseif ($invoice->purchase_order_id) {
            $po = PurchaseOrder::find($invoice->purchase_order_id);
            if ($po) {
                $this->createBaseRelationship($po, $invoice);
                $this->createTargetRelationship($po, $invoice);
                $this->clearDocumentCache($po);
            }
        }

        $paymentIds = DB::table('purchase_payment_allocations')
            ->where('invoice_id', $invoice->id)
            ->pluck('payment_id')
            ->unique()
            ->filter();

        foreach ($paymentIds as $paymentId) {
            $payment = PurchasePayment::find($paymentId);
            if ($payment) {
                $this->createBaseRelationship($invoice, $payment);
                $this->createTargetRelationship($invoice, $payment);
                $this->clearDocumentCache($payment);
            }
        }

        $this->clearDocumentCache($invoice);
    }

    public function syncPurchasePaymentRelationships(PurchasePayment $payment): void
    {
        $morphPayment = $payment->getMorphClass();
        $morphInvoice = (new PurchaseInvoice)->getMorphClass();

        DocumentRelationship::where('target_document_type', $morphPayment)
            ->where('target_document_id', $payment->id)
            ->where('source_document_type', $morphInvoice)
            ->whereIn('relationship_type', ['base', 'target'])
            ->delete();

        $invoiceIds = DB::table('purchase_payment_allocations')
            ->where('payment_id', $payment->id)
            ->pluck('invoice_id');

        foreach ($invoiceIds as $invoiceId) {
            $invoice = PurchaseInvoice::find($invoiceId);
            if ($invoice) {
                $this->createBaseRelationship($invoice, $payment);
                $this->createTargetRelationship($invoice, $payment);
                $this->clearDocumentCache($invoice);
            }
        }

        $this->clearDocumentCache($payment);
    }

    public function labelForMorphClass(string $morphClass): string
    {
        $labels = [
            'App\Models\PurchaseOrder' => 'Purchase Order',
            'App\Models\GoodsReceiptPO' => 'Goods Receipt PO',
            'App\Models\Accounting\PurchaseInvoice' => 'Purchase Invoice',
            'App\Models\Accounting\PurchasePayment' => 'Purchase Payment',
            'App\Models\SalesOrder' => 'Sales Order',
            'App\Models\DeliveryOrder' => 'Delivery Order',
            'App\Models\Accounting\SalesInvoice' => 'Sales Invoice',
            'App\Models\Accounting\SalesReceipt' => 'Sales Receipt',
            'App\Models\Accounting\SalesCreditMemo' => 'Sales Credit Memo',
            'App\Models\SalesQuotation' => 'Sales Quotation',
        ];

        return $labels[$morphClass] ?? 'Document';
    }

    /**
     * Get navigation data for a document
     */
    public function getNavigationData($document, ?User $user = null): array
    {
        $baseDocuments = $this->getBaseDocuments($document, $user);
        $targetDocuments = $this->getTargetDocuments($document, $user);

        return [
            'base_documents' => [
                'documents' => $baseDocuments->map(function ($doc) {
                    return [
                        'id' => $doc->id,
                        'type' => $doc->getMorphClass(),
                        'type_label' => $this->labelForMorphClass($doc->getMorphClass()),
                        'number' => $doc->order_no ?? $doc->grn_no ?? $doc->invoice_no ?? $doc->payment_no ?? $doc->receipt_no ?? $doc->do_number ?? $doc->memo_no ?? 'N/A',
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
                        'type_label' => $this->labelForMorphClass($doc->getMorphClass()),
                        'number' => $doc->order_no ?? $doc->grn_no ?? $doc->invoice_no ?? $doc->payment_no ?? $doc->receipt_no ?? $doc->do_number ?? $doc->memo_no ?? 'N/A',
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
     * Get URL for a document detail page (used by navigation API and relationship map).
     */
    public function getDocumentUrl($document): string
    {
        $type = $document->getMorphClass();
        $id = $document->id;

        $routes = [
            'App\Models\PurchaseOrder' => 'purchase-orders.show',
            'App\Models\GoodsReceiptPO' => 'goods-receipt-pos.show',
            'App\Models\PurchaseInvoice' => 'purchase-invoices.show',
            'App\Models\Accounting\PurchaseInvoice' => 'purchase-invoices.show',
            'App\Models\PurchasePayment' => 'purchase-payments.show',
            'App\Models\Accounting\PurchasePayment' => 'purchase-payments.show',
            'App\Models\SalesOrder' => 'sales-orders.show',
            'App\Models\DeliveryOrder' => 'delivery-orders.show',
            'App\Models\SalesInvoice' => 'sales-invoices.show',
            'App\Models\Accounting\SalesInvoice' => 'sales-invoices.show',
            'App\Models\SalesReceipt' => 'sales-receipts.show',
            'App\Models\Accounting\SalesReceipt' => 'sales-receipts.show',
            'App\Models\Accounting\SalesCreditMemo' => 'sales-credit-memos.show',
            'App\Models\SalesQuotation' => 'sales-quotations.show',
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
            DocumentRelationship::where('target_document_type', 'App\Models\PurchaseInvoice')->delete();
            DocumentRelationship::where('source_document_type', 'App\Models\PurchaseInvoice')->delete();
            DocumentRelationship::where('target_document_type', 'App\Models\PurchasePayment')->delete();
            DocumentRelationship::where('source_document_type', 'App\Models\PurchasePayment')->delete();

            // Initialize GRPO -> PO relationships
            $this->initializeGRPORelationships();

            // Initialize PI -> GRPO relationships
            $this->initializePIRelationships();

            // Initialize PI -> PO (no GRPO) relationships
            $this->initializePIPurchaseOrderRelationships();

            // Initialize PP -> PI relationships
            $this->initializePPRelationships();

            // Initialize DO -> SO relationships
            $this->initializeDORelationships();

            $this->initializeSIRelationships();

            // Initialize SI -> DO relationships (from pivot)
            $this->initializeSIRelationshipsFromDO();

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

        $morphPi = (new PurchaseInvoice)->getMorphClass();

        foreach ($pis as $pi) {
            DocumentRelationship::updateOrCreate([
                'source_document_type' => 'App\Models\GoodsReceiptPO',
                'source_document_id' => $pi->goods_receipt_id,
                'target_document_type' => $morphPi,
                'target_document_id' => $pi->id,
                'relationship_type' => 'target',
            ]);

            DocumentRelationship::updateOrCreate([
                'source_document_type' => 'App\Models\GoodsReceiptPO',
                'source_document_id' => $pi->goods_receipt_id,
                'target_document_type' => $morphPi,
                'target_document_id' => $pi->id,
                'relationship_type' => 'base',
            ]);
        }
    }

    /**
     * Initialize PI -> PO relationships when invoice is tied to PO only (no GRPO).
     */
    private function initializePIPurchaseOrderRelationships(): void
    {
        $pis = DB::table('purchase_invoices')
            ->whereNotNull('purchase_order_id')
            ->whereNull('goods_receipt_id')
            ->get();

        $morphPi = (new PurchaseInvoice)->getMorphClass();

        foreach ($pis as $pi) {
            DocumentRelationship::updateOrCreate([
                'source_document_type' => 'App\Models\PurchaseOrder',
                'source_document_id' => $pi->purchase_order_id,
                'target_document_type' => $morphPi,
                'target_document_id' => $pi->id,
                'relationship_type' => 'target',
            ]);

            DocumentRelationship::updateOrCreate([
                'source_document_type' => 'App\Models\PurchaseOrder',
                'source_document_id' => $pi->purchase_order_id,
                'target_document_type' => $morphPi,
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

        $morphPi = (new PurchaseInvoice)->getMorphClass();
        $morphPp = (new PurchasePayment)->getMorphClass();

        foreach ($allocations as $allocation) {
            DocumentRelationship::updateOrCreate([
                'source_document_type' => $morphPi,
                'source_document_id' => $allocation->invoice_id,
                'target_document_type' => $morphPp,
                'target_document_id' => $allocation->payment_id,
                'relationship_type' => 'target',
            ]);

            DocumentRelationship::updateOrCreate([
                'source_document_type' => $morphPi,
                'source_document_id' => $allocation->invoice_id,
                'target_document_type' => $morphPp,
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
     * Initialize SI -> DO relationships from delivery_order_sales_invoice pivot
     */
    private function initializeSIRelationshipsFromDO(): void
    {
        $pivots = DB::table('delivery_order_sales_invoice')->get();

        foreach ($pivots as $pivot) {
            DocumentRelationship::updateOrCreate([
                'source_document_type' => 'App\Models\DeliveryOrder',
                'source_document_id' => $pivot->delivery_order_id,
                'target_document_type' => 'App\Models\Accounting\SalesInvoice',
                'target_document_id' => $pivot->sales_invoice_id,
                'relationship_type' => 'target',
            ], []);

            DocumentRelationship::updateOrCreate([
                'source_document_type' => 'App\Models\DeliveryOrder',
                'source_document_id' => $pivot->delivery_order_id,
                'target_document_type' => 'App\Models\Accounting\SalesInvoice',
                'target_document_id' => $pivot->sales_invoice_id,
                'relationship_type' => 'base',
            ], []);
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

    /**
     * Use expanded sales-chain graph on the relationship-map API for these document types.
     */
    public function isSalesChainExpansionRoot(Model $document): bool
    {
        return $document instanceof SalesOrder
            || $document instanceof DeliveryOrder
            || $document instanceof SalesInvoice
            || $document instanceof SalesReceipt
            || $document instanceof SalesCreditMemo
            || $document instanceof SalesQuotation;
    }

    /**
     * Build the full sales (and optional trading) document set and directed edges for the Relationship Map modal.
     *
     * @return array{models: array<string, Model>, edges: list<array{from: Model, to: Model}>}
     */
    public function expandSalesRelationshipMapGraph(Model $root, ?User $user = null): array
    {
        $stableKey = static fn (Model $m): string => $m->getMorphClass().':'.$m->getKey();

        $edgeKeys = [];
        $edges = [];
        $addEdge = static function (Model $from, Model $to) use (&$edges, &$edgeKeys, $stableKey): void {
            $k = $stableKey($from).'|'.$stableKey($to);
            if (isset($edgeKeys[$k])) {
                return;
            }
            $edgeKeys[$k] = true;
            $edges[] = ['from' => $from, 'to' => $to];
        };

        $models = [];
        $rk = $stableKey($root);
        $models[$rk] = $root;

        $expanded = [];
        $queue = new \SplQueue;
        $queue->enqueue([$root, 0]);

        while (! $queue->isEmpty()) {
            /** @var Model $doc */
            [$doc, $depth] = $queue->dequeue();
            $sk = $stableKey($doc);
            if (isset($expanded[$sk])) {
                continue;
            }
            $expanded[$sk] = true;

            if ($depth >= self::SALES_MAP_MAX_DEPTH) {
                continue;
            }

            if (! $this->shouldTraverseEdgesFrom($doc)) {
                continue;
            }

            $bases = $this->loadBaseDocumentsDirect($doc, $user);
            foreach ($bases as $parent) {
                if (! $this->canUserViewDocument($parent, $user)) {
                    continue;
                }
                $models[$stableKey($parent)] = $parent;
                $addEdge($parent, $doc);
                $queue->enqueue([$parent, $depth + 1]);
            }

            $targets = $this->loadTargetDocumentsDirect($doc, $user);
            foreach ($targets as $child) {
                if (! $this->canUserViewDocument($child, $user)) {
                    continue;
                }
                $models[$stableKey($child)] = $child;
                $addEdge($doc, $child);
                $queue->enqueue([$child, $depth + 1]);
            }
        }

        $this->enrichSalesRelationshipMapGraph($models, $addEdge, $user);

        return ['models' => $models, 'edges' => $edges];
    }

    private function shouldTraverseEdgesFrom(Model $document): bool
    {
        return $document instanceof SalesOrder
            || $document instanceof DeliveryOrder
            || $document instanceof SalesInvoice
            || $document instanceof SalesReceipt
            || $document instanceof SalesQuotation;
    }

    private function canUserViewDocument(?Model $document, ?User $user): bool
    {
        if (! $document || ! $user) {
            return true;
        }

        $permission = DocumentRelationship::getDocumentPermission($document->getMorphClass());

        return $user->can($permission.'.view');
    }

    /**
     * @param  array<string, Model>  $models
     * @param  callable(Model, Model): void  $addEdge
     */
    private function enrichSalesRelationshipMapGraph(array &$models, callable $addEdge, ?User $user): void
    {
        $stableKey = static fn (Model $m): string => $m->getMorphClass().':'.$m->getKey();

        for ($pass = 0; $pass < 8; $pass++) {
            $beforeCount = count($models);
            foreach (array_values($models) as $model) {
                $this->enrichSalesRelationshipMapGraphForModel($model, $models, $addEdge, $user, $stableKey);
            }
            if (count($models) === $beforeCount) {
                break;
            }
        }
    }

    /**
     * @param  array<string, Model>  $models
     * @param  callable(Model, Model): void  $addEdge
     * @param  callable(Model): string  $stableKey
     */
    private function enrichSalesRelationshipMapGraphForModel(
        Model $model,
        array &$models,
        callable $addEdge,
        ?User $user,
        callable $stableKey
    ): void {
        if ($model instanceof DeliveryOrder) {
            if ($model->sales_order_id) {
                $so = SalesOrder::query()->find($model->sales_order_id);
                if ($so && $this->canUserViewDocument($so, $user)) {
                    $models[$stableKey($so)] = $so;
                    $addEdge($so, $model);
                }
            }

            $model->loadMissing(['salesInvoices']);
            foreach ($model->salesInvoices as $invoice) {
                if ($this->canUserViewDocument($invoice, $user)) {
                    $models[$stableKey($invoice)] = $invoice;
                    $addEdge($model, $invoice);
                }
            }
        }

        if ($model instanceof SalesOrder) {
            $quotations = SalesQuotation::query()
                ->where('converted_to_sales_order_id', $model->id)
                ->get();
            foreach ($quotations as $sq) {
                if ($this->canUserViewDocument($sq, $user)) {
                    $models[$stableKey($sq)] = $sq;
                    $addEdge($sq, $model);
                }
            }
        }

        if ($model instanceof SalesQuotation && $model->converted_to_sales_order_id) {
            $so = SalesOrder::query()->find($model->converted_to_sales_order_id);
            if ($so && $this->canUserViewDocument($so, $user)) {
                $models[$stableKey($so)] = $so;
                $addEdge($model, $so);
            }
        }

        if ($model instanceof SalesInvoice) {
            if ($model->sales_order_id) {
                $so = SalesOrder::query()->find($model->sales_order_id);
                if ($so && $this->canUserViewDocument($so, $user)) {
                    $models[$stableKey($so)] = $so;
                    $addEdge($so, $model);
                }
            }

            $model->loadMissing(['deliveryOrders', 'creditMemo']);

            foreach ($model->deliveryOrders as $do) {
                if ($this->canUserViewDocument($do, $user)) {
                    $models[$stableKey($do)] = $do;
                    $addEdge($do, $model);
                }
            }

            $memo = $model->creditMemo;
            if ($memo && $this->canUserViewDocument($memo, $user)) {
                $models[$stableKey($memo)] = $memo;
                $addEdge($model, $memo);
            }

            $receiptIds = DB::table('sales_receipt_allocations')
                ->where('invoice_id', $model->id)
                ->pluck('receipt_id');
            foreach ($receiptIds as $rid) {
                $sr = SalesReceipt::query()->find($rid);
                if ($sr && $this->canUserViewDocument($sr, $user)) {
                    $models[$stableKey($sr)] = $sr;
                    $addEdge($model, $sr);
                }
            }

            $grpoIds = DB::table('sales_invoice_grpo_combinations')
                ->where('sales_invoice_id', $model->id)
                ->pluck('goods_receipt_id');
            foreach ($grpoIds as $grpoId) {
                $grpo = GoodsReceiptPO::query()->find($grpoId);
                if ($grpo && $this->canUserViewDocument($grpo, $user)) {
                    $models[$stableKey($grpo)] = $grpo;
                    $addEdge($grpo, $model);
                    if ($grpo->purchase_order_id) {
                        $po = PurchaseOrder::query()->find($grpo->purchase_order_id);
                        if ($po && $this->canUserViewDocument($po, $user)) {
                            $models[$stableKey($po)] = $po;
                            $addEdge($po, $grpo);
                        }
                    }
                }
            }
        }

        if ($model instanceof SalesCreditMemo && $model->sales_invoice_id) {
            $si = SalesInvoice::query()->find($model->sales_invoice_id);
            if ($si && $this->canUserViewDocument($si, $user)) {
                $models[$stableKey($si)] = $si;
                $addEdge($si, $model);
            }
        }
    }

    private function loadBaseDocumentsDirect(Model $document, ?User $user): Collection
    {
        $relationships = DocumentRelationship::query()
            ->where('target_document_type', $document->getMorphClass())
            ->where('target_document_id', $document->id)
            ->where('relationship_type', 'base')
            ->with(['sourceDocument'])
            ->get();

        $baseDocuments = $relationships->map(fn ($relationship) => $relationship->sourceDocument)->filter();
        if ($user) {
            $baseDocuments = $this->filterByUserPermissions($baseDocuments, $user);
        }

        return $baseDocuments;
    }

    private function loadTargetDocumentsDirect(Model $document, ?User $user): Collection
    {
        $relationships = DocumentRelationship::query()
            ->where('source_document_type', $document->getMorphClass())
            ->where('source_document_id', $document->id)
            ->whereIn('relationship_type', ['base', 'target'])
            ->with(['targetDocument'])
            ->get();

        $targetDocuments = $relationships->map(fn ($relationship) => $relationship->targetDocument)->filter();
        $targetDocuments = $targetDocuments->unique(fn ($doc) => $doc->getMorphClass().'-'.$doc->getKey())->values();

        if ($user) {
            $targetDocuments = $this->filterByUserPermissions($targetDocuments, $user);
        }

        return $targetDocuments;
    }
}
