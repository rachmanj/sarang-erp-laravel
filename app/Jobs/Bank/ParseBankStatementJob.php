<?php

namespace App\Jobs\Bank;

use App\Models\Bank\BankReconciliation;
use App\Services\Bank\BankReconciliationService;
use App\Services\Bank\BankStatementParser;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ParseBankStatementJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $reconciliationId)
    {
        $this->afterCommit();
    }

    public function handle(
        BankStatementParser $parser,
        BankReconciliationService $reconciliationService,
    ): void {
        $reconciliation = BankReconciliation::query()->find($this->reconciliationId);
        if (! $reconciliation) {
            return;
        }

        try {
            $result = $parser->parseForReconciliation($reconciliation);

            $reconciliationService->markInReview($reconciliation, [
                'opening_balance_bank' => $result['opening_balance'],
                'closing_balance_bank' => $result['closing_balance'],
                'statement_opening' => $result['opening_balance'],
                'statement_closing' => $result['closing_balance'],
                'period_start' => $reconciliation->periodStartDate(),
                'period_end' => $reconciliation->periodEndDate(),
            ]);

            FetchBookGlLinesJob::dispatch($reconciliation->id);
        } catch (\Throwable $e) {
            Log::error('ParseBankStatementJob failed', [
                'reconciliation_id' => $this->reconciliationId,
                'message' => $e->getMessage(),
            ]);

            $reconciliationService->markFailed($reconciliation, $e->getMessage());
        }
    }
}
