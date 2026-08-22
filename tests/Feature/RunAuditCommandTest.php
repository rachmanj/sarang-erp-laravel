<?php

namespace Tests\Feature;

use App\Models\Audit\AuditResult;
use App\Models\Audit\AuditRun;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RunAuditCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed();
    }

    public function test_audit_run_completes_and_persists_nine_check_results(): void
    {
        $this->artisan('audit:run')
            ->assertSuccessful()
            ->expectsOutputToContain('Audit run #');

        $auditRun = AuditRun::query()->latest('id')->first();

        $this->assertNotNull($auditRun);
        $this->assertSame('completed', $auditRun->status);
        $this->assertSame(9, $auditRun->total_checks);
        $this->assertNotNull($auditRun->started_at);
        $this->assertNotNull($auditRun->finished_at);

        $results = AuditResult::query()
            ->where('audit_run_id', $auditRun->id)
            ->orderBy('id')
            ->get();

        $this->assertCount(9, $results);

        $expectedKeys = [
            'tb_imbalance',
            'journal_imbalance',
            'duplicate_posting',
            'reversal_wrong_account',
            'non_postable_parent',
            'backdated_journal',
            'negative_cash',
            'reconciliation_gap',
            'orphaned_match_group',
        ];

        $this->assertSame($expectedKeys, $results->pluck('check_key')->all());

        foreach ($results as $result) {
            $this->assertContains($result->status, ['pass', 'fail']);
        }

        $this->assertSame(
            $auditRun->passed_checks + $auditRun->failed_checks,
            $auditRun->total_checks
        );
    }
}
