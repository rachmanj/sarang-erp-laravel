<?php

namespace App\Services\Accounting;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use App\Services\Accounting\PeriodCloseService;
use App\Services\DocumentNumberingService;
use App\Services\ControlAccountService;
use App\Services\ExchangeRateService;

class PostingService
{
    public function __construct(
        private PeriodCloseService $periods,
        private DocumentNumberingService $documentNumberingService,
        private ControlAccountService $controlAccountService,
        private ExchangeRateService $exchangeRateService
    ) {}
    public function postJournal(array $payload): int
    {
        // Expected $payload keys: date, description, period_id|null, source_type, source_id, posted_by|null, lines[]
        // Each line: account_id, debit, credit, project_id|null, dept_id|null, memo|null
        // Multi-currency support: currency_id, exchange_rate, debit_foreign, credit_foreign
        $this->validatePayload($payload);
        $this->assertBalanced($payload['lines']);
        $this->validateMultiCurrency($payload['lines']);

        if ($this->periods->isDateClosed($payload['date'])) {
            throw new \RuntimeException('Cannot post to a closed period');
        }

        return DB::transaction(function () use ($payload) {
            // Determine journal currency
            $journalCurrency = $this->determineJournalCurrency($payload['lines']);

            $journalId = DB::table('journals')->insertGetId([
                'date' => $payload['date'],
                'description' => $payload['description'] ?? null,
                'period_id' => $payload['period_id'] ?? null,
                'source_type' => $payload['source_type'],
                'source_id' => $payload['source_id'],
                'posted_by' => $payload['posted_by'] ?? null,
                'currency_id' => $journalCurrency['currency_id'],
                'exchange_rate' => $journalCurrency['exchange_rate'],
                'posted_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Generate journal number using centralized service
            $journalNo = $this->documentNumberingService->generateNumber('journal', $payload['date']);
            DB::table('journals')->where('id', $journalId)->update(['journal_no' => $journalNo]);

            $linesInsert = [];
            foreach ($payload['lines'] as $l) {
                $lineCurrency = $this->processLineCurrency($l, $payload['date']);

                $linesInsert[] = [
                    'journal_id' => $journalId,
                    'account_id' => $l['account_id'],
                    'debit' => (float)($l['debit'] ?? 0),
                    'credit' => (float)($l['credit'] ?? 0),
                    'currency_id' => $lineCurrency['currency_id'],
                    'exchange_rate' => $lineCurrency['exchange_rate'],
                    'debit_foreign' => $lineCurrency['debit_foreign'],
                    'credit_foreign' => $lineCurrency['credit_foreign'],
                    'project_id' => empty($l['project_id']) ? null : $l['project_id'],
                    'dept_id' => empty($l['dept_id']) ? null : $l['dept_id'],
                    'memo' => $l['memo'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            DB::table('journal_lines')->insert($linesInsert);

            // Update control account balances for each line
            foreach ($payload['lines'] as $line) {
                $this->controlAccountService->updateBalanceOnJournalPost(
                    $line['account_id'],
                    (float)($line['debit'] ?? 0),
                    (float)($line['credit'] ?? 0),
                    empty($line['project_id']) ? null : $line['project_id'],
                    empty($line['dept_id']) ? null : $line['dept_id']
                );
            }

            return $journalId;
        });
    }

    public function reverseJournal(int $journalId, ?string $date = null, ?int $postedBy = null): int
    {
        $date = $date ?: now()->toDateString();

        $journal = DB::table('journals')->where('id', $journalId)->first();
        if (!$journal) {
            throw new \InvalidArgumentException('Journal not found');
        }

        $lines = DB::table('journal_lines')->where('journal_id', $journalId)->get();
        if ($lines->isEmpty()) {
            throw new \RuntimeException('Cannot reverse empty journal');
        }

        $payload = [
            'date' => $date,
            'description' => 'Reversal of #' . $journalId . ($journal->description ? ' - ' . $journal->description : ''),
            'period_id' => $journal->period_id,
            'source_type' => $journal->source_type,
            'source_id' => $journal->source_id,
            'posted_by' => $postedBy,
            'lines' => [],
        ];

        foreach ($lines as $l) {
            $payload['lines'][] = [
                'account_id' => $l->account_id,
                'debit' => (float)$l->credit,
                'credit' => (float)$l->debit,
                'project_id' => $l->project_id,
                'dept_id' => $l->dept_id,
                'memo' => 'Reversal of line ' . $l->id,
            ];
        }

        return $this->postJournal($payload);
    }

    private function validatePayload(array $payload): void
    {
        foreach (['date', 'source_type', 'source_id', 'lines'] as $key) {
            if (!array_key_exists($key, $payload)) {
                throw new \InvalidArgumentException("Missing required key: {$key}");
            }
        }
        if (!is_array($payload['lines']) || count($payload['lines']) === 0) {
            throw new \InvalidArgumentException('Journal must contain at least one line');
        }
        foreach ($payload['lines'] as $idx => $l) {
            if (empty($l['account_id'])) {
                throw new \InvalidArgumentException("Line {$idx} missing account_id");
            }
            $debit = (float)($l['debit'] ?? 0);
            $credit = (float)($l['credit'] ?? 0);
            if ($debit < 0 || $credit < 0) {
                throw new \InvalidArgumentException("Line {$idx} has negative amount");
            }
            if ($debit === 0.0 && $credit === 0.0) {
                throw new \InvalidArgumentException("Line {$idx} must have debit or credit");
            }
        }
    }

    private function assertBalanced(array $lines): void
    {
        $sumDebit = 0.0;
        $sumCredit = 0.0;
        foreach ($lines as $l) {
            $sumDebit += (float)($l['debit'] ?? 0);
            $sumCredit += (float)($l['credit'] ?? 0);
        }
        if (round($sumDebit - $sumCredit, 2) !== 0.0) {
            throw new \InvalidArgumentException('Journal is not balanced');
        }
    }

    private function validateMultiCurrency(array $lines): void
    {
        foreach ($lines as $idx => $line) {
            if (isset($line['currency_id']) && !isset($line['exchange_rate'])) {
                throw new \InvalidArgumentException("Line {$idx} has currency_id but missing exchange_rate");
            }
            if (isset($line['exchange_rate']) && !isset($line['currency_id'])) {
                throw new \InvalidArgumentException("Line {$idx} has exchange_rate but missing currency_id");
            }
            if (isset($line['debit_foreign']) && !isset($line['currency_id'])) {
                throw new \InvalidArgumentException("Line {$idx} has debit_foreign but missing currency_id");
            }
            if (isset($line['credit_foreign']) && !isset($line['currency_id'])) {
                throw new \InvalidArgumentException("Line {$idx} has credit_foreign but missing currency_id");
            }
        }
    }

    private function determineJournalCurrency(array $lines): array
    {
        // Get base currency dynamically
        $baseCurrency = \App\Models\Currency::getBaseCurrency();
        $baseCurrencyId = $baseCurrency ? $baseCurrency->id : 1;
        $baseExchangeRate = 1.000000;

        // Check if all lines use the same currency
        $currencies = [];
        foreach ($lines as $line) {
            $currencyId = $line['currency_id'] ?? $baseCurrencyId;
            $currencies[$currencyId] = true;
        }

        // If only one currency is used, use that currency
        if (count($currencies) === 1) {
            $currencyId = array_key_first($currencies);
            if ($currencyId === $baseCurrencyId) {
                return ['currency_id' => $currencyId, 'exchange_rate' => $baseExchangeRate];
            }

            // Get exchange rate for non-base currency
            $exchangeRate = $this->exchangeRateService->getRate($currencyId, $baseCurrencyId, Carbon::parse($lines[0]['date'] ?? now()));
            return [
                'currency_id' => $currencyId,
                'exchange_rate' => $exchangeRate ? $exchangeRate->rate : $baseExchangeRate
            ];
        }

        // Mixed currency journal - use base currency
        return ['currency_id' => $baseCurrencyId, 'exchange_rate' => $baseExchangeRate];
    }

    private function processLineCurrency(array $line, string $date): array
    {
        // Get base currency dynamically
        $baseCurrency = \App\Models\Currency::getBaseCurrency();
        $baseCurrencyId = $baseCurrency ? $baseCurrency->id : 1;
        $baseExchangeRate = 1.000000;

        $currencyId = $line['currency_id'] ?? $baseCurrencyId;
        $exchangeRate = $line['exchange_rate'] ?? $baseExchangeRate;

        $debit = (float)($line['debit'] ?? 0);
        $credit = (float)($line['credit'] ?? 0);

        $debitForeign = $line['debit_foreign'] ?? 0;
        $creditForeign = $line['credit_foreign'] ?? 0;

        // If foreign amounts are provided, use them; otherwise calculate from IDR amounts
        if ($debitForeign > 0 || $creditForeign > 0) {
            // Foreign amounts provided - validate they match IDR amounts
            $calculatedDebitForeign = $debit / $exchangeRate;
            $calculatedCreditForeign = $credit / $exchangeRate;

            if (abs($debitForeign - $calculatedDebitForeign) > 0.01) {
                throw new \InvalidArgumentException("Debit foreign amount does not match calculated amount");
            }
            if (abs($creditForeign - $calculatedCreditForeign) > 0.01) {
                throw new \InvalidArgumentException("Credit foreign amount does not match calculated amount");
            }
        } else {
            // Calculate foreign amounts from IDR amounts
            $debitForeign = $currencyId === $baseCurrencyId ? $debit : $debit / $exchangeRate;
            $creditForeign = $currencyId === $baseCurrencyId ? $credit : $credit / $exchangeRate;
        }

        return [
            'currency_id' => $currencyId,
            'exchange_rate' => $exchangeRate,
            'debit_foreign' => $debitForeign,
            'credit_foreign' => $creditForeign,
        ];
    }

    public function postMultiCurrencyJournal(array $payload): int
    {
        // Enhanced version that handles FX gain/loss calculation
        // Expected additional keys: settlement_currency_id, settlement_exchange_rate, settlement_date

        if (!isset($payload['settlement_currency_id']) || !isset($payload['settlement_exchange_rate'])) {
            // Fall back to regular posting
            return $this->postJournal($payload);
        }

        // Calculate FX gain/loss
        $fxGainLoss = $this->calculateFxGainLoss($payload);

        if (abs($fxGainLoss) > 0.01) {
            // Add FX gain/loss line
            $fxAccountId = $fxGainLoss > 0 ?
                \App\Models\ErpParameter::get('realized_gain_loss_account_id', 0) :
                \App\Models\ErpParameter::get('realized_gain_loss_account_id', 0); // Use same account for both

            if ($fxAccountId > 0) {
                $payload['lines'][] = [
                    'account_id' => $fxAccountId,
                    'debit' => $fxGainLoss < 0 ? abs($fxGainLoss) : 0,
                    'credit' => $fxGainLoss > 0 ? $fxGainLoss : 0,
                    'currency_id' => $payload['settlement_currency_id'],
                    'exchange_rate' => $payload['settlement_exchange_rate'],
                    'memo' => 'FX Gain/Loss on Settlement',
                ];
            }
        }

        return $this->postJournal($payload);
    }

    private function calculateFxGainLoss(array $payload): float
    {
        $originalTotal = 0;
        $settlementTotal = 0;

        foreach ($payload['lines'] as $line) {
            $debit = (float)($line['debit'] ?? 0);
            $credit = (float)($line['credit'] ?? 0);
            $originalTotal += ($debit - $credit);
        }

        // Convert settlement total using settlement exchange rate
        $settlementTotal = $originalTotal / $payload['settlement_exchange_rate'];

        // Calculate FX gain/loss
        return $originalTotal - $settlementTotal;
    }
}
