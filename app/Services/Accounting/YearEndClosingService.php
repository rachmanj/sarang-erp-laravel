<?php

namespace App\Services\Accounting;

use App\Services\Reports\ReportService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class YearEndClosingService
{
    public function __construct(
        private PostingService $postingService,
        private ReportService $reportService,
        private ChartOfAccountsService $chartOfAccounts
    ) {}

    public function closeFiscalYear(int $year, ?int $postedBy = null): int
    {
        $from = sprintf('%04d-01-01', $year);
        $to = sprintf('%04d-12-31', $year);

        $pnl = $this->reportService->getProfitAndLoss(['from' => $from, 'to' => $to], true, false);
        $netIncome = (float) ($pnl['subtotals']['net_income'] ?? 0);

        if (abs($netIncome) < 0.01) {
            return 0;
        }

        $currentYearEarningsId = $this->chartOfAccounts->accountIdByCode('3.3.2');
        $lines = [];

        $accounts = DB::table('journal_lines as jl')
            ->join('journals as j', 'j.id', '=', 'jl.journal_id')
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->whereIn('a.type', ['income', 'expense'])
            ->whereDate('j.date', '>=', $from)
            ->whereDate('j.date', '<=', $to)
            ->whereNotNull('j.posted_at')
            ->selectRaw('a.id, a.type, SUM(jl.debit) as debit, SUM(jl.credit) as credit')
            ->groupBy('a.id', 'a.type')
            ->get();

        foreach ($accounts as $row) {
            $amount = match ($row->type) {
                'income' => round((float) $row->credit - (float) $row->debit, 2),
                'expense' => round((float) $row->debit - (float) $row->credit, 2),
                default => 0.0,
            };

            if (abs($amount) < 0.01) {
                continue;
            }

            if ($row->type === 'income') {
                $lines[] = [
                    'account_id' => (int) $row->id,
                    'debit' => $amount,
                    'credit' => 0,
                    'memo' => "Year-end close {$year} - close income",
                ];
            } else {
                $lines[] = [
                    'account_id' => (int) $row->id,
                    'debit' => 0,
                    'credit' => $amount,
                    'memo' => "Year-end close {$year} - close expense",
                ];
            }
        }

        if ($netIncome > 0) {
            $lines[] = [
                'account_id' => $currentYearEarningsId,
                'debit' => 0,
                'credit' => $netIncome,
                'memo' => "Year-end close {$year} - net income",
            ];
        } else {
            $lines[] = [
                'account_id' => $currentYearEarningsId,
                'debit' => abs($netIncome),
                'credit' => 0,
                'memo' => "Year-end close {$year} - net loss",
            ];
        }

        return $this->postingService->postJournal([
            'date' => $to,
            'description' => "Year-end closing journal {$year}",
            'source_type' => 'year_end_close',
            'source_id' => $year,
            'posted_by' => $postedBy ?? Auth::id(),
            'lines' => $lines,
        ]);
    }

    public function rollRetainedEarnings(int $year, ?int $postedBy = null): int
    {
        $openingDate = sprintf('%04d-01-01', $year);
        $priorYear = $year - 1;
        $priorEnd = sprintf('%04d-12-31', $priorYear);

        $currentYearEarningsId = $this->chartOfAccounts->accountIdByCode('3.3.2');
        $retainedEarningsId = $this->chartOfAccounts->accountIdByCode('3.3.1');

        $balance = (float) DB::table('journal_lines as jl')
            ->join('journals as j', 'j.id', '=', 'jl.journal_id')
            ->where('jl.account_id', $currentYearEarningsId)
            ->whereDate('j.date', '<=', $priorEnd)
            ->whereNotNull('j.posted_at')
            ->selectRaw('COALESCE(SUM(jl.credit - jl.debit), 0) as balance')
            ->value('balance');

        $balance = round($balance, 2);
        if (abs($balance) < 0.01) {
            return 0;
        }

        $lines = $balance > 0
            ? [
                ['account_id' => $currentYearEarningsId, 'debit' => $balance, 'credit' => 0, 'memo' => 'Roll prior year earnings'],
                ['account_id' => $retainedEarningsId, 'debit' => 0, 'credit' => $balance, 'memo' => 'Roll to retained earnings'],
            ]
            : [
                ['account_id' => $retainedEarningsId, 'debit' => abs($balance), 'credit' => 0, 'memo' => 'Roll prior year loss'],
                ['account_id' => $currentYearEarningsId, 'debit' => 0, 'credit' => abs($balance), 'memo' => 'Roll prior year loss'],
            ];

        return $this->postingService->postJournal([
            'date' => $openingDate,
            'description' => "Retained earnings roll-forward {$priorYear} to {$year}",
            'source_type' => 'retained_earnings_roll',
            'source_id' => $year,
            'posted_by' => $postedBy ?? Auth::id(),
            'lines' => $lines,
        ]);
    }
}
