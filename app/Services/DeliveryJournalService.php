<?php

namespace App\Services;

use App\Models\DeliveryOrder;
use App\Services\Accounting\JournalBuilders\DeliveryOrderJournalBuilder;
use App\Services\Accounting\PostingService;
use Exception;
use Illuminate\Support\Facades\DB;

class DeliveryJournalService
{
    public function __construct(
        private PostingService $postingService,
        private DocumentNumberingService $documentNumberingService,
        private DeliveryOrderJournalBuilder $deliveryOrderJournalBuilder,
    ) {}

    /**
     * Create revenue recognition journal entry when items are delivered
     */
    public function createRevenueRecognition(DeliveryOrder $deliveryOrder): int
    {
        $draft = $this->deliveryOrderJournalBuilder->buildRevenueRecognition($deliveryOrder);

        return $this->postingService->postJournal([
            'date' => $draft->date ?? $deliveryOrder->actual_delivery_date->toDateString(),
            'description' => $draft->description,
            'source_type' => DeliveryOrder::class,
            'source_id' => $deliveryOrder->id,
            'lines' => $draft->lines,
        ]);
    }

    /**
     * Reverse all original (non-reversal) journals for this delivery order, newest first.
     * Skips entries that already have a matching reversal journal.
     */
    public function reverseOriginalJournalsForDeliveryOrder(DeliveryOrder $deliveryOrder, ?string $asOfDate = null, ?int $postedBy = null): void
    {
        $asOfDate = $asOfDate ?? now()->toDateString();
        $postedBy = $postedBy ?? auth()->id();

        $candidates = DB::table('journals')
            ->where('source_type', DeliveryOrder::class)
            ->where('source_id', $deliveryOrder->id)
            ->where('description', 'not like', 'Reversal of %')
            ->orderByDesc('id')
            ->get();

        foreach ($candidates as $journal) {
            $alreadyReversed = DB::table('journals')
                ->where('source_type', DeliveryOrder::class)
                ->where('source_id', $deliveryOrder->id)
                ->where(function ($q) use ($journal) {
                    $q->where('description', 'Reversal of #'.$journal->id)
                        ->orWhere('description', 'like', 'Reversal of #'.$journal->id.' -%');
                })
                ->exists();

            if ($alreadyReversed) {
                continue;
            }

            $this->postingService->reverseJournal((int) $journal->id, $asOfDate, $postedBy);
        }
    }

    /**
     * Get sales revenue account ID
     */
    private function getSalesRevenueAccount(): int
    {
        $account = DB::table('accounts')
            ->where('code', '4.1.1.01')
            ->orWhere('name', 'like', '%Penjualan Stationery%')
            ->first();

        if (! $account) {
            throw new Exception('Sales Revenue account not found. Please create account with code 4.1.1.01');
        }

        return $account->id;
    }

    /**
     * Get COGS account ID
     */
    private function getCOGSAccount(): int
    {
        $account = DB::table('accounts')
            ->where(function ($q) {
                $q->where('code', '5.1.01')
                    ->orWhere('name', 'like', '%HPP Stationery%');
            })
            ->first();

        if (! $account) {
            throw new Exception('Cost of Goods Sold account not found. Please create account with code 5.1.01');
        }

        return $account->id;
    }

    /**
     * Get AR UnInvoice account ID
     */
    private function getARUnInvoiceAccount(): int
    {
        $account = DB::table('accounts')
            ->where('code', '1.1.2.04') // AR UnInvoice
            ->first();

        if (! $account) {
            throw new Exception('AR UnInvoice account not found. Please create account with code 1.1.2.04');
        }

        return $account->id;
    }

    /**
     * Get accounts receivable account ID
     */
    private function getAccountsReceivableAccount(): int
    {
        $account = DB::table('accounts')
            ->where('code', '1.1.2.01') // Piutang Dagang
            ->first();

        if (! $account) {
            throw new Exception('Accounts Receivable account not found. Please create account with code 1.1.2.01');
        }

        return $account->id;
    }
}
