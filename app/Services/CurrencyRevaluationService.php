<?php

namespace App\Services;

use App\Models\Currency;
use App\Models\CurrencyRevaluation;
use App\Models\CurrencyRevaluationLine;
use App\Models\ExchangeRate;
use App\Models\ErpParameter;
use App\Services\Accounting\PostingService;
use Illuminate\Support\Facades\DB;

class CurrencyRevaluationService
{
    public function __construct(
        private PostingService $postingService
    ) {}

    /**
     * Calculate revaluation for a currency
     */
    public function calculateRevaluation($currency, $revaluationDate): array
    {
        if ($currency instanceof Currency) {
            $currency = $currency->id;
        }

        $baseCurrency = Currency::getBaseCurrency();
        $currentRate = ExchangeRate::getLatestRate($baseCurrency->id, $currency, $revaluationDate);

        if (!$currentRate) {
            throw new \Exception("No exchange rate available for currency {$currency} on {$revaluationDate}");
        }

        $openBalances = $this->getOpenBalances($currency, $revaluationDate);
        $revaluationLines = [];
        $totalGain = 0;
        $totalLoss = 0;

        foreach ($openBalances as $balance) {
            $originalIdrAmount = $balance['original_amount'] * $balance['original_exchange_rate'];
            $newIdrAmount = $balance['original_amount'] * $currentRate->rate;
            $gainLoss = $newIdrAmount - $originalIdrAmount;

            $revaluationLines[] = [
                'account_id' => $balance['account_id'],
                'business_partner_id' => $balance['business_partner_id'] ?? null,
                'document_type' => $balance['document_type'] ?? null,
                'document_id' => $balance['document_id'] ?? null,
                'original_amount' => $balance['original_amount'],
                'original_currency_id' => $currency,
                'original_exchange_rate' => $balance['original_exchange_rate'],
                'revaluation_amount' => $newIdrAmount,
                'revaluation_exchange_rate' => $currentRate->rate,
                'unrealized_gain_loss' => $gainLoss,
            ];

            if ($gainLoss > 0) {
                $totalGain += $gainLoss;
            } else {
                $totalLoss += abs($gainLoss);
            }
        }

        return [
            'currency_id' => $currency,
            'revaluation_date' => $revaluationDate,
            'reference_rate_id' => $currentRate->id,
            'total_unrealized_gain' => $totalGain,
            'total_unrealized_loss' => $totalLoss,
            'lines' => $revaluationLines,
            'line_count' => count($revaluationLines),
        ];
    }

