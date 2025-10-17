<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GRPOJournalService;
use App\Services\Accounting\PostingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class JournalPreviewController extends Controller
{
    protected GRPOJournalService $grpoJournalService;
    protected PostingService $postingService;

    public function __construct(GRPOJournalService $grpoJournalService, PostingService $postingService)
    {
        $this->grpoJournalService = $grpoJournalService;
        $this->postingService = $postingService;
    }

    /**
     * Get journal preview for GRPO from form data (before saving)
     */
    public function grpoPreview(Request $request): JsonResponse
    {
        try {
            // Validate required fields
            $request->validate([
                'date' => 'required|date',
                'business_partner_id' => 'required|exists:business_partners,id',
                'warehouse_id' => 'required|exists:warehouses,id',
                'lines' => 'required|array|min:1',
                'lines.*.item_id' => 'required|exists:inventory_items,id',
                'lines.*.qty' => 'required|numeric|min:0.01',
            ]);

            // Calculate total amount from lines
            $totalAmount = 0;
            $lines = [];

            foreach ($request->lines as $lineData) {
                $item = \App\Models\InventoryItem::find($lineData['item_id']);
                $unitPrice = $item->unit_price ?? 0;
                $amount = (float)$lineData['qty'] * $unitPrice;
                $totalAmount += $amount;

                $lines[] = [
                    'item_id' => $lineData['item_id'],
                    'description' => $lineData['description'] ?? $item->name,
                    'qty' => (float)$lineData['qty'],
                    'unit_price' => $unitPrice,
                    'amount' => $amount,
                ];
            }

            if ($totalAmount <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Total amount must be greater than zero'
                ], 400);
            }

            // Get account mappings (same logic as GRPOJournalService)
            $inventoryAccountId = $this->getInventoryAccountFromWarehouse($request->warehouse_id);
            $liabilityAccountId = $this->getLiabilityAccountFromPartner($request->business_partner_id);

            // Create journal preview data
            $journalPreview = [
                'journal_number' => 'Auto-generated',
                'date' => \Carbon\Carbon::parse($request->date)->format('d F Y'),
                'description' => 'GRPO Receipt - [GRN Number]',
                'lines' => [
                    [
                        'account_code' => \App\Models\Accounting\Account::find($inventoryAccountId)->code ?? '1.1.3.01',
                        'account_name' => \App\Models\Accounting\Account::find($inventoryAccountId)->name ?? 'Persediaan Barang Dagangan',
                        'description' => 'Inventory - ' . $lines[0]['description'],
                        'debit' => $totalAmount,
                        'credit' => null,
                    ],
                    [
                        'account_code' => \App\Models\Accounting\Account::find($liabilityAccountId)->code ?? '2.1.1.03',
                        'account_name' => \App\Models\Accounting\Account::find($liabilityAccountId)->name ?? 'AP UnInvoice',
                        'description' => 'AP UnInvoice - GRPO',
                        'debit' => null,
                        'credit' => $totalAmount,
                    ]
                ],
                'total_debit' => $totalAmount,
                'total_credit' => $totalAmount,
                'is_balanced' => true,
            ];

            return response()->json([
                'success' => true,
                ...$journalPreview
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
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

            // Generate journal preview based on document type and action
            $journalPreview = $this->generateJournalPreview($document, $actionType);

            return response()->json([
                'success' => true,
                'data' => $journalPreview
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating journal preview: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate journal preview for a document
     */
    private function generateJournalPreview($document, string $actionType): array
    {
        $documentType = $document->getMorphClass();

        switch ($documentType) {
            case 'App\Models\GoodsReceiptPO':
                return $this->generateGRPOJournalPreview($document, $actionType);

            case 'App\Models\Accounting\PurchaseInvoice':
                return $this->generatePIJournalPreview($document, $actionType);

            case 'App\Models\Accounting\PurchasePayment':
                return $this->generatePPJournalPreview($document, $actionType);

            case 'App\Models\DeliveryOrder':
                return $this->generateDOJournalPreview($document, $actionType);

            case 'App\Models\Accounting\SalesInvoice':
                return $this->generateSIJournalPreview($document, $actionType);

            case 'App\Models\Accounting\SalesReceipt':
                return $this->generateSRJournalPreview($document, $actionType);

            default:
                throw new \Exception("Journal preview not supported for document type: {$documentType}");
        }
    }

    /**
     * Generate GRPO journal preview
     */
    private function generateGRPOJournalPreview($grpo, string $actionType): array
    {
        if ($actionType !== 'post') {
            throw new \Exception("Action type '{$actionType}' not supported for GRPO");
        }

        // Load relationships
        $grpo->load(['lines.item.category', 'businessPartner']);

        $totalAmount = $grpo->lines->sum('amount');

        $journalLines = [];

        // Debit inventory accounts
        foreach ($grpo->lines as $line) {
            $inventoryAccount = $this->getInventoryAccount($line->item);
            $journalLines[] = [
                'account_id' => $inventoryAccount['id'],
                'account_code' => $inventoryAccount['code'],
                'account_name' => $inventoryAccount['name'],
                'debit' => $line->amount,
                'credit' => 0,
                'memo' => "Inventory - {$line->item->name}",
            ];
        }

        // Credit AP UnInvoice
        $apAccount = $this->getAPUnInvoiceAccount();
        $journalLines[] = [
            'account_id' => $apAccount['id'],
            'account_code' => $apAccount['code'],
            'account_name' => $apAccount['name'],
            'debit' => 0,
            'credit' => $totalAmount,
            'memo' => "AP UnInvoice - GRPO {$grpo->grpo_number}",
        ];

        return [
            'journal_number' => 'Auto-generated',
            'date' => now()->format('Y-m-d'),
            'description' => "GRPO Receipt - {$grpo->grpo_number}",
            'lines' => $journalLines,
        ];
    }

    /**
     * Generate Purchase Invoice journal preview
     */
    private function generatePIJournalPreview($pi, string $actionType): array
    {
        if ($actionType !== 'post') {
            throw new \Exception("Action type '{$actionType}' not supported for Purchase Invoice");
        }

        $totalAmount = $pi->total_amount;

        $journalLines = [];

        // Debit AP UnInvoice (reducing un-invoiced liability)
        $apUnInvoiceAccount = $this->getAPUnInvoiceAccount();
        $journalLines[] = [
            'account_id' => $apUnInvoiceAccount['id'],
            'account_code' => $apUnInvoiceAccount['code'],
            'account_name' => $apUnInvoiceAccount['name'],
            'debit' => $totalAmount,
            'credit' => 0,
            'memo' => 'Reduce AP UnInvoice',
        ];

        // Credit Utang Dagang (creating proper liability)
        $apAccount = $this->getAPAccount();
        $journalLines[] = [
            'account_id' => $apAccount['id'],
            'account_code' => $apAccount['code'],
            'account_name' => $apAccount['name'],
            'debit' => 0,
            'credit' => $totalAmount,
            'memo' => 'Accounts Payable',
        ];

        return [
            'journal_number' => 'Auto-generated',
            'date' => now()->format('Y-m-d'),
            'description' => "Purchase Invoice - {$pi->invoice_no}",
            'lines' => $journalLines,
        ];
    }

    /**
     * Generate Purchase Payment journal preview
     */
    private function generatePPJournalPreview($pp, string $actionType): array
    {
        if ($actionType !== 'post') {
            throw new \Exception("Action type '{$actionType}' not supported for Purchase Payment");
        }

        $totalAmount = $pp->total_amount;

        $journalLines = [];

        // Debit Utang Dagang
        $apAccount = $this->getAPAccount();
        $journalLines[] = [
            'account_id' => $apAccount['id'],
            'account_code' => $apAccount['code'],
            'account_name' => $apAccount['name'],
            'debit' => $totalAmount,
            'credit' => 0,
            'memo' => 'Reduce Accounts Payable',
        ];

        // Credit Cash
        $cashAccount = $this->getCashAccount();
        $journalLines[] = [
            'account_id' => $cashAccount['id'],
            'account_code' => $cashAccount['code'],
            'account_name' => $cashAccount['name'],
            'debit' => 0,
            'credit' => $totalAmount,
            'memo' => 'Cash Payment',
        ];

        return [
            'journal_number' => 'Auto-generated',
            'date' => now()->format('Y-m-d'),
            'description' => "Purchase Payment - {$pp->payment_no}",
            'lines' => $journalLines,
        ];
    }

    /**
     * Generate Delivery Order journal preview
     */
    private function generateDOJournalPreview($do, string $actionType): array
    {
        if ($actionType !== 'post') {
            throw new \Exception("Action type '{$actionType}' not supported for Delivery Order");
        }

        $totalAmount = $do->lines->sum('amount');

        $journalLines = [];

        // Debit AR UnInvoice
        $arUnInvoiceAccount = $this->getARUnInvoiceAccount();
        $journalLines[] = [
            'account_id' => $arUnInvoiceAccount['id'],
            'account_code' => $arUnInvoiceAccount['code'],
            'account_name' => $arUnInvoiceAccount['name'],
            'debit' => $totalAmount,
            'credit' => 0,
            'memo' => "AR UnInvoice - DO {$do->do_number}",
        ];

        // Credit Revenue
        $revenueAccount = $this->getRevenueAccount();
        $journalLines[] = [
            'account_id' => $revenueAccount['id'],
            'account_code' => $revenueAccount['code'],
            'account_name' => $revenueAccount['name'],
            'debit' => 0,
            'credit' => $totalAmount,
            'memo' => 'Revenue Recognition',
        ];

        return [
            'journal_number' => 'Auto-generated',
            'date' => now()->format('Y-m-d'),
            'description' => "Delivery Order - {$do->do_number}",
            'lines' => $journalLines,
        ];
    }

    /**
     * Generate Sales Invoice journal preview
     */
    private function generateSIJournalPreview($si, string $actionType): array
    {
        if ($actionType !== 'post') {
            throw new \Exception("Action type '{$actionType}' not supported for Sales Invoice");
        }

        $totalAmount = $si->total_amount;

        $journalLines = [];

        // Debit AR UnInvoice (reducing un-invoiced receivable)
        $arUnInvoiceAccount = $this->getARUnInvoiceAccount();
        $journalLines[] = [
            'account_id' => $arUnInvoiceAccount['id'],
            'account_code' => $arUnInvoiceAccount['code'],
            'account_name' => $arUnInvoiceAccount['name'],
            'debit' => $totalAmount,
            'credit' => 0,
            'memo' => 'Reduce AR UnInvoice',
        ];

        // Credit Piutang Dagang (creating proper receivable)
        $arAccount = $this->getARAccount();
        $journalLines[] = [
            'account_id' => $arAccount['id'],
            'account_code' => $arAccount['code'],
            'account_name' => $arAccount['name'],
            'debit' => 0,
            'credit' => $totalAmount,
            'memo' => 'Accounts Receivable',
        ];

        return [
            'journal_number' => 'Auto-generated',
            'date' => now()->format('Y-m-d'),
            'description' => "Sales Invoice - {$si->invoice_no}",
            'lines' => $journalLines,
        ];
    }

    /**
     * Generate Sales Receipt journal preview
     */
    private function generateSRJournalPreview($sr, string $actionType): array
    {
        if ($actionType !== 'post') {
            throw new \Exception("Action type '{$actionType}' not supported for Sales Receipt");
        }

        $totalAmount = $sr->total_amount;

        $journalLines = [];

        // Debit Cash
        $cashAccount = $this->getCashAccount();
        $journalLines[] = [
            'account_id' => $cashAccount['id'],
            'account_code' => $cashAccount['code'],
            'account_name' => $cashAccount['name'],
            'debit' => $totalAmount,
            'credit' => 0,
            'memo' => 'Cash Receipt',
        ];

        // Credit Piutang Dagang
        $arAccount = $this->getARAccount();
        $journalLines[] = [
            'account_id' => $arAccount['id'],
            'account_code' => $arAccount['code'],
            'account_name' => $arAccount['name'],
            'debit' => 0,
            'credit' => $totalAmount,
            'memo' => 'Reduce Accounts Receivable',
        ];

        return [
            'journal_number' => 'Auto-generated',
            'date' => now()->format('Y-m-d'),
            'description' => "Sales Receipt - {$sr->receipt_no}",
            'lines' => $journalLines,
        ];
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
     * Get inventory account from warehouse
     */
    private function getInventoryAccountFromWarehouse($warehouseId): int
    {
        // Default inventory account - you can enhance this to get from warehouse settings
        $defaultAccount = \App\Models\Accounting\Account::where('code', '1.1.3.01')->first();
        return $defaultAccount ? $defaultAccount->id : 1;
    }

    /**
     * Get liability account from business partner
     */
    private function getLiabilityAccountFromPartner($businessPartnerId): int
    {
        // Default AP UnInvoice account
        $defaultAccount = \App\Models\Accounting\Account::where('code', '2.1.1.03')->first();
        return $defaultAccount ? $defaultAccount->id : 2;
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
     * Get account information by code
     */
    private function getAccountByCode(string $code): array
    {
        $account = DB::table('accounts')
            ->where('code', $code)
            ->first();

        if (!$account) {
            throw new \Exception("Account with code {$code} not found");
        }

        return [
            'id' => $account->id,
            'code' => $account->code,
            'name' => $account->name,
        ];
    }

    private function getInventoryAccount($item): array
    {
        // Use the same logic as GRPOJournalService
        if ($item->category) {
            $categoryName = strtolower($item->category->name);

            $accountMap = [
                'electronics' => '1.1.3.01',
                'furniture' => '1.1.3.02',
                'office supplies' => '1.1.3.03',
                'raw materials' => '1.1.3.04',
            ];

            $accountCode = $accountMap[$categoryName] ?? '1.1.3.99';
        } else {
            $accountCode = '1.1.3.99'; // Default inventory account
        }

        return $this->getAccountByCode($accountCode);
    }

    private function getAPUnInvoiceAccount(): array
    {
        return $this->getAccountByCode('2.1.1.03');
    }

    private function getAPAccount(): array
    {
        return $this->getAccountByCode('2.1.1.01');
    }

    private function getARUnInvoiceAccount(): array
    {
        return $this->getAccountByCode('1.1.2.04');
    }

    private function getARAccount(): array
    {
        return $this->getAccountByCode('1.1.2.01');
    }

    private function getCashAccount(): array
    {
        return $this->getAccountByCode('1.1.1.01');
    }

    private function getRevenueAccount(): array
    {
        return $this->getAccountByCode('4.1.1');
    }
}
