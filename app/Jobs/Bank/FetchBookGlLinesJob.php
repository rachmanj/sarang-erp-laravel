<?php

namespace App\Jobs\Bank;

use App\Models\Bank\BankReconciliation;
use App\Services\Bank\BankReconciliationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class FetchBookGlLinesJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $reconciliationId)
    {
        $this->afterCommit();
    }

    public function handle(BankReconciliationService $reconciliationService): void
    {
        $reconciliation = BankReconciliation::query()->find($this->reconciliationId);
        if (! $reconciliation) {
            return;
        }

        try {
            $reconciliationService->fetchBookLines($reconciliation);

            if ($reconciliation->status === BankReconciliation::STATUS_PROCESSING) {
                $reconciliationService->markInReview($reconciliation);
            }
        } catch (\Throwable $e) {
            Log::error('FetchBookGlLinesJob failed', [
                'reconciliation_id' => $this->reconciliationId,
                'message' => $e->getMessage(),
            ]);

            $reconciliationService->markFailed($reconciliation, $e->getMessage());
        }
    }
}
