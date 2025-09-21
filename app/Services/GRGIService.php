<?php

namespace App\Services;

use App\Models\GRGIHeader;
use App\Models\GRGILine;
use App\Models\GRGIPurpose;
use App\Models\GRGIAccountMapping;
use App\Models\GRGIJournalEntry;
use App\Models\InventoryItem;
use App\Models\InventoryWarehouseStock;
use App\Models\Journal;
use App\Models\JournalLine;
use App\Models\Accounting\Account;
use App\Models\ProductCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class GRGIService
{
    /**
     * Create a new GR/GI header
     */
    public function createHeader($data)
    {
        return DB::transaction(function () use ($data) {
            // Generate document number
            $documentNumber = $this->generateDocumentNumber($data['document_type']);

            $header = GRGIHeader::create([
                'document_number' => $documentNumber,
                'document_type' => $data['document_type'],
                'purpose_id' => $data['purpose_id'],
                'warehouse_id' => $data['warehouse_id'],
                'transaction_date' => $data['transaction_date'],
                'reference_number' => $data['reference_number'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => 'draft',
                'created_by' => Auth::id(),
            ]);

            return $header;
        });
    }

    /**
     * Add a line to GR/GI header
     */
    public function addLine($headerId, $lineData)
    {
        return DB::transaction(function () use ($headerId, $lineData) {
            $header = GRGIHeader::findOrFail($headerId);

            if (!$header->canBeEdited()) {
                throw new \Exception('Cannot add line to non-editable document');
            }

            $line = GRGILine::create([
                'header_id' => $headerId,
                'item_id' => $lineData['item_id'],
                'quantity' => $lineData['quantity'],
                'unit_price' => $lineData['unit_price'],
                'total_amount' => $lineData['quantity'] * $lineData['unit_price'],
                'notes' => $lineData['notes'] ?? null,
            ]);

            // Update header total
            $this->updateHeaderTotal($headerId);

            return $line;
        });
    }

    /**
     * Remove a line from GR/GI header
     */
    public function removeLine($lineId)
    {
        return DB::transaction(function () use ($lineId) {
            $line = GRGILine::findOrFail($lineId);
            $headerId = $line->header_id;

            if (!$line->header->canBeEdited()) {
                throw new \Exception('Cannot remove line from non-editable document');
            }

            $line->delete();

            // Update header total
            $this->updateHeaderTotal($headerId);

            return true;
        });
    }

    /**
     * Update header total amount
     */
    public function updateHeaderTotal($headerId)
    {
        $header = GRGIHeader::findOrFail($headerId);
        $totalAmount = $header->lines()->sum('total_amount');

        $header->update(['total_amount' => $totalAmount]);

        return $totalAmount;
    }

    /**
     * Calculate GI valuation using FIFO/LIFO/Average cost
     */
    public function calculateGIValuation($itemId, $warehouseId, $quantity, $method = 'FIFO')
    {
        $item = InventoryItem::findOrFail($itemId);

        switch (strtoupper($method)) {
            case 'FIFO':
                return $this->calculateFIFO($itemId, $warehouseId, $quantity);
            case 'LIFO':
                return $this->calculateLIFO($itemId, $warehouseId, $quantity);
            case 'AVERAGE':
                return $this->calculateAverageCost($itemId, $warehouseId, $quantity);
            default:
                // Fallback to standard cost
                $unitPrice = $item->standard_cost ?? $item->last_purchase_price ?? 0;
                return [
                    'unit_price' => $unitPrice,
                    'total_amount' => $quantity * $unitPrice,
                    'method' => 'STANDARD',
                ];
        }
    }

    /**
     * Calculate FIFO (First In, First Out) valuation
     */
    protected function calculateFIFO($itemId, $warehouseId, $quantity)
    {
        // Get inventory transactions ordered by date (oldest first)
        $transactions = InventoryTransaction::where('item_id', $itemId)
            ->where('warehouse_id', $warehouseId)
            ->where('transaction_type', 'purchase')
            ->where('quantity', '>', 0)
            ->orderBy('transaction_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $remainingQuantity = $quantity;
        $totalCost = 0;
        $usedTransactions = [];

        foreach ($transactions as $transaction) {
            if ($remainingQuantity <= 0) break;

            $availableQuantity = $transaction->quantity;
            $usedQuantity = min($remainingQuantity, $availableQuantity);

            $totalCost += $usedQuantity * $transaction->unit_cost;
            $remainingQuantity -= $usedQuantity;

            $usedTransactions[] = [
                'transaction_id' => $transaction->id,
                'quantity' => $usedQuantity,
                'unit_cost' => $transaction->unit_cost,
                'total_cost' => $usedQuantity * $transaction->unit_cost,
            ];
        }

        $unitPrice = $quantity > 0 ? $totalCost / $quantity : 0;

        return [
            'unit_price' => $unitPrice,
            'total_amount' => $totalCost,
            'method' => 'FIFO',
            'used_transactions' => $usedTransactions,
        ];
    }

    /**
     * Calculate LIFO (Last In, First Out) valuation
     */
    protected function calculateLIFO($itemId, $warehouseId, $quantity)
    {
        // Get inventory transactions ordered by date (newest first)
        $transactions = InventoryTransaction::where('item_id', $itemId)
            ->where('warehouse_id', $warehouseId)
            ->where('transaction_type', 'purchase')
            ->where('quantity', '>', 0)
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $remainingQuantity = $quantity;
        $totalCost = 0;
        $usedTransactions = [];

        foreach ($transactions as $transaction) {
            if ($remainingQuantity <= 0) break;

            $availableQuantity = $transaction->quantity;
            $usedQuantity = min($remainingQuantity, $availableQuantity);

            $totalCost += $usedQuantity * $transaction->unit_cost;
            $remainingQuantity -= $usedQuantity;

            $usedTransactions[] = [
                'transaction_id' => $transaction->id,
                'quantity' => $usedQuantity,
                'unit_cost' => $transaction->unit_cost,
                'total_cost' => $usedQuantity * $transaction->unit_cost,
            ];
        }

        $unitPrice = $quantity > 0 ? $totalCost / $quantity : 0;

        return [
            'unit_price' => $unitPrice,
            'total_amount' => $totalCost,
            'method' => 'LIFO',
            'used_transactions' => $usedTransactions,
        ];
    }

    /**
     * Calculate Average Cost valuation
     */
    protected function calculateAverageCost($itemId, $warehouseId, $quantity)
    {
        // Calculate weighted average cost from all purchase transactions
        $transactions = InventoryTransaction::where('item_id', $itemId)
            ->where('warehouse_id', $warehouseId)
            ->where('transaction_type', 'purchase')
            ->where('quantity', '>', 0)
            ->get();

        if ($transactions->isEmpty()) {
            $item = InventoryItem::findOrFail($itemId);
            $unitPrice = $item->standard_cost ?? $item->last_purchase_price ?? 0;
            return [
                'unit_price' => $unitPrice,
                'total_amount' => $quantity * $unitPrice,
                'method' => 'AVERAGE',
            ];
        }

        $totalQuantity = $transactions->sum('quantity');
        $totalCost = $transactions->sum(function ($transaction) {
            return $transaction->quantity * $transaction->unit_cost;
        });

        $averageUnitPrice = $totalQuantity > 0 ? $totalCost / $totalQuantity : 0;

        return [
            'unit_price' => $averageUnitPrice,
            'total_amount' => $quantity * $averageUnitPrice,
            'method' => 'AVERAGE',
        ];
    }

    /**
     * Generate journal entries for GR/GI
     */
    public function generateJournalEntries($headerId)
    {
        return DB::transaction(function () use ($headerId) {
            $header = GRGIHeader::with(['lines.item.productCategory', 'purpose'])->findOrFail($headerId);

            if ($header->status !== 'approved') {
                throw new \Exception('Can only generate journal entries for approved documents');
            }

            $journalEntries = [];

            foreach ($header->lines as $line) {
                $journalEntry = $this->createJournalEntry($header, $line);
                $journalEntries[] = $journalEntry;
            }

            return $journalEntries;
        });
    }

    /**
     * Create individual journal entry for a line
     */
    protected function createJournalEntry($header, $line)
    {
        $journal = Journal::create([
            'journal_number' => $this->generateJournalNumber(),
            'journal_date' => $header->transaction_date,
            'reference' => $header->document_number,
            'description' => "{$header->document_type_name} - {$line->item->name}",
            'total_debit' => $line->total_amount,
            'total_credit' => $line->total_amount,
            'status' => 'posted',
            'created_by' => Auth::id(),
        ]);

        // Get account mappings
        $accountMapping = $this->getAccountMapping($header->purpose_id, $line->item->category_id);

        if ($header->document_type === 'goods_receipt') {
            // GR: Debit = item category (auto), Credit = manual selection
            $debitAccountId = $this->getItemCategoryAccount($line->item->category_id);
            $creditAccountId = $accountMapping->credit_account_id;
        } else {
            // GI: Debit = manual selection, Credit = item category (auto)
            $debitAccountId = $accountMapping->debit_account_id;
            $creditAccountId = $this->getItemCategoryAccount($line->item->category_id);
        }

        // Create journal lines
        JournalLine::create([
            'journal_id' => $journal->id,
            'account_id' => $debitAccountId,
            'debit_amount' => $line->total_amount,
            'credit_amount' => 0,
            'description' => "Debit for {$line->item->name}",
        ]);

        JournalLine::create([
            'journal_id' => $journal->id,
            'account_id' => $creditAccountId,
            'debit_amount' => 0,
            'credit_amount' => $line->total_amount,
            'description' => "Credit for {$line->item->name}",
        ]);

        // Link to GR/GI
        GRGIJournalEntry::create([
            'header_id' => $header->id,
            'line_id' => $line->id,
            'gr_gi_type' => $header->document_type,
            'journal_entry_id' => $journal->id,
        ]);

        return $journal;
    }

    /**
     * Get account mapping for purpose and item category
     */
    protected function getAccountMapping($purposeId, $itemCategoryId)
    {
        $mapping = GRGIAccountMapping::where('purpose_id', $purposeId)
            ->where('item_category_id', $itemCategoryId)
            ->first();

        if (!$mapping) {
            throw new \Exception("Account mapping not found for purpose ID {$purposeId} and category ID {$itemCategoryId}");
        }

        return $mapping;
    }

    /**
     * Get item category account (this would be configured per category)
     */
    protected function getItemCategoryAccount($itemCategoryId)
    {
        // Try to find category-specific inventory account
        $category = ProductCategory::find($itemCategoryId);

        if ($category && $category->inventory_account_id) {
            return $category->inventory_account_id;
        }

        // Fallback to default inventory account
        $defaultInventoryAccount = Account::where('account_code', 'INV001')->first();

        if (!$defaultInventoryAccount) {
            // Try to find any inventory account
            $defaultInventoryAccount = Account::where('account_name', 'like', '%inventory%')
                ->orWhere('account_name', 'like', '%stock%')
                ->first();
        }

        if (!$defaultInventoryAccount) {
            throw new \Exception('No inventory account found. Please configure inventory accounts.');
        }

        return $defaultInventoryAccount->id;
    }

    /**
     * Validate account mapping before creating journal entries
     */
    protected function validateAccountMapping($header)
    {
        foreach ($header->lines as $line) {
            try {
                $this->getAccountMapping($header->purpose_id, $line->item->category_id);
            } catch (\Exception $e) {
                throw new \Exception("Account mapping validation failed for line {$line->id}: " . $e->getMessage());
            }
        }
    }

    /**
     * Approve GR/GI document
     */
    public function approve($headerId, $approvedBy)
    {
        return DB::transaction(function () use ($headerId, $approvedBy) {
            $header = GRGIHeader::with(['lines.item', 'purpose'])->findOrFail($headerId);

            if (!$header->canBeApproved()) {
                throw new \Exception('Document cannot be approved');
            }

            // Validate account mappings before approval
            $this->validateAccountMapping($header);

            // Update document status
            $header->update([
                'status' => 'approved',
                'approved_by' => $approvedBy,
                'approved_at' => now(),
            ]);

            // Generate journal entries
            $this->generateJournalEntries($headerId);

            // Update inventory
            $this->updateInventory($header);

            return $header;
        });
    }

    /**
     * Cancel GR/GI document
     */
    public function cancel($headerId, $cancelledBy)
    {
        return DB::transaction(function () use ($headerId, $cancelledBy) {
            $header = GRGIHeader::findOrFail($headerId);

            if (!$header->canBeCancelled()) {
                throw new \Exception('Document cannot be cancelled');
            }

            $header->update([
                'status' => 'cancelled',
                'cancelled_by' => $cancelledBy,
                'cancelled_at' => now(),
            ]);

            return $header;
        });
    }

    /**
     * Update inventory based on GR/GI
     */
    protected function updateInventory($header)
    {
        foreach ($header->lines as $line) {
            $quantityChange = $header->document_type === 'goods_receipt'
                ? $line->quantity
                : -$line->quantity;

            $this->updateWarehouseStock(
                $line->item_id,
                $header->warehouse_id,
                $quantityChange,
                $header->document_type,
                $header->id,
                "GR/GI: {$header->document_number}"
            );
        }
    }

    /**
     * Update warehouse stock
     */
    protected function updateWarehouseStock($itemId, $warehouseId, $quantityChange, $transactionType, $referenceId, $notes)
    {
        $warehouseStock = InventoryWarehouseStock::firstOrCreate(
            ['item_id' => $itemId, 'warehouse_id' => $warehouseId],
            [
                'quantity_on_hand' => 0,
                'reserved_quantity' => 0,
                'available_quantity' => 0,
                'min_stock_level' => 0,
                'max_stock_level' => 0,
                'reorder_point' => 0,
            ]
        );

        $warehouseStock->quantity_on_hand += $quantityChange;
        $warehouseStock->updateAvailableQuantity();
        $warehouseStock->save();
    }

    /**
     * Get GR/GI purposes by type
     */
    public function getPurposes($type = null)
    {
        $query = GRGIPurpose::active();

        if ($type) {
            $query->byType($type);
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Generate document number
     */
    protected function generateDocumentNumber($documentType)
    {
        $prefix = $documentType === 'goods_receipt' ? 'GR' : 'GI';
        $year = date('Y');
        $month = date('m');

        $lastNumber = GRGIHeader::where('document_type', $documentType)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->count();

        $nextNumber = $lastNumber + 1;

        return "{$prefix}{$year}{$month}" . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Generate journal number
     */
    protected function generateJournalNumber()
    {
        $year = date('Y');
        $month = date('m');

        $lastNumber = Journal::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->count();

        $nextNumber = $lastNumber + 1;

        return "JNL{$year}{$month}" . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
