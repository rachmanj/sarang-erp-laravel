<?php

namespace App\Services;

use App\Models\GoodsReceiptPO;
use App\Models\GoodsReceiptPOLine;
use App\Models\GRPOJournalEntry;
use App\Models\Accounting\Journal;
use App\Models\Accounting\JournalLine;
use App\Services\Accounting\PostingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class GRPOJournalService
{
    public function __construct(
        private PostingService $postingService
    ) {}

    /**
     * Create journal entries for GRPO
     */
    public function createJournalEntries(GoodsReceiptPO $grpo): Journal
    {
        if (!$grpo->canBeJournalized()) {
            throw new \Exception('GRPO cannot be journalized. Status must be "received" and not already journalized.');
        }

        return DB::transaction(function () use ($grpo) {
            // Calculate total amount from lines
            $totalAmount = $grpo->lines->sum(function ($line) {
                return $line->qty * ($line->unit_price ?? 0);
            });

            if ($totalAmount <= 0) {
                throw new \Exception('GRPO total amount must be greater than zero to create journal entries.');
            }

            // Get account mappings
            $inventoryAccountId = $this->getInventoryAccount($grpo);
            $liabilityAccountId = $this->getLiabilityAccount($grpo);

            // Create journal payload
            $payload = [
                'date' => $grpo->date,
                'description' => "GRPO Receipt - {$grpo->grn_no}",
                'source_type' => 'goods_receipt_po',
                'source_id' => $grpo->id,
                'posted_by' => Auth::id(),
                'lines' => [
                    [
                        'account_id' => $inventoryAccountId,
                        'debit' => $totalAmount,
                        'credit' => 0,
                        'project_id' => null,
                        'dept_id' => null,
                        'memo' => "Inventory receipt from GRPO {$grpo->grn_no}",
                    ],
                    [
                        'account_id' => $liabilityAccountId,
                        'debit' => 0,
                        'credit' => $totalAmount,
                        'project_id' => null,
                        'dept_id' => null,
                        'memo' => "Liability for GRPO {$grpo->grn_no}",
                    ],
                ],
            ];

            // Post journal using PostingService
            $journalId = $this->postingService->postJournal($payload);
            $journal = Journal::findOrFail($journalId);

            // Update GRPO with journal information
            $grpo->update([
                'journal_id' => $journalId,
                'journal_posted_at' => now(),
                'journal_posted_by' => Auth::id(),
            ]);

            // Create GRPO journal entry tracking records
            $this->createGRPOJournalEntries($grpo, $journal);

            return $journal;
        });
    }

    /**
     * Get inventory account for GRPO
     */
    protected function getInventoryAccount(GoodsReceiptPO $grpo): int
    {
        // Try to get account from first line's item category
        $firstLine = $grpo->lines->first();
        if ($firstLine && $firstLine->item && $firstLine->item->category) {
            $category = $firstLine->item->category;
            if ($category->inventory_account_id) {
                return $category->inventory_account_id;
            }
        }

        // Fallback to default inventory account based on item category
        $categoryName = strtolower($firstLine->item->category->name ?? 'electronics');
        $categoryMapping = [
            'electronics' => '1.1.3.01.02', // Persediaan Electronics
            'furniture' => '1.1.3.01.03',   // Persediaan Furniture
            'stationery' => '1.1.3.01.01',  // Persediaan Stationery
            'vehicles' => '1.1.3.01.04',    // Persediaan Vehicles
            'services' => '1.1.3.01.05',    // Persediaan Services
        ];

        $accountCode = $categoryMapping[$categoryName] ?? '1.1.3.01.02'; // Default to electronics

        $defaultAccount = DB::table('accounts')
            ->where('code', $accountCode)
            ->first();

        if (!$defaultAccount) {
            throw new \Exception('No inventory account found. Please configure inventory accounts.');
        }

        return $defaultAccount->id;
    }

    /**
     * Get liability account for GRPO
     */
    protected function getLiabilityAccount(GoodsReceiptPO $grpo): int
    {
        // Use AP UnInvoice account (intermediate account for goods received but not yet invoiced)
        $apAccount = DB::table('accounts')
            ->where('code', '2.1.1.03') // AP UnInvoice
            ->first();

        if (!$apAccount) {
            throw new \Exception('No AP UnInvoice account found. Please configure AP UnInvoice account.');
        }

        return $apAccount->id;
    }

    /**
     * Create GRPO journal entry tracking records
     */
    protected function createGRPOJournalEntries(GoodsReceiptPO $grpo, Journal $journal): void
    {
        $journalLines = $journal->lines()->get();

        foreach ($grpo->lines as $grpoLine) {
            $lineAmount = $grpoLine->qty * ($grpoLine->unit_price ?? 0);

            // Find corresponding journal lines
            $inventoryJournalLine = $journalLines->where('account_id', $this->getInventoryAccount($grpo))->first();
            $liabilityJournalLine = $journalLines->where('account_id', $this->getLiabilityAccount($grpo))->first();

            if ($inventoryJournalLine) {
                GRPOJournalEntry::create([
                    'grpo_id' => $grpo->id,
                    'grpo_line_id' => $grpoLine->id,
                    'journal_id' => $journal->id,
                    'journal_line_id' => $inventoryJournalLine->id,
                    'amount' => $lineAmount,
                    'account_type' => 'inventory',
                ]);
            }

            if ($liabilityJournalLine) {
                GRPOJournalEntry::create([
                    'grpo_id' => $grpo->id,
                    'grpo_line_id' => $grpoLine->id,
                    'journal_id' => $journal->id,
                    'journal_line_id' => $liabilityJournalLine->id,
                    'amount' => $lineAmount,
                    'account_type' => 'liability',
                ]);
            }
        }
    }

    /**
     * Reverse journal entries for GRPO
     */
    public function reverseJournalEntries(GoodsReceiptPO $grpo): Journal
    {
        if (!$grpo->isJournalized()) {
            throw new \Exception('GRPO has not been journalized yet.');
        }

        return DB::transaction(function () use ($grpo) {
            // Reverse the journal using PostingService
            $reversalJournalId = $this->postingService->reverseJournal(
                $grpo->journal_id,
                now()->toDateString(),
                Auth::id()
            );

            // Update GRPO to remove journal reference
            $grpo->update([
                'journal_id' => null,
                'journal_posted_at' => null,
                'journal_posted_by' => null,
            ]);

            // Delete GRPO journal entry tracking records
            GRPOJournalEntry::where('grpo_id', $grpo->id)->delete();

            return Journal::findOrFail($reversalJournalId);
        });
    }

    /**
     * Get journal entries for GRPO
     */
    public function getJournalEntries(GoodsReceiptPO $grpo): array
    {
        if (!$grpo->isJournalized()) {
            return [];
        }

        return [
            'journal' => $grpo->journal,
            'journal_lines' => $grpo->journal->lines,
            'grpo_journal_entries' => $grpo->journalEntries,
        ];
    }
}
