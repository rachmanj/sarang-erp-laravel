<?php

namespace Tests\Feature;

use App\Models\Bank\BankAccount;
use App\Models\Bank\BankReconciliation;
use App\Models\Bank\BankStatement;
use App\Services\Bank\BankReconciliationOpenRouterClient;
use App\Services\Bank\BankStatementParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BankStatementParseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed();
    }

    public function test_parse_for_reconciliation_accepts_identical_transactions(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('bank-statements/duplicate-lines.pdf', '%PDF-1.4 duplicate test');

        $bankAccount = $this->createBankAccount();

        $statement = BankStatement::create([
            'bank_account_id' => $bankAccount->id,
            'period_start' => '2026-01-01',
            'period_end' => '2026-01-31',
            'opening_balance' => 1000,
            'closing_balance' => 900,
            'currency' => 'IDR',
            'original_filename' => 'mandiri-jan.pdf',
            'file_path' => 'bank-statements/duplicate-lines.pdf',
            'status' => 'imported',
        ]);

        $reconciliation = BankReconciliation::create([
            'bank_account_id' => $bankAccount->id,
            'bank_statement_id' => $statement->id,
            'periode' => '2026-01-01',
            'period_start' => '2026-01-01',
            'period_end' => '2026-01-31',
            'source_mode' => 'ai',
            'status' => BankReconciliation::STATUS_PROCESSING,
        ]);

        $duplicateLine = [
            'posting_date' => '2026-01-05',
            'value_date' => '2026-01-05',
            'description' => 'Transfer biaya admin',
            'reference_no' => 'TRX001',
            'amount' => 50.00,
            'direction' => 'debit',
            'running_balance' => 950.00,
        ];

        $parsedPayload = [
            'account_number' => $bankAccount->account_number,
            'period_start' => '2026-01-01',
            'period_end' => '2026-01-31',
            'opening_balance' => 1000.00,
            'closing_balance' => 900.00,
            'currency' => 'IDR',
            'lines' => [$duplicateLine, $duplicateLine],
        ];

        $this->mock(BankReconciliationOpenRouterClient::class, function ($mock) use ($parsedPayload): void {
            $mock->shouldReceive('chatCompletionWithPdf')
                ->once()
                ->andReturn([
                    'choices' => [
                        [
                            'message' => [
                                'content' => json_encode($parsedPayload, JSON_THROW_ON_ERROR),
                            ],
                        ],
                    ],
                ]);
        });

        $result = app(BankStatementParser::class)->parseForReconciliation($reconciliation->fresh());

        $this->assertSame(2, $result['lines_count']);
        $this->assertDatabaseCount('bank_statement_lines', 2);

        $hashes = DB::table('bank_statement_lines')
            ->where('bank_reconciliation_id', $reconciliation->id)
            ->pluck('line_hash');

        $this->assertCount(2, $hashes);
        $this->assertNotSame($hashes[0], $hashes[1]);
    }

    private function createBankAccount(): BankAccount
    {
        $bankCoaId = (int) DB::table('accounts')->where('code', '1.1.1.02')->value('id');
        if (! $bankCoaId) {
            $bankCoaId = DB::table('accounts')->insertGetId([
                'code' => '1.1.1.02',
                'name' => 'Kas di Bank',
                'type' => 'asset',
                'is_postable' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return BankAccount::create([
            'code' => 'BNK-PARSE',
            'name' => 'Parse Test Bank',
            'bank_name' => 'Mandiri',
            'account_number' => '800201845200',
            'currency' => 'IDR',
            'account_id' => $bankCoaId,
            'is_active' => true,
        ]);
    }
}