    /**
     * Get open balances for revaluation
     */
    public function getOpenBalances($currency, $asOfDate): array
    {
        $baseCurrency = Currency::getBaseCurrency();

        if (!$baseCurrency) {
            throw new \Exception('Base currency not found');
        }

        if ($currency == $baseCurrency->id) {
            return []; // No revaluation needed for base currency
        }

        $balances = [];

        // Get outstanding Purchase Invoices
        $purchaseInvoices = DB::table('purchase_invoices')
            ->join('business_partners', 'purchase_invoices.business_partner_id', '=', 'business_partners.id')
            ->where('purchase_invoices.currency_id', $currency)
            ->where('purchase_invoices.status', '!=', 'paid')
            ->select(
                'purchase_invoices.id as document_id',
                'business_partners.account_id',
                'business_partners.id as business_partner_id',
                'purchase_invoices.total_amount_foreign as original_amount',
                'purchase_invoices.exchange_rate as original_exchange_rate'
            )
            ->get();

        foreach ($purchaseInvoices as $invoice) {
            $balances[] = [
                'account_id' => $invoice->account_id,
                'business_partner_id' => $invoice->business_partner_id,
                'document_type' => 'purchase_invoice',
                'document_id' => $invoice->document_id,
                'original_amount' => $invoice->original_amount,
                'original_exchange_rate' => $invoice->original_exchange_rate,
            ];
        }

        // Get outstanding Sales Invoices
        $salesInvoices = DB::table('sales_invoices')
            ->join('business_partners', 'sales_invoices.business_partner_id', '=', 'business_partners.id')
            ->where('sales_invoices.currency_id', $currency)
            ->where('sales_invoices.status', '!=', 'paid')
            ->select(
                'sales_invoices.id as document_id',
                'business_partners.account_id',
                'business_partners.id as business_partner_id',
                'sales_invoices.total_amount_foreign as original_amount',
                'sales_invoices.exchange_rate as original_exchange_rate'
            )
            ->get();

        foreach ($salesInvoices as $invoice) {
            $balances[] = [
                'account_id' => $invoice->account_id,
                'business_partner_id' => $invoice->business_partner_id,
                'document_type' => 'sales_invoice',
                'document_id' => $invoice->document_id,
                'original_amount' => $invoice->original_amount,
                'original_exchange_rate' => $invoice->original_exchange_rate,
            ];
        }

        // Get foreign currency bank accounts
        $bankAccounts = DB::table('bank_accounts')
            ->where('currency', '!=', $baseCurrency->code)
            ->where('currency', Currency::find($currency)->code ?? '')
            ->select('id as document_id', 'id as account_id')
            ->get();

        foreach ($bankAccounts as $account) {
            // Calculate current balance for bank account
            $balance = DB::table('bank_transactions')
                ->where('bank_account_id', $account->document_id)
                ->where('transaction_date', '<=', $asOfDate)
                ->sum(DB::raw('CASE WHEN transaction_type = "debit" THEN amount ELSE -amount END'));

            if ($balance != 0) {
                // Get the last exchange rate used for this bank account
                $lastTransaction = DB::table('bank_transactions')
                    ->where('bank_account_id', $account->document_id)
                    ->where('transaction_date', '<=', $asOfDate)
                    ->orderBy('transaction_date', 'desc')
                    ->orderBy('id', 'desc')
                    ->first();

                $balances[] = [
                    'account_id' => $account->account_id,
                    'business_partner_id' => null,
                    'document_type' => 'bank_account',
                    'document_id' => $account->document_id,
                    'original_amount' => abs($balance),
                    'original_exchange_rate' => $lastTransaction->exchange_rate ?? 1,
                ];
            }
        }

        return $balances;
    }

    /**
     * Create revaluation document
     */
    public function createRevaluation($currency, $revaluationDate, $notes = null, $revaluedBy = null): CurrencyRevaluation
    {
        $calculation = $this->calculateRevaluation($currency, $revaluationDate);

        if (empty($calculation['lines'])) {
            throw new \Exception('No open balances found for revaluation');
        }

        $revaluation = CurrencyRevaluation::createRevaluation(
            1, // Base currency ID (IDR)
            $revaluationDate,
            $calculation['reference_rate_id'],
            $notes,
            $revaluedBy
        );

        // Create revaluation lines
        foreach ($calculation['lines'] as $lineData) {
            $lineData['revaluation_id'] = $revaluation->id;
            CurrencyRevaluationLine::create($lineData);
        }

        // Update totals
        $revaluation->update([
            'total_unrealized_gain' => $calculation['total_unrealized_gain'],
            'total_unrealized_loss' => $calculation['total_unrealized_loss'],
        ]);

        return $revaluation->fresh(['lines', 'currency', 'referenceRate']);
    }

