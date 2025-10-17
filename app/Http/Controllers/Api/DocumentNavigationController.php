<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DocumentRelationshipService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DocumentNavigationController extends Controller
{
    protected DocumentRelationshipService $relationshipService;

    public function __construct(DocumentRelationshipService $relationshipService)
    {
        $this->relationshipService = $relationshipService;
    }

    /**
     * Get navigation data for a document
     */
    public function getNavigationData(Request $request, string $documentType, int $documentId): JsonResponse
    {
        try {
            // Get the document model
            $document = $this->getDocumentModel($documentType, $documentId);

            if (!$document) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document not found'
                ], 404);
            }

            // Get user for permission filtering
            $user = $request->user();

            // Get navigation data
            $navigationData = $this->relationshipService->getNavigationData($document, $user);

            return response()->json([
                'success' => true,
                'data' => $navigationData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving navigation data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get base documents for a document
     */
    public function getBaseDocuments(Request $request, string $documentType, int $documentId): JsonResponse
    {
        try {
            $document = $this->getDocumentModel($documentType, $documentId);

            if (!$document) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document not found'
                ], 404);
            }

            $user = $request->user();

            $baseDocuments = $this->relationshipService->getBaseDocuments($document, $user);
            $buttonState = $this->relationshipService->getButtonState($baseDocuments);

            return response()->json([
                'success' => true,
                'data' => [
                    'documents' => $baseDocuments->map(function ($doc) {
                        return [
                            'id' => $doc->id,
                            'type' => $doc->getMorphClass(),
                            'number' => $doc->document_number ?? $doc->order_no ?? $doc->invoice_no ?? $doc->payment_no ?? $doc->receipt_no ?? 'N/A',
                            'status' => $doc->status ?? 'N/A',
                            'amount' => $doc->total_amount ?? $doc->amount ?? 0,
                            'date' => $doc->date ?? $doc->created_at,
                            'url' => $this->getDocumentUrl($doc),
                        ];
                    }),
                    'state' => $buttonState,
                    'count' => $baseDocuments->count(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving base documents: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get target documents for a document
     */
    public function getTargetDocuments(Request $request, string $documentType, int $documentId): JsonResponse
    {
        try {
            $document = $this->getDocumentModel($documentType, $documentId);

            if (!$document) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document not found'
                ], 404);
            }

            $user = $request->user();

            $targetDocuments = $this->relationshipService->getTargetDocuments($document, $user);
            $buttonState = $this->relationshipService->getButtonState($targetDocuments);

            return response()->json([
                'success' => true,
                'data' => [
                    'documents' => $targetDocuments->map(function ($doc) {
                        return [
                            'id' => $doc->id,
                            'type' => $doc->getMorphClass(),
                            'number' => $doc->document_number ?? $doc->order_no ?? $doc->invoice_no ?? $doc->payment_no ?? $doc->receipt_no ?? 'N/A',
                            'status' => $doc->status ?? 'N/A',
                            'amount' => $doc->total_amount ?? $doc->amount ?? 0,
                            'date' => $doc->date ?? $doc->created_at,
                            'url' => $this->getDocumentUrl($doc),
                        ];
                    }),
                    'state' => $buttonState,
                    'count' => $targetDocuments->count(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving target documents: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get document model by type and ID
     */
    private function getDocumentModel(string $documentType, int $documentId)
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

        $modelClass = $modelMap[$documentType] ?? null;

        if (!$modelClass) {
            return null;
        }

        return $modelClass::find($documentId);
    }

    /**
     * Check if user can access document
     */
    private function userCanAccessDocument($user, $document): bool
    {
        if (!$user) {
            return false;
        }

        $permission = \App\Models\DocumentRelationship::getDocumentPermission($document->getMorphClass());
        return $user->can($permission . '.view');
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
}
