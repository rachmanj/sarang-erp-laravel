<?php

namespace App\Services\Bank;

class BankReconciliationSupport
{
    public static function lineHash(
        string $postingDate,
        string $direction,
        float $amount,
        ?string $referenceNo,
        ?string $description
    ): string {
        $payload = implode('|', [
            $postingDate,
            $direction,
            number_format($amount, 2, '.', ''),
            trim((string) $referenceNo),
            trim((string) $description),
        ]);

        return hash('sha256', $payload);
    }

    public static function statementDirectionToBookSide(string $direction): string
    {
        return $direction === 'credit' ? 'debit' : 'credit';
    }

    public static function bookLineMatchesStatementDirection(object $bookLine, string $statementDirection): bool
    {
        $bookSide = self::statementDirectionToBookSide($statementDirection);

        if ($bookSide === 'debit') {
            return (float) $bookLine->debit > 0 && (float) $bookLine->credit == 0.0;
        }

        return (float) $bookLine->credit > 0 && (float) $bookLine->debit == 0.0;
    }

    public static function bookLineAmount(object $bookLine): float
    {
        $debit = (float) $bookLine->debit;
        $credit = (float) $bookLine->credit;

        return $debit > 0 ? $debit : $credit;
    }

    public static function suggestCounterAccountCode(string $description): ?string
    {
        $text = strtolower($description);

        if (str_contains($text, 'bunga') || str_contains($text, 'credit interest')) {
            return '4.1.1.01';
        }

        if (str_contains($text, 'pajak') || str_contains($text, 'withholding tax')) {
            return '2.1.3.01';
        }

        if (str_contains($text, 'biaya adm') || str_contains($text, 'admin fee') || str_contains($text, 'monthly card')) {
            return '5.1.1.01';
        }

        return null;
    }
}
