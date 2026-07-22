<?php

namespace Tests\Feature;

use App\Models\Bank\BankAccount;
use App\Models\Bank\BankBookLine;
use App\Models\Bank\BankStatementLine;
use App\Services\Bank\BankReconciliationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BankReconciliationCarryForwardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed();
    }

    public function test_outstanding_book_line_carries_into_next_month(): void
    {
        $bankCoaId = $this->ensureBankCoa();
        $bankAccount = BankAccount::create([
            'code' => 'BNK-CF',
            'name' => 'Carry Forward Test',
            'bank_name' => 'BCA',
            'account_number' => '444555666',
            'currency' => 'IDR',
            'account_id' => $bankCoaId,
            'is_active' => true,
        ]);

        $service = app(BankReconciliationService::class);
        $january = $service->createManualSession($bankAccount, '2026-01-01');

        $bookLine = BankBookLine::create([
            'bank_reconciliation_id' => $january->id,
            'journal_line_id' => null,
            'posting_date' => '2026-01-30',
            'description' => 'Unpresented cheque',
            'debit' => 0,
            'credit' => 2500,
            'match_status' => BankBookLine::MATCH_UNMATCHED,
        ]);

        $service->setBookLineOutstanding($january, $bookLine, true, 'Outstanding cheque');
        $service->updateStatementBalances($january, 50000, 50000);
        $january->update([
            'opening_balance_book' => 50000,
            'closing_balance_book' => 47500,
            'book_balance' => 47500,
            'status' => 'completed',
            'finalized_at' => now(),
        ]);

        $february = $service->createManualSession($bankAccount, '2026-02-01');

        $this->assertDatabaseHas('bank_book_lines', [
            'bank_reconciliation_id' => $february->id,
            'is_carried_forward' => 1,
            'carried_from_book_line_id' => $bookLine->id,
            'credit' => 2500,
            'match_status' => BankBookLine::MATCH_UNMATCHED,
        ]);

        $bankLine = BankStatementLine::create([
            'bank_reconciliation_id' => $february->id,
            'posting_date' => '2026-02-03',
            'description' => 'Cheque cleared',
            'amount' => 2500,
            'direction' => 'debit',
            'debit' => 2500,
            'credit' => 0,
            'match_status' => BankStatementLine::MATCH_UNMATCHED,
            'line_hash' => hash('sha256', 'cf-clear'),
        ]);

        app(\App\Services\Bank\ReconciliationMatchingService::class)->manualMatch(
            $february,
            [$bankLine->id],
            [$february->bookLines()->where('is_carried_forward', true)->first()->id],
        );

        $this->assertDatabaseHas('reconciliation_match_groups', [
            'bank_reconciliation_id' => $february->id,
            'match_type' => 'manual',
        ]);
    }

    private function ensureBankCoa(): int
    {
        $id = (int) DB::table('accounts')->where('code', '1.1.1.02')->value('id');
        if ($id) {
            return $id;
        }

        return DB::table('accounts')->insertGetId([
            'code' => '1.1.1.02',
            'name' => 'Kas di Bank - Operasional',
            'type' => 'asset',
            'is_postable' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
