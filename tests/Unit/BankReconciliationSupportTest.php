<?php

namespace Tests\Unit;

use App\Services\Bank\BankReconciliationSupport;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BankReconciliationSupportTest extends TestCase
{
    public function test_line_hash_is_stable(): void
    {
        $hash = BankReconciliationSupport::lineHash(
            '2026-01-06',
            'credit',
            482850.00,
            'REF123',
            'Payment received',
        );

        $this->assertSame(64, strlen($hash));
        $this->assertSame($hash, BankReconciliationSupport::lineHash(
            '2026-01-06',
            'credit',
            482850.00,
            'REF123',
            'Payment received',
        ));
    }

    #[DataProvider('directionProvider')]
    public function test_statement_direction_maps_to_book_side(string $statementDirection, string $expectedBookSide): void
    {
        $this->assertSame($expectedBookSide, BankReconciliationSupport::statementDirectionToBookSide($statementDirection));
    }

    public static function directionProvider(): array
    {
        return [
            ['credit', 'debit'],
            ['debit', 'credit'],
        ];
    }

    public function test_book_line_matches_statement_direction(): void
    {
        $creditBookLine = (object) ['debit' => 100.0, 'credit' => 0.0];
        $debitBookLine = (object) ['debit' => 0.0, 'credit' => 100.0];

        $this->assertTrue(BankReconciliationSupport::bookLineMatchesStatementDirection($creditBookLine, 'credit'));
        $this->assertTrue(BankReconciliationSupport::bookLineMatchesStatementDirection($debitBookLine, 'debit'));
        $this->assertFalse(BankReconciliationSupport::bookLineMatchesStatementDirection($creditBookLine, 'debit'));
    }

    public function test_suggest_counter_account_for_bank_fees(): void
    {
        $this->assertSame('5.1.1.01', BankReconciliationSupport::suggestCounterAccountCode('Biaya Adm 14903'));
        $this->assertSame('4.1.1.01', BankReconciliationSupport::suggestCounterAccountCode('Bunga 14903'));
    }
}
