<?php

namespace App\Console\Commands;

use App\Models\Audit\AuditResult;
use App\Models\Audit\AuditRun;
use App\Services\Audit\AuditCheck;
use App\Services\Audit\AuditCheckResult;
use App\Services\Audit\Checks\BackdatedJournalCheck;
use App\Services\Audit\Checks\DuplicatePostingCheck;
use App\Services\Audit\Checks\JournalImbalanceCheck;
use App\Services\Audit\Checks\NegativeCashCheck;
use App\Services\Audit\Checks\NonPostableParentCheck;
use App\Services\Audit\Checks\OrphanedMatchGroupCheck;
use App\Services\Audit\Checks\ReconciliationGapCheck;
use App\Services\Audit\Checks\ReversalWrongAccountCheck;
use App\Services\Audit\Checks\TbImbalanceCheck;
use Illuminate\Console\Command;
use Throwable;

class RunAuditCommand extends Command
{
    protected $signature = 'audit:run {--triggered-by=artisan : Who triggered this audit run}';

    protected $description = 'Run detect-only data audit checks and store results';

    /**
     * @return array<int, class-string<AuditCheck>>
     */
    private function checkClasses(): array
    {
        return [
            TbImbalanceCheck::class,
            JournalImbalanceCheck::class,
            DuplicatePostingCheck::class,
            ReversalWrongAccountCheck::class,
            NonPostableParentCheck::class,
            BackdatedJournalCheck::class,
            NegativeCashCheck::class,
            ReconciliationGapCheck::class,
            OrphanedMatchGroupCheck::class,
        ];
    }

    public function handle(): int
    {
        $checks = array_map(fn (string $class) => app($class), $this->checkClasses());
        $startedAt = now();

        $auditRun = AuditRun::create([
            'status' => 'running',
            'started_at' => $startedAt,
            'triggered_by' => $this->option('triggered-by'),
            'total_checks' => count($checks),
        ]);

        $passedChecks = 0;
        $failedChecks = 0;
        $totalIssues = 0;
        $tableRows = [];

        foreach ($checks as $check) {
            try {
                $result = $check->run();
            } catch (Throwable $exception) {
                $result = AuditCheckResult::fail([$exception->getMessage()]);
            }

            if ($result->isPass()) {
                $passedChecks++;
            } else {
                $failedChecks++;
            }

            $issueCount = count($result->issues);
            $totalIssues += $issueCount;

            AuditResult::create([
                'audit_run_id' => $auditRun->id,
                'check_key' => $check->key,
                'check_name' => $check->name,
                'status' => $result->status,
                'issue_count' => $issueCount,
                'details' => $issueCount > 0 ? json_encode($result->issues, JSON_UNESCAPED_UNICODE) : null,
            ]);

            $tableRows[] = [
                $check->key,
                $check->name,
                $result->status,
                $issueCount,
            ];
        }

        $auditRun->update([
            'status' => 'completed',
            'finished_at' => now(),
            'passed_checks' => $passedChecks,
            'failed_checks' => $failedChecks,
            'total_issues' => $totalIssues,
        ]);

        $this->info("Audit run #{$auditRun->id} completed.");
        $this->table(['Check Key', 'Check Name', 'Status', 'Issues'], $tableRows);
        $this->line("Passed: {$passedChecks}, Failed: {$failedChecks}, Total issues: {$totalIssues}");

        return self::SUCCESS;
    }
}
