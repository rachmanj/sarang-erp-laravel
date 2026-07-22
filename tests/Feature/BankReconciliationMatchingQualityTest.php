<?php

namespace Tests\Feature;

use App\Models\Bank\BankAccount;
use App\Models\Bank\BankBookLine;
use App\Models\Bank\BankStatementLine;
use App\Services\Bank\BankReconciliationService;
use App\Services\Bank\ReconciliationMatchingService;
use App\Services\Bank\ReconciliationSnapshotIntegrityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BankReconciliationMatchingQualityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed();
    }

    public function test_reference_match_takes_priority(): void
    {
        $bankCoaId = $this->ensureBankCoa();
        $bankAccount = BankAccount::create([
            'code' => 'BNK-REF',
            'name' => 'Reference Match',
            'bank_name' => 'Mandiri',
            'account_number' => '121212',
            'currency' => 'IDR',
            'account_id' => $bankCoaId,
            'is_active' => true,
        ]);

        $reconciliation = app(BankReconciliationService::class)->createManualSession($bankAccount, '2026-08-01');

        BankStatementLine::create([
            'bank_reconciliation_id' => $reconciliation->id,
            'posting_date' => '2026-08-05',
            'description' => 'Transfer',
            'reference_no' => 'TRX998877',
            'amount' => 1000,
            'direction' => 'credit',
            'debit' => 0,
            'credit' => 1000,
            'match_status' => BankStatementLine::MATCH_UNMATCHED,
            'line_hash' => hash('sha256', 'ref-bank'),
        ]);

        BankBookLine::create([
            'bank_reconciliation_id' => $reconciliation->id,
            'posting_date' => '2026-08-10',
            'description' => 'Unrelated same amount',
            'debit' => 1000,
            'credit' => 0,
            'match_status' => BankBookLine::MATCH_UNMATCHED,
        ]);

        BankBookLine::create([
            'bank_reconciliation_id' => $reconciliation->id,
            'posting_date' => '2026-08-06',
            'description' => 'Receipt TRX998877 from customer',
            'debit' => 1000,
            'credit' => 0,
            'match_status' => BankBookLine::MATCH_UNMATCHED,
        ]);

        $matched = app(ReconciliationMatchingService::class)->autoMatch($reconciliation);

        $this->assertSame(1, $matched);
        $this->assertDatabaseHas('reconciliation_match_groups', [
            'bank_reconciliation_id' => $reconciliation->id,
            'match_type' => 'auto_reference',
        ]);
        $this->assertDatabaseHas('reconciliation_match_audits', [
            'bank_reconciliation_id' => $reconciliation->id,
            'action' => 'auto_match',
        ]);
    }

    public function test_snapshot_integrity_flags_changed_journal_line(): void
    {
        $bankCoaId = $this->ensureBankCoa();
        $arId = (int) DB::table('accounts')->where('code', '1.1.2.01')->value('id');
        if (! $arId) {
            $arId = DB::table('accounts')->insertGetId([
                'code' => '1.1.2.01',
                'name' => 'Piutang Usaha',
                'type' => 'asset',
                'is_postable' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $bankAccount = BankAccount::create([
            'code' => 'BNK-STALE',
            'name' => 'Stale Test',
            'bank_name' => 'BCA',
            'account_number' => '343434',
            'currency' => 'IDR',
            'account_id' => $bankCoaId,
            'is_active' => true,
        ]);

        $reconciliation = app(BankReconciliationService::class)->createManualSession($bankAccount, '2026-09-01');

        $journalId = app(\App\Services\Accounting\PostingService::class)->postJournal([
            'date' => '2026-09-01',
            'description' => 'Receipt',
            'source_type' => 'manual_journal',
            'source_id' => 99,
            'lines' => [
                ['account_id' => $bankCoaId, 'debit' => 100, 'credit' => 0, 'memo' => 'Bank'],
                ['account_id' => $arId, 'debit' => 0, 'credit' => 100, 'memo' => 'AR'],
            ],
        ]);

        $journalLineId = (int) DB::table('journal_lines')
            ->where('journal_id', $journalId)
            ->where('account_id', $bankCoaId)
            ->value('id');

        BankBookLine::create([
            'bank_reconciliation_id' => $reconciliation->id,
            'journal_line_id' => $journalLineId,
            'posting_date' => '2026-09-01',
            'description' => 'Snapshot',
            'debit' => 100,
            'credit' => 0,
            'match_status' => BankBookLine::MATCH_UNMATCHED,
        ]);

        DB::table('journal_lines')->where('id', $journalLineId)->update(['debit' => 150]);

        $stale = app(ReconciliationSnapshotIntegrityService::class)->refreshStaleFlags($reconciliation);

        $this->assertSame(1, $stale);
        $this->assertDatabaseHas('bank_book_lines', [
            'bank_reconciliation_id' => $reconciliation->id,
            'is_stale' => 1,
            'stale_reason' => 'Source journal line amounts changed',
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
