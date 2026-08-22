<?php

namespace Tests\Feature;

use App\Models\Audit\AuditResult;
use App\Models\Audit\AuditRun;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed();
    }

    public function test_audit_index_requires_audit_view_permission(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get(route('audit.index'))->assertForbidden();
    }

    public function test_audit_index_lists_runs_with_stats(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('audit.view');
        $this->actingAs($user);

        $run = AuditRun::query()->create([
            'status' => 'completed',
            'started_at' => now()->subMinutes(5),
            'finished_at' => now(),
            'triggered_by' => 'test',
            'total_checks' => 2,
            'passed_checks' => 1,
            'failed_checks' => 1,
            'total_issues' => 3,
        ]);

        AuditResult::query()->create([
            'audit_run_id' => $run->id,
            'check_key' => 'tb_imbalance',
            'check_name' => 'TB Imbalance',
            'status' => 'fail',
            'issue_count' => 3,
            'details' => json_encode(['selisih=100'], JSON_UNESCAPED_UNICODE),
        ]);

        $response = $this->get(route('audit.index'));

        $response->assertOk();
        $response->assertSee('Total Audit Run');
        $response->assertSee('Isu pada Run Terakhir');
        $response->assertSee('Status Run Terakhir');
        $response->assertSee('Selesai');
        $response->assertSee((string) $run->id);
        $response->assertSee(route('audit.show', $run), false);
    }

    public function test_audit_show_displays_check_results_and_issues(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('audit.view');
        $this->actingAs($user);

        $run = AuditRun::query()->create([
            'status' => 'completed',
            'started_at' => now()->subMinutes(5),
            'finished_at' => now(),
            'triggered_by' => 'artisan',
            'total_checks' => 1,
            'passed_checks' => 0,
            'failed_checks' => 1,
            'total_issues' => 1,
        ]);

        AuditResult::query()->create([
            'audit_run_id' => $run->id,
            'check_key' => 'journal_imbalance',
            'check_name' => 'Journal Imbalance',
            'status' => 'fail',
            'issue_count' => 1,
            'details' => json_encode(['id=99, journal_no=J-001'], JSON_UNESCAPED_UNICODE),
        ]);

        $response = $this->get(route('audit.show', $run));

        $response->assertOk();
        $response->assertSee('Ringkasan Audit Run #'.$run->id);
        $response->assertSee('journal_imbalance');
        $response->assertSee('Journal Imbalance');
        $response->assertSee('Gagal');
        $response->assertSee('id=99, journal_no=J-001');
    }

    public function test_audit_show_requires_audit_view_permission(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $run = AuditRun::query()->create([
            'status' => 'completed',
            'started_at' => now(),
            'finished_at' => now(),
            'triggered_by' => 'test',
            'total_checks' => 0,
            'passed_checks' => 0,
            'failed_checks' => 0,
            'total_issues' => 0,
        ]);

        $this->get(route('audit.show', $run))->assertForbidden();
    }
}
