<?php

namespace App\Console\Commands;

use App\Models\Bank\BankReconciliation;
use App\Services\Bank\BankBookLineFetcher;
use App\Services\Bank\ReconciliationBalanceService;
use Illuminate\Console\Command;

class MigrateLegacyBankReconciliationCommand extends Command
{
    protected $signature = 'bank-reconciliation:migrate-legacy {--fetch-book : Re-fetch book lines for sessions missing them}';

    protected $description = 'Verify and repair legacy bank reconciliation sessions after N:M migration';

    public function handle(
        BankBookLineFetcher $fetcher,
        ReconciliationBalanceService $balanceService,
    ): int {
        $sessions = BankReconciliation::query()->orderBy('id')->get();

        if ($sessions->isEmpty()) {
            $this->info('No reconciliation sessions found.');

            return self::SUCCESS;
        }

        foreach ($sessions as $session) {
            $this->line("Session #{$session->id} — {$session->bankAccount?->name} — {$session->periode?->format('Y-m')}");

            if ($this->option('fetch-book') && $session->bookLines()->count() === 0) {
                $count = $fetcher->fetchAndReplace($session);
                $this->info("  Fetched {$count} book line(s).");
            }

            $balanced = $balanceService->isBalanced($session);
            $diff = $balanceService->difference($session);

            if ($balanced) {
                $this->info('  Balance OK (difference = 0).');
            } else {
                $this->warn('  Not balanced — difference: '.number_format($diff, 2));
            }

            $this->info('  Match groups: '.$session->matchGroups()->count());
            $this->info('  Bank lines: '.$session->bankLines()->count().' | Book lines: '.$session->bookLines()->count());
        }

        return self::SUCCESS;
    }
}
