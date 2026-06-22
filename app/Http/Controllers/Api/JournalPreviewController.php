<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Accounting\JournalBuilders\DeliveryOrderJournalBuilder;
use App\Services\Accounting\JournalBuilders\GrpoJournalBuilder;
use App\Services\Accounting\JournalBuilders\JournalPreviewPresenter;
use App\Services\Accounting\JournalBuilders\PurchaseInvoiceJournalBuilder;
use App\Services\Accounting\JournalBuilders\PurchasePaymentJournalBuilder;
use App\Services\Accounting\JournalBuilders\SalesInvoiceJournalBuilder;
use App\Services\Accounting\JournalBuilders\SalesReceiptJournalBuilder;
use App\Services\GRPOJournalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JournalPreviewController extends Controller
{
    public function __construct(
        private GRPOJournalService $grpoJournalService,
        private GrpoJournalBuilder $grpoJournalBuilder,
        private PurchaseInvoiceJournalBuilder $purchaseInvoiceJournalBuilder,
        private PurchasePaymentJournalBuilder $purchasePaymentJournalBuilder,
        private DeliveryOrderJournalBuilder $deliveryOrderJournalBuilder,
        private SalesInvoiceJournalBuilder $salesInvoiceJournalBuilder,
        private SalesReceiptJournalBuilder $salesReceiptJournalBuilder,
        private JournalPreviewPresenter $journalPreviewPresenter,
    ) {}

    /**
     * Get journal preview for GRPO from form data (before saving)
     */
    public function grpoPreview(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'date' => 'required|date',
                'business_partner_id' => 'required|exists:business_partners,id',
                'warehouse_id' => 'required|exists:warehouses,id',
                'lines' => 'required|array|min:1',
                'lines.*.item_id' => 'required|exists:inventory_items,id',
                'lines.*.qty' => 'required|numeric|min:0.01',
            ]);

            $totalAmount = 0;
            $lines = [];

            foreach ($request->lines as $lineData) {
                $item = \App\Models\InventoryItem::find($lineData['item_id']);
                $unitPrice = $item->unit_price ?? 0;
                $amount = (float) $lineData['qty'] * $unitPrice;
                $totalAmount += $amount;

                $lines[] = [
                    'item_id' => $lineData['item_id'],
                    'description' => $lineData['description'] ?? $item->name,
                    'qty' => (float) $lineData['qty'],
                    'unit_price' => $unitPrice,
                    'amount' => $amount,
                ];
            }

            if ($totalAmount <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Total amount must be greater than zero',
                ], 400);
            }

            $inventoryAccountId = (int) (DB::table('accounts')->where('code', '1.1.3.01')->value('id') ?? 1);
            $liabilityAccountId = (int) (DB::table('accounts')->where('code', '2.1.1.03')->value('id') ?? 2);
            $inventoryAccount = DB::table('accounts')->where('id', $inventoryAccountId)->first();
            $liabilityAccount = DB::table('accounts')->where('id', $liabilityAccountId)->first();

            $journalPreview = [
                'journal_number' => 'Auto-generated',
                'date' => \Carbon\Carbon::parse($request->date)->format('d F Y'),
                'description' => 'GRPO Receipt - [GRN Number]',
                'lines' => [
                    [
                        'account_code' => $inventoryAccount->code ?? '1.1.3.01',
                        'account_name' => $inventoryAccount->name ?? 'Persediaan Barang Dagangan',
                        'description' => 'Inventory - '.$lines[0]['description'],
                        'debit' => $totalAmount,
                        'credit' => null,
                    ],
                    [
                        'account_code' => $liabilityAccount->code ?? '2.1.1.03',
                        'account_name' => $liabilityAccount->name ?? 'AP UnInvoice',
                        'description' => 'AP UnInvoice - GRPO',
                        'debit' => null,
                        'credit' => $totalAmount,
                    ],
                ],
                'total_debit' => $totalAmount,
                'total_credit' => $totalAmount,
                'is_balanced' => true,
            ];

            return response()->json([
                'success' => true,
                ...$journalPreview,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get journal preview for a document action
     */
    public function getJournalPreview(Request $request, string $documentType, int $documentId): JsonResponse
    {
        try {
            $actionType = $request->input('action_type', 'post');

            $document = $this->getDocumentModel($documentType, $documentId);

            if (! $document) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document not found',
                ], 404);
            }

            $journalPreview = $this->generateJournalPreview($document, $actionType);

            return response()->json([
                'success' => true,
                'data' => $journalPreview,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating journal preview: '.$e->getMessage(),
            ], 500);
        }
    }

    private function generateJournalPreview($document, string $actionType): array
    {
        if ($actionType !== 'post') {
            throw new \Exception("Action type '{$actionType}' is not supported for journal preview");
        }

        $draft = match ($document->getMorphClass()) {
            'App\Models\GoodsReceiptPO' => $this->grpoJournalBuilder->build($document),
            'App\Models\Accounting\PurchaseInvoice' => $this->purchaseInvoiceJournalBuilder->build($document),
            'App\Models\Accounting\PurchasePayment' => $this->purchasePaymentJournalBuilder->build($document),
            'App\Models\DeliveryOrder' => $this->deliveryOrderJournalBuilder->buildRevenueRecognition($document),
            'App\Models\Accounting\SalesInvoice' => $this->salesInvoiceJournalBuilder->build($document),
            'App\Models\Accounting\SalesReceipt' => $this->salesReceiptJournalBuilder->build($document),
            default => throw new \Exception("Journal preview not supported for document type: {$document->getMorphClass()}"),
        };

        return $this->journalPreviewPresenter->present($draft);
    }

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

        if (! $modelClass) {
            return null;
        }

        return $modelClass::find($documentId);
    }
}
