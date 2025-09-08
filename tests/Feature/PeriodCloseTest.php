<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Services\Accounting\PostingService;

class PeriodCloseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed();
    }

    public function test_posting_into_closed_period_is_blocked(): void
    {
        $service = app(PostingService::class);
        $date = now()->startOfMonth()->toDateString();
        // close period
        DB::table('periods')->updateOrInsert(
            ['year' => (int) date('Y'), 'month' => (int) date('n')],
            ['is_closed' => true, 'closed_at' => now(), 'created_at' => now(), 'updated_at' => now()]
        );

        $this->expectException(\RuntimeException::class);
        $service->postJournal([
            'date' => $date,
            'source_type' => 'test',
            'source_id' => 99,
            'lines' => [
                ['account_id' => $this->accountId('1.1.2.01'), 'debit' => 100, 'credit' => 0],
                ['account_id' => $this->accountId('4.1.1'), 'debit' => 0, 'credit' => 100],
            ],
        ]);
    }

    private function accountId(string $code): int
    {
        return (int) DB::table('accounts')->where('code', $code)->value('id');
    }
}
