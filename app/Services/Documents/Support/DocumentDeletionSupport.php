<?php

namespace App\Services\Documents\Support;

use App\Models\DocumentRelationship;
use App\Models\TaxTransaction;
use App\Services\Accounting\PeriodCloseService;
use App\Services\Accounting\PostingService;
use App\Services\Documents\DocumentDescriptor;
use App\Services\Documents\DocumentType;
use App\Services\Documents\Exceptions\DocumentDeletionBlockedException;
use App\Services\InventoryService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DocumentDeletionSupport
{
    public function __construct(
        private PostingService $postingService,
        private PeriodCloseService $periodCloseService,
        private InventoryService $inventoryService,
    ) {}

    /** @param list<string> $sourceTypes */
    public function reverseJournalsFor(array $sourceTypes, int $sourceId, ?int $postedBy = null): void
    {
        if ($sourceTypes === []) {
            return;
        }

        $journals = DB::table('journals')
            ->whereIn('source_type', $sourceTypes)
            ->where('source_id', $sourceId)
            ->orderByDesc('id')
            ->get();

        foreach ($journals as $journal) {
            if (str_starts_with((string) $journal->description, 'Reversal of #')) {
                continue;
            }

            $alreadyReversed = DB::table('journals')
                ->where('source_type', $journal->source_type)
                ->where('source_id', $journal->source_id)
                ->where('description', 'like', 'Reversal of #'.$journal->id.'%')
                ->exists();

            if ($alreadyReversed) {
                continue;
            }

            $lineIds = DB::table('journal_lines')->where('journal_id', $journal->id)->pluck('id');
            if ($lineIds->isNotEmpty()) {
                DB::table('bank_reconciliation_matches')
                    ->whereIn('journal_line_id', $lineIds)
                    ->update(['journal_line_id' => null]);
            }

            DB::table('bank_reconciliation_matches')
                ->where('journal_id', $journal->id)
                ->update(['journal_id' => null]);

            $this->postingService->reverseJournal(
                (int) $journal->id,
                now()->toDateString(),
                $postedBy ?? Auth::id()
            );
        }
    }

    public function deleteTaxTransactions(string $referenceType, int $referenceId): void
    {
        TaxTransaction::query()
            ->where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->delete();
    }

    public function clearDocumentRelationships(string $modelClass, int $documentId): void
    {
        DocumentRelationship::query()
            ->where(function ($query) use ($modelClass, $documentId) {
                $query->where('source_document_type', $modelClass)
                    ->where('source_document_id', $documentId);
            })
            ->orWhere(function ($query) use ($modelClass, $documentId) {
                $query->where('target_document_type', $modelClass)
                    ->where('target_document_id', $documentId);
            })
            ->delete();
    }

    public function reopenClosureIfClosedBy(object $document, string $closedByType, int $closedById): void
    {
        if (($document->closure_status ?? 'open') !== 'closed') {
            return;
        }

        if ((int) ($document->closed_by_document_id ?? 0) !== $closedById) {
            return;
        }

        if (($document->closed_by_document_type ?? null) !== $closedByType) {
            return;
        }

        $document->forceFill([
            'closure_status' => 'open',
            'closed_by_document_type' => null,
            'closed_by_document_id' => null,
            'closed_at' => null,
            'closed_by_user_id' => null,
        ])->save();
    }

    public function reverseInventoryByReference(string $referenceType, int $referenceId, string $notes): void
    {
        $transactions = InventoryTransaction::query()
            ->where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->orderBy('id')
            ->get();

        foreach ($transactions as $transaction) {
            if ($transaction->transaction_type === 'purchase' && (float) $transaction->quantity > 0) {
                $this->inventoryService->removePurchaseInventoryTransaction($transaction);

                continue;
            }

            InventoryTransaction::query()->create([
                'item_id' => $transaction->item_id,
                'transaction_type' => 'adjustment',
                'quantity' => -((float) $transaction->quantity),
                'unit_cost' => $transaction->unit_cost,
                'total_cost' => -((float) $transaction->total_cost),
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'transaction_date' => now()->toDateString(),
                'notes' => $notes,
                'warehouse_id' => $transaction->warehouse_id,
                'created_by' => Auth::id(),
            ]);

            $transaction->delete();

            $item = \App\Models\InventoryItem::find($transaction->item_id);
            if ($item) {
                $this->inventoryService->updateItemValuation($item);
            }
        }
    }

    /**
     * @param  list<array{type: string, id: int}>  $documents
     */
    public function assertDeletable(array $documents): void
    {
        $blockers = [];

        if ($this->periodCloseService->isDateClosed(now()->toDateString())) {
            $blockers[] = 'The current accounting period is closed. Reopen it before deleting posted documents (reversals post today).';
        }

        foreach ($documents as $doc) {
            $type = $doc['type'];
            $id = $doc['id'];
            $label = DocumentDescriptor::label($type);
            $modelClass = DocumentDescriptor::modelClass($type);
            $model = $modelClass::query()->find($id);

            if (! $model) {
                continue;
            }

            $dateColumn = DocumentDescriptor::dateColumn($type);
            $documentDate = $model->{$dateColumn} ?? null;
            if ($documentDate) {
                $dateString = $documentDate instanceof Carbon
                    ? $documentDate->toDateString()
                    : Carbon::parse($documentDate)->toDateString();

                if ($this->periodCloseService->isDateClosed($dateString)) {
                    $blockers[] = "{$label} #{$this->documentNumber($type, $model)} falls in a closed period ({$dateString}). Reopen that period first.";
                }
            }

            foreach (DocumentDescriptor::journalSourceTypes($type) as $sourceType) {
                $journals = DB::table('journals')
                    ->where('source_type', $sourceType)
                    ->where('source_id', $id)
                    ->get(['id', 'date', 'description']);

                foreach ($journals as $journal) {
                    if (str_starts_with((string) $journal->description, 'Reversal of #')) {
                        continue;
                    }

                    if ($this->periodCloseService->isDateClosed((string) $journal->date)) {
                        $blockers[] = "{$label} #{$this->documentNumber($type, $model)} has journal #{$journal->id} in closed period ({$journal->date}). Reopen that period first.";
                    }
                }
            }
        }

        if ($blockers !== []) {
            throw new DocumentDeletionBlockedException(
                'Cannot delete: '.implode(' ', $blockers),
                $blockers
            );
        }
    }

    /** @param list<array{type: string, id: int}> $documents */
    public function assertNoSharedSettlementDocuments(array $documents): void
    {
        $idsByType = [];
        foreach ($documents as $doc) {
            $idsByType[$doc['type']][] = $doc['id'];
        }

        $invoiceIds = $idsByType[DocumentType::SALES_INVOICE] ?? [];
        if ($invoiceIds !== []) {
            $receiptIds = DB::table('sales_receipt_allocations')
                ->whereIn('invoice_id', $invoiceIds)
                ->pluck('receipt_id')
                ->unique()
                ->all();

            foreach ($receiptIds as $receiptId) {
                $allInvoiceIds = DB::table('sales_receipt_allocations')
                    ->where('receipt_id', $receiptId)
                    ->pluck('invoice_id')
                    ->all();

                $outside = array_diff($allInvoiceIds, $invoiceIds);
                if ($outside !== []) {
                    throw new DocumentDeletionBlockedException(
                        'Sales Receipt #'.(DB::table('sales_receipts')->where('id', $receiptId)->value('receipt_no') ?? $receiptId)
                        .' also pays other invoices not included in this delete. Delete or reallocate that receipt first.'
                    );
                }
            }
        }

        $purchaseInvoiceIds = $idsByType[DocumentType::PURCHASE_INVOICE] ?? [];
        if ($purchaseInvoiceIds !== []) {
            $paymentIds = DB::table('purchase_payment_allocations')
                ->whereIn('invoice_id', $purchaseInvoiceIds)
                ->pluck('payment_id')
                ->unique()
                ->all();

            foreach ($paymentIds as $paymentId) {
                $allInvoiceIds = DB::table('purchase_payment_allocations')
                    ->where('payment_id', $paymentId)
                    ->pluck('invoice_id')
                    ->all();

                $outside = array_diff($allInvoiceIds, $purchaseInvoiceIds);
                if ($outside !== []) {
                    throw new DocumentDeletionBlockedException(
                        'Purchase Payment #'.(DB::table('purchase_payments')->where('id', $paymentId)->value('payment_no') ?? $paymentId)
                        .' also pays other invoices not included in this delete. Delete or reallocate that payment first.'
                    );
                }
            }
        }
    }

    public function documentNumber(string $type, object $model): string
    {
        $column = DocumentDescriptor::numberColumn($type);
        $number = $model->{$column} ?? null;

        return $number ? (string) $number : '#'.$model->id;
    }

    public function childRef(string $type, int $id): array
    {
        return ['type' => $type, 'id' => $id];
    }
}
