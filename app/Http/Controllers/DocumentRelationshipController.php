<?php

namespace App\Http\Controllers;

use App\Models\DocumentRelationship;
use App\Services\DocumentRelationshipService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class DocumentRelationshipController extends Controller
{
    protected $relationshipService;

    public function __construct(DocumentRelationshipService $relationshipService)
    {
        $this->relationshipService = $relationshipService;
    }

    /**
     * Get relationship map data for a document
     */
    public function getRelationshipMap(Request $request, string $documentType, int $documentId): JsonResponse
    {
        try {
            // Get the document model
            $document = $this->getDocumentModel($documentType, $documentId);

            if (!$document) {
                return response()->json(['error' => 'Document not found'], 404);
            }

            // Debug: Check document morph class
            $morphClass = $document->getMorphClass();

            // Debug: Check raw relationships
            $rawRelationships = \App\Models\DocumentRelationship::where('source_document_type', $morphClass)
                ->where('source_document_id', $document->id)
                ->orWhere('target_document_type', $morphClass)
                ->where('target_document_id', $document->id)
                ->get();

            // Get navigation data
            $navigationData = $this->relationshipService->getNavigationData($document, Auth::user());

            // Generate Mermaid diagram data
            $mermaidData = $this->generateMermaidDiagram($document, $navigationData);

            return response()->json([
                'success' => true,
                'debug' => [
                    'morph_class' => $morphClass,
                    'raw_relationships_count' => $rawRelationships->count(),
                    'raw_relationships' => $rawRelationships->toArray()
                ],
                'document' => [
                    'id' => $document->id,
                    'type' => $documentType,
                    'number' => $this->getDocumentNumber($document),
                    'status' => $document->status ?? 'N/A',
                    'amount' => $document->total_amount ?? $document->amount ?? 0,
                    'date' => $document->date ?? $document->created_at,
                ],
                'relationships' => $navigationData,
                'mermaid' => $mermaidData,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to load relationship map: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get document model instance
     */
    private function getDocumentModel(string $documentType, int $documentId)
    {
        $modelMap = [
            'purchase-orders' => \App\Models\PurchaseOrder::class,
            'goods-receipt-pos' => \App\Models\GoodsReceiptPO::class,
            'purchase-invoices' => \App\Models\Accounting\PurchaseInvoice::class,
            'purchase-payments' => \App\Models\Accounting\PurchasePayment::class,
            'sales-orders' => \App\Models\SalesOrder::class,
            'delivery-orders' => \App\Models\DeliveryOrder::class,
            'sales-invoices' => \App\Models\Accounting\SalesInvoice::class,
            'sales-receipts' => \App\Models\Accounting\SalesReceipt::class,
        ];

        $modelClass = $modelMap[$documentType] ?? null;

        if (!$modelClass) {
            return null;
        }

        return $modelClass::find($documentId);
    }

    /**
     * Get document number for display
     */
    private function getDocumentNumber($document): string
    {
        return $document->order_no ??
            $document->grn_no ??
            $document->invoice_no ??
            $document->payment_no ??
            $document->receipt_no ??
            $document->do_number ??
            '#' . $document->id;
    }

    /**
     * Generate Mermaid diagram data
     */
    private function generateMermaidDiagram($document, array $navigationData): array
    {
        $nodes = [];
        $edges = [];
        $currentDocumentId = 'doc_' . $document->id;

        // Add current document node with detailed information
        $nodes[] = [
            'id' => $currentDocumentId,
            'label' => $this->getDocumentNumber($document),
            'type' => $this->getDocumentTypeLabel($document),
            'status' => $document->status ?? 'N/A',
            'amount' => $document->total_amount ?? $document->amount ?? 0,
            'date' => $this->formatDate($document->date ?? $document->created_at),
            'reference' => $this->getDocumentReference($document),
            'isCurrent' => true,
        ];

        // Add base documents (parents) with detailed information
        foreach ($navigationData['base_documents']['documents'] as $baseDoc) {
            $nodeId = 'doc_' . $baseDoc['id'];
            $baseDocumentModel = $this->getDocumentModelById($baseDoc['type'], $baseDoc['id']);

            $nodes[] = [
                'id' => $nodeId,
                'label' => $baseDoc['number'],
                'type' => $this->getDocumentTypeLabelFromString($baseDoc['type']),
                'status' => $baseDoc['status'],
                'amount' => $baseDoc['amount'],
                'date' => $baseDocumentModel ? $this->formatDate($baseDocumentModel->date ?? $baseDocumentModel->created_at) : 'N/A',
                'reference' => $baseDocumentModel ? $this->getDocumentReference($baseDocumentModel) : 'N/A',
                'isCurrent' => false,
                'url' => $baseDoc['url'],
            ];

            // Add edge from base to current with relationship type
            $edges[] = [
                'from' => $nodeId,
                'to' => $currentDocumentId,
                'label' => $this->getRelationshipLabel($baseDoc['type'], $this->getDocumentTypeLabel($document)),
                'type' => 'direct',
            ];
        }

        // Add target documents (children) with detailed information
        foreach ($navigationData['target_documents']['documents'] as $targetDoc) {
            $nodeId = 'doc_' . $targetDoc['id'];
            $targetDocumentModel = $this->getDocumentModelById($targetDoc['type'], $targetDoc['id']);

            $nodes[] = [
                'id' => $nodeId,
                'label' => $targetDoc['number'],
                'type' => $this->getDocumentTypeLabelFromString($targetDoc['type']),
                'status' => $targetDoc['status'],
                'amount' => $targetDoc['amount'],
                'date' => $targetDocumentModel ? $this->formatDate($targetDocumentModel->date ?? $targetDocumentModel->created_at) : 'N/A',
                'reference' => $targetDocumentModel ? $this->getDocumentReference($targetDocumentModel) : 'N/A',
                'isCurrent' => false,
                'url' => $targetDoc['url'],
            ];

            // Add edge from current to target with relationship type
            $edges[] = [
                'from' => $currentDocumentId,
                'to' => $nodeId,
                'label' => $this->getRelationshipLabel($this->getDocumentTypeLabel($document), $targetDoc['type']),
                'type' => 'direct',
            ];
        }

        // Add cross-relationships (e.g., PO directly to PI)
        $this->addCrossRelationships($nodes, $edges);

        return [
            'nodes' => $nodes,
            'edges' => $edges,
            'layout' => $this->determineLayout($nodes),
        ];
    }

    /**
     * Get document model by ID and type
     */
    private function getDocumentModelById(string $type, int $id)
    {
        $modelMap = [
            'App\\Models\\PurchaseOrder' => \App\Models\PurchaseOrder::class,
            'App\\Models\\GoodsReceiptPO' => \App\Models\GoodsReceiptPO::class,
            'App\\Models\\Accounting\\PurchaseInvoice' => \App\Models\Accounting\PurchaseInvoice::class,
            'App\\Models\\Accounting\\PurchasePayment' => \App\Models\Accounting\PurchasePayment::class,
            'App\\Models\\SalesOrder' => \App\Models\SalesOrder::class,
            'App\\Models\\DeliveryOrder' => \App\Models\DeliveryOrder::class,
            'App\\Models\\Accounting\\SalesInvoice' => \App\Models\Accounting\SalesInvoice::class,
            'App\\Models\\Accounting\\SalesReceipt' => \App\Models\Accounting\SalesReceipt::class,
        ];

        $modelClass = $modelMap[$type] ?? null;
        return $modelClass ? $modelClass::find($id) : null;
    }

    /**
     * Format date for display
     */
    private function formatDate($date): string
    {
        if (!$date) return 'N/A';

        if (is_string($date)) {
            $date = \Carbon\Carbon::parse($date);
        }

        return $date->format('d.m.Y');
    }

    /**
     * Get document reference
     */
    private function getDocumentReference($document): string
    {
        return $document->reference ??
            $document->vendor_reference ??
            $document->customer_reference ??
            $document->invoice_reference ??
            'N/A';
    }

    /**
     * Get relationship label
     */
    private function getRelationshipLabel(string $fromType, string $toType): string
    {
        $relationships = [
            'Purchase Order' => [
                'Goods Receipt PO' => 'receives',
                'Purchase Invoice' => 'invoices',
                'Purchase Payment' => 'pays'
            ],
            'Goods Receipt PO' => [
                'Purchase Invoice' => 'invoices',
                'Purchase Payment' => 'pays'
            ],
            'Purchase Invoice' => [
                'Purchase Payment' => 'pays'
            ],
            'Sales Order' => [
                'Delivery Order' => 'delivers',
                'Sales Invoice' => 'invoices',
                'Sales Receipt' => 'receives'
            ],
            'Delivery Order' => [
                'Sales Invoice' => 'invoices',
                'Sales Receipt' => 'receives'
            ],
            'Sales Invoice' => [
                'Sales Receipt' => 'receives'
            ]
        ];

        return $relationships[$fromType][$toType] ?? 'relates to';
    }

    /**
     * Add cross-relationships (e.g., PO directly to PI)
     */
    private function addCrossRelationships(array &$nodes, array &$edges): void
    {
        $nodeMap = [];
        foreach ($nodes as $node) {
            $nodeMap[$node['id']] = $node;
        }

        // Add direct relationships (e.g., PO to PI bypassing GRPO)
        foreach ($nodes as $node) {
            if ($node['type'] === 'Purchase Order') {
                foreach ($nodes as $targetNode) {
                    if ($targetNode['type'] === 'Purchase Invoice' && $targetNode['id'] !== $node['id']) {
                        // Check if there's already a path through GRPO
                        $hasGRPOPath = false;
                        foreach ($edges as $edge) {
                            if ($edge['from'] === $node['id'] && $nodeMap[$edge['to']]['type'] === 'Goods Receipt PO') {
                                foreach ($edges as $edge2) {
                                    if ($edge2['from'] === $edge['to'] && $edge2['to'] === $targetNode['id']) {
                                        $hasGRPOPath = true;
                                        break 2;
                                    }
                                }
                            }
                        }

                        // Add direct relationship if no GRPO path exists
                        if (!$hasGRPOPath) {
                            $edges[] = [
                                'from' => $node['id'],
                                'to' => $targetNode['id'],
                                'label' => 'direct invoice',
                                'type' => 'parallel',
                            ];
                        }
                    }
                }
            }
        }
    }

    /**
     * Get document type label for display
     */
    private function getDocumentTypeLabel($document): string
    {
        $class = get_class($document);

        $labels = [
            \App\Models\PurchaseOrder::class => 'Purchase Order',
            \App\Models\GoodsReceiptPO::class => 'Goods Receipt PO',
            \App\Models\Accounting\PurchaseInvoice::class => 'Purchase Invoice',
            \App\Models\Accounting\PurchasePayment::class => 'Purchase Payment',
            \App\Models\SalesOrder::class => 'Sales Order',
            \App\Models\DeliveryOrder::class => 'Delivery Order',
            \App\Models\Accounting\SalesInvoice::class => 'Sales Invoice',
            \App\Models\Accounting\SalesReceipt::class => 'Sales Receipt',
        ];

        return $labels[$class] ?? 'Document';
    }

    /**
     * Get document type label from string
     */
    private function getDocumentTypeLabelFromString(string $type): string
    {
        $labels = [
            'App\\Models\\PurchaseOrder' => 'Purchase Order',
            'App\\Models\\GoodsReceiptPO' => 'Goods Receipt PO',
            'App\\Models\\Accounting\\PurchaseInvoice' => 'Purchase Invoice',
            'App\\Models\\Accounting\\PurchasePayment' => 'Purchase Payment',
            'App\\Models\\SalesOrder' => 'Sales Order',
            'App\\Models\\DeliveryOrder' => 'Delivery Order',
            'App\\Models\\Accounting\\SalesInvoice' => 'Sales Invoice',
            'App\\Models\\Accounting\\SalesReceipt' => 'Sales Receipt',
        ];

        return $labels[$type] ?? 'Document';
    }

    /**
     * Determine layout based on number of nodes
     */
    private function determineLayout(array $nodes): string
    {
        $nodeCount = count($nodes);

        if ($nodeCount <= 3) {
            return 'horizontal';
        } elseif ($nodeCount <= 6) {
            return 'vertical';
        } else {
            return 'complex';
        }
    }
}