    /**
     * Post revaluation to GL
     */
    public function postRevaluation($revaluationId): \App\Models\Accounting\Journal
    {
        $revaluation = CurrencyRevaluation::with(['lines.account', 'currency', 'referenceRate'])->findOrFail($revaluationId);

        if (!$revaluation->canBePosted()) {
            throw new \Exception('Revaluation cannot be posted');
        }

        $baseCurrency = Currency::getBaseCurrency();

        if (!$baseCurrency) {
            throw new \Exception('Base currency not found');
        }

        // Get FX gain/loss accounts from ERP parameters
        $unrealizedGainAccountId = ErpParameter::where('parameter_key', 'unrealized_gain_loss_account_id')->first()?->parameter_value;

        if (!$unrealizedGainAccountId) {
            throw new \Exception('Unrealized FX gain/loss account not configured');
        }

        $journalLines = [];
        $totalDebit = 0;
        $totalCredit = 0;

        // Create journal lines for each revaluation line
        foreach ($revaluation->lines as $line) {
            if ($line->unrealized_gain_loss != 0) {
                // Debit/Credit the account being revalued
                $accountDebit = 0;
                $accountCredit = 0;

                if ($line->unrealized_gain_loss > 0) {
                    $accountDebit = $line->unrealized_gain_loss;
                } else {
                    $accountCredit = abs($line->unrealized_gain_loss);
                }

                $journalLines[] = [
                    'account_id' => $line->account_id,
                    'debit' => $accountDebit,
                    'credit' => $accountCredit,
                    'project_id' => null,
                    'dept_id' => null,
                    'memo' => "Currency revaluation - {$revaluation->revaluation_no}",
                ];

                // Credit/Debit the unrealized gain/loss account
                $journalLines[] = [
                    'account_id' => $unrealizedGainAccountId,
                    'debit' => $accountCredit,
                    'credit' => $accountDebit,
                    'project_id' => null,
                    'dept_id' => null,
                    'memo' => "Currency revaluation - {$revaluation->revaluation_no}",
                ];

                $totalDebit += $accountDebit + $accountCredit;
                $totalCredit += $accountDebit + $accountCredit;
            }
        }

        if (empty($journalLines)) {
            throw new \Exception('No journal lines to post');
        }

        // Post journal
        $payload = [
            'date' => $revaluation->revaluation_date,
            'description' => "Currency Revaluation - {$revaluation->revaluation_no} ({$revaluation->currency->code})",
            'source_type' => 'currency_revaluation',
            'source_id' => $revaluation->id,
            'posted_by' => auth()->id(),
            'lines' => $journalLines,
        ];

        $journalId = $this->postingService->postJournal($payload);
        $journal = \App\Models\Accounting\Journal::findOrFail($journalId);

        // Update revaluation status
        $revaluation->update([
            'status' => 'posted',
            'journal_id' => $journalId,
            'posted_by' => auth()->id(),
            'posted_at' => now(),
        ]);

        return $journal;
    }

    /**
     * Reverse posted revaluation
     */
    public function reverseRevaluation($revaluationId): \App\Models\Accounting\Journal
    {
        $revaluation = CurrencyRevaluation::findOrFail($revaluationId);

        if (!$revaluation->canBeReversed()) {
            throw new \Exception('Revaluation cannot be reversed');
        }

        // Reverse the journal
        $reversalJournal = $this->postingService->reverseJournal(
            $revaluation->journal_id,
            now()->toDateString(),
            auth()->id()
        );

        // Update revaluation status
        $revaluation->update([
            'status' => 'draft',
            'journal_id' => null,
            'posted_by' => null,
            'posted_at' => null,
        ]);

        return \App\Models\Accounting\Journal::findOrFail($reversalJournal);
    }

    /**
     * Get revaluation statistics
     */
    public function getRevaluationStats(): array
    {
        return [
            'total_revaluations' => CurrencyRevaluation::count(),
            'draft_revaluations' => CurrencyRevaluation::draft()->count(),
            'posted_revaluations' => CurrencyRevaluation::posted()->count(),
            'revaluations_this_month' => CurrencyRevaluation::whereMonth('revaluation_date', now()->month)
                ->whereYear('revaluation_date', now()->year)
                ->count(),
            'total_unrealized_gain' => CurrencyRevaluation::posted()->sum('total_unrealized_gain'),
            'total_unrealized_loss' => CurrencyRevaluation::posted()->sum('total_unrealized_loss'),
        ];
    }

    /**
     * Get revaluation history for currency
     */
    public function getRevaluationHistory($currencyId, $startDate = null, $endDate = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = CurrencyRevaluation::forCurrency($currencyId);

        if ($startDate) {
            $query->where('revaluation_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('revaluation_date', '<=', $endDate);
        }

        return $query->with(['currency', 'referenceRate', 'journal'])
            ->orderBy('revaluation_date', 'desc')
            ->get();
    }
}
