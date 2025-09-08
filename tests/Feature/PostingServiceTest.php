<?php

namespace Tests\Feature;

use App\Services\Accounting\PostingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PostingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed();
    }

    public function test_post_balanced_journal_creates_records(): void
    {
        $service = app(PostingService::class);
        $payload = [
            'date' => now()->toDateString(),
            'description' => 'Test',
            'source_type' => 'test',
            'source_id' => 1,
            'lines' => [
                ['account_id' => $this->accountId('1.1.2.01'), 'debit' => 100, 'credit' => 0],
                ['account_id' => $this->accountId('4.1.1'), 'debit' => 0, 'credit' => 100],
            ],
        ];
        $jid = $service->postJournal($payload);
        $this->assertDatabaseHas('journals', ['id' => $jid, 'description' => 'Test']);
        $this->assertMatchesRegularExpression('/^JNL-\d{6}-\d{6}$/', DB::table('journals')->where('id', $jid)->value('journal_no'));
        $lines = DB::table('journal_lines')->where('journal_id', $jid)->count();
        $this->assertSame(2, $lines);
    }

    public function test_unbalanced_journal_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $service = app(PostingService::class);
        $service->postJournal([
            'date' => now()->toDateString(),
            'source_type' => 'test',
            'source_id' => 1,
            'lines' => [
                ['account_id' => $this->accountId('1.1.2.01'), 'debit' => 100, 'credit' => 0],
            ],
        ]);
    }

    public function test_reversal_creates_opposite_entries(): void
    {
        $service = app(PostingService::class);
        $jid = $service->postJournal([
            'date' => now()->toDateString(),
            'source_type' => 'test',
            'source_id' => 2,
            'lines' => [
                ['account_id' => $this->accountId('1.1.2.01'), 'debit' => 50, 'credit' => 0],
                ['account_id' => $this->accountId('4.1.1'), 'debit' => 0, 'credit' => 50],
            ],
        ]);
        $rid = $service->reverseJournal($jid, now()->toDateString());
        $this->assertNotEquals($jid, $rid);
        $this->assertDatabaseHas('journals', ['id' => $rid]);
    }

    public function test_negative_amount_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $service = app(PostingService::class);
        $service->postJournal([
            'date' => now()->toDateString(),
            'source_type' => 'test',
            'source_id' => 3,
            'lines' => [
                ['account_id' => $this->accountId('1.1.2.01'), 'debit' => -10, 'credit' => 0],
                ['account_id' => $this->accountId('4.1.1'), 'debit' => 0, 'credit' => 10],
            ],
        ]);
    }

    public function test_zero_debit_and_credit_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $service = app(PostingService::class);
        $service->postJournal([
            'date' => now()->toDateString(),
            'source_type' => 'test',
            'source_id' => 4,
            'lines' => [
                ['account_id' => $this->accountId('1.1.2.01'), 'debit' => 0, 'credit' => 0],
                ['account_id' => $this->accountId('4.1.1'), 'debit' => 0, 'credit' => 0],
            ],
        ]);
    }

    public function test_dimensions_preserved_in_post_and_reversal(): void
    {
        $service = app(PostingService::class);
        $projectId = (int) DB::table('projects')->value('id');
        $fundId = (int) DB::table('funds')->value('id');
        $deptId = (int) DB::table('departments')->value('id');

        $jid = $service->postJournal([
            'date' => now()->toDateString(),
            'description' => 'With dimensions',
            'source_type' => 'test',
            'source_id' => 5,
            'lines' => [
                [
                    'account_id' => $this->accountId('1.1.2.01'),
                    'debit' => 75,
                    'credit' => 0,
                    'project_id' => $projectId,
                    'fund_id' => $fundId,
                    'dept_id' => $deptId,
                ],
                [
                    'account_id' => $this->accountId('4.1.1'),
                    'debit' => 0,
                    'credit' => 75,
                    'project_id' => $projectId,
                    'fund_id' => $fundId,
                    'dept_id' => $deptId,
                ],
            ],
        ]);

        $orig = DB::table('journal_lines')->where('journal_id', $jid)->get();
        $this->assertCount(2, $orig);
        foreach ($orig as $l) {
            $this->assertSame($projectId, (int) $l->project_id);
            $this->assertSame($fundId, (int) $l->fund_id);
            $this->assertSame($deptId, (int) $l->dept_id);
        }

        $rid = $service->reverseJournal($jid, now()->toDateString());
        $rev = DB::table('journal_lines')->where('journal_id', $rid)->get();
        $this->assertCount(2, $rev);
        foreach ($rev as $l) {
            $this->assertSame($projectId, (int) $l->project_id);
            $this->assertSame($fundId, (int) $l->fund_id);
            $this->assertSame($deptId, (int) $l->dept_id);
        }
    }

    private function accountId(string $code): int
    {
        return (int) DB::table('accounts')->where('code', $code)->value('id');
    }
}
