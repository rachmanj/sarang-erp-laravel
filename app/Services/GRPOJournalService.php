<?php

namespace App\Services;

use App\Models\Accounting\Journal;
use App\Models\GoodsReceiptPO;
use App\Models\GRPOJournalEntry;
use App\Services\Accounting\JournalBuilders\GrpoJournalBuilder;
use App\Services\Accounting\PostingService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GRPOJournalService
{
    public function __construct(
        private PostingService $postingService,
        private GrpoJournalBuilder $grpoJournalBuilder,
    ) {}

    public function buildJournalDraft(GoodsReceiptPO $grpo): \App\Services\Accounting\JournalBuilders\JournalDraft
    {
        return $this->grpoJournalBuilder->build($grpo);
    }

    /**
     * Create journal entries for GRPO
     */
    public function createJournalEntries(GoodsReceiptPO $grpo): Journal
    {
        if (! $grpo->canBeJournalized()) {
            throw new \Exception('GRPO cannot be journalized. Status must be "received" and not already journalized.');
        }

        return DB::transaction(function () use ($grpo) {
            $draft = $this->buildJournalDraft($grpo);

            $payload = [
                'date' => $draft->date ?? $grpo->date,
                'description' => $draft->description,
                'source_type' => 'goods_receipt_po',
                'source_id' => $grpo->id,
                'posted_by' => Auth::id(),
                'lines' => $draft->lines,
            ];

            $journalId = $this->postingService->postJournal($payload);
            $journal = Journal::findOrFail($journalId);

            $grpo->update([
                'journal_id' => $journalId,
                'journal_posted_at' => now(),
                'journal_posted_by' => Auth::id(),
            ]);

            $this->createGRPOJournalEntries($grpo, $journal);

            return $journal;
        });
    }

    /**
     * Create GRPO journal entry tracking records
     */
    protected function createGRPOJournalEntries(GoodsReceiptPO $grpo, Journal $journal): void
    {
        $journalLines = $journal->lines()->get();
        $liabilityAccountId = $this->grpoJournalBuilder->getLiabilityAccountForTracking();

        foreach ($grpo->lines as $grpoLine) {
            $lineAmount = $grpoLine->qty * ($grpoLine->unit_price ?? 0);
            $inventoryAccountId = $this->grpoJournalBuilder->getInventoryAccountForLine($grpoLine);

            $inventoryJournalLine = $journalLines->where('account_id', $inventoryAccountId)->first();
            $liabilityJournalLine = $journalLines->where('account_id', $liabilityAccountId)->first();

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
        if (! $grpo->isJournalized()) {
            throw new \Exception('GRPO has not been journalized yet.');
        }

        return DB::transaction(function () use ($grpo) {
            $reversalJournalId = $this->postingService->reverseJournal(
                $grpo->journal_id,
                now()->toDateString(),
                Auth::id()
            );

            $grpo->update([
                'journal_id' => null,
                'journal_posted_at' => null,
                'journal_posted_by' => null,
            ]);

            GRPOJournalEntry::where('grpo_id', $grpo->id)->delete();

            return Journal::findOrFail($reversalJournalId);
        });
    }

    /**
     * Get journal entries for GRPO
     */
    public function getJournalEntries(GoodsReceiptPO $grpo): array
    {
        if (! $grpo->isJournalized()) {
            return [];
        }

        return [
            'journal' => $grpo->journal,
            'journal_lines' => $grpo->journal->lines,
            'grpo_journal_entries' => $grpo->journalEntries,
        ];
    }
}
