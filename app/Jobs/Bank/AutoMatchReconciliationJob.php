<?php

namespace App\Jobs\Bank;

use App\Models\Bank\BankReconciliation;
use App\Services\Bank\ReconciliationMatchingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class AutoMatchReconciliationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $reconciliationId)
    {
        $this->afterCommit();
    }

    public function handle(ReconciliationMatchingService $matchingService): void
    {
        $reconciliation = BankReconciliation::query()->find($this->reconciliationId);
        if (! $reconciliation || $reconciliation->isLockedForEditing()) {
            return;
        }

        try {
            $matchingService->autoMatch($reconciliation);
        } catch (\Throwable $e) {
            Log::error('AutoMatchReconciliationJob failed', [
                'reconciliation_id' => $this->reconciliationId,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
