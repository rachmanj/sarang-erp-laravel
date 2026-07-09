<?php

namespace App\Services\Bank;

use App\Models\Bank\BankAccount;
use App\Models\Bank\BankReconciliation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class KoranDashboardService
{
    /**
     * @return array{
     *     year: int,
     *     months: list<array{num: int, label: string, periode: string}>,
     *     accounts: Collection<int, BankAccount>,
     *     matrix: array<int, array<int, array<string, mixed>>>
     * }
     */
    public function buildMatrix(int $year): array
    {
        $months = collect(range(1, 12))->map(fn (int $m) => [
            'num' => $m,
            'label' => date('M', mktime(0, 0, 0, $m, 1)),
            'periode' => sprintf('%04d-%02d-01', $year, $m),
        ])->all();

        $accounts = BankAccount::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $sessions = BankReconciliation::query()
            ->with('statement')
            ->whereYear('periode', $year)
            ->get()
            ->keyBy(fn (BankReconciliation $r) => $r->bank_account_id.'-'.(int) $r->periode?->format('n'));

        $matrix = [];
        foreach ($accounts as $account) {
            $matrix[$account->id] = [];
            foreach ($months as $month) {
                $key = $account->id.'-'.$month['num'];
                $session = $sessions->get($key);
                $matrix[$account->id][$month['num']] = $this->cellDto($account->id, $month, $session);
            }
        }

        return [
            'year' => $year,
            'months' => $months,
            'accounts' => $accounts,
            'matrix' => $matrix,
        ];
    }

    /**
     * @param  array{num: int, label: string, periode: string}  $month
     * @return array<string, mixed>
     */
    private function cellDto(int $bankAccountId, array $month, ?BankReconciliation $session): array
    {
        if (! $session) {
            return [
                'status' => 'empty',
                'bank_account_id' => $bankAccountId,
                'month' => $month['num'],
                'periode' => $month['periode'],
                'reconciliation_id' => null,
                'source_mode' => null,
                'label' => 'Empty',
                'badge_class' => 'secondary',
                'can_upload' => true,
                'can_open' => false,
                'has_pdf' => false,
                'statement_pdf_url' => null,
                'visual' => $this->visualForEmptyCell(),
            ];
        }

        $hasPdf = $this->statementHasPdf($session);

        $badgeClass = match ($session->status) {
            BankReconciliation::STATUS_COMPLETED => 'success',
            BankReconciliation::STATUS_IN_REVIEW => 'warning',
            BankReconciliation::STATUS_PROCESSING => 'info',
            BankReconciliation::STATUS_FAILED => 'danger',
            default => 'secondary',
        };

        $pdfUrl = $hasPdf ? route('bank-reconciliation.statement-pdf', $session) : null;

        return [
            'status' => $session->status,
            'bank_account_id' => $bankAccountId,
            'month' => $month['num'],
            'periode' => $month['periode'],
            'reconciliation_id' => $session->id,
            'source_mode' => $session->source_mode,
            'label' => strtoupper(str_replace('_', ' ', $session->status)),
            'badge_class' => $badgeClass,
            'can_upload' => false,
            'can_open' => true,
            'is_completed' => $session->status === BankReconciliation::STATUS_COMPLETED,
            'is_ai' => $session->source_mode === BankReconciliation::SOURCE_AI,
            'has_pdf' => $hasPdf,
            'statement_pdf_url' => $pdfUrl,
            'visual' => $this->visualForSession($session->status, $hasPdf, $pdfUrl),
        ];
    }

    /**
     * @return array{primary: string, overlay: ?array{icon: string, color: string, title: string, href: ?string}}
     */
    private function visualForEmptyCell(): array
    {
        return [
            'primary' => 'missing',
            'overlay' => null,
        ];
    }

    /**
     * @return array{primary: string, overlay: ?array{icon: string, color: string, title: string, href: ?string}}
     */
    private function visualForSession(string $status, bool $hasPdf, ?string $pdfUrl): array
    {
        if ($hasPdf) {
            return [
                'primary' => 'present',
                'overlay' => [
                    'icon' => 'file-pdf',
                    'color' => 'red',
                    'title' => 'Preview PDF attachment',
                    'href' => $pdfUrl,
                ],
            ];
        }

        $overlay = match ($status) {
            BankReconciliation::STATUS_PROCESSING => [
                'icon' => 'list',
                'color' => 'teal',
                'title' => 'Processing',
                'href' => null,
            ],
            BankReconciliation::STATUS_IN_REVIEW => [
                'icon' => 'balance-scale',
                'color' => 'blue',
                'title' => 'In review',
                'href' => null,
            ],
            BankReconciliation::STATUS_COMPLETED => [
                'icon' => 'check-double',
                'color' => 'green',
                'title' => 'Completed',
                'href' => null,
            ],
            BankReconciliation::STATUS_FAILED => [
                'icon' => 'exclamation-triangle',
                'color' => 'red',
                'title' => 'Failed',
                'href' => null,
            ],
            default => null,
        };

        return [
            'primary' => 'present',
            'overlay' => $overlay,
        ];
    }

    private function statementHasPdf(BankReconciliation $session): bool
    {
        $session->loadMissing('statement');
        $filePath = $session->statement?->file_path;

        return is_string($filePath) && $filePath !== '' && Storage::disk('local')->exists($filePath);
    }

    /**
     * @return array<string, mixed>
     */
    public function cellFor(int $bankAccountId, int $year, int $month): array
    {
        $periode = sprintf('%04d-%02d-01', $year, $month);
        $monthMeta = [
            'num' => $month,
            'label' => date('M', mktime(0, 0, 0, $month, 1)),
            'periode' => $periode,
        ];

        $session = BankReconciliation::query()
            ->with('statement')
            ->where('bank_account_id', $bankAccountId)
            ->whereDate('periode', $periode)
            ->first();

        return $this->cellDto($bankAccountId, $monthMeta, $session);
    }
}
