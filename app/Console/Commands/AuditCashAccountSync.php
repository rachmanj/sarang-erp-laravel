<?php

namespace App\Console\Commands;

use App\Models\Accounting\CashExpense;
use App\Models\Accounting\PurchasePayment;
use App\Models\Accounting\SalesReceipt;
use App\Services\Accounting\PeriodCloseService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AuditCashAccountSync extends Command
{
    protected $signature = 'finance:cash-account-sync
                            {--fix : Perbaiki journal_lines.account_id untuk dokumen yang selisih (satu akun kas/bank di dokumen)}
                            {--from= : Tanggal dokumen awal (Y-m-d), default awal tahun berjalan}
                            {--to= : Tanggal dokumen akhir (Y-m-d), default hari ini}
                            {--force : Izinkan perbaikan pada periode yang sudah ditutup}';

    protected $description = 'Deteksi selisih akun kas/bank antara dokumen posted dan baris jurnal terkait';

    private const IMBALANCE_TOLERANCE = 0.01;

    private const NET_EFFECTIVE_TOLERANCE = 0.5;

    /** @var array<int, true> */
    private array $cashAccountIdSet = [];

    /** @var array<int, string> */
    private array $accountLabels = [];

    public function handle(PeriodCloseService $periodClose): int
    {
        [$from, $to] = $this->resolveDateRange();
        $this->loadCashAccounts();

        $checked = 0;
        $discrepancies = [];
        /** @var array<string, array{checked: int, selisih: int}> */
        $statsByJenis = [];

        foreach ($this->auditPurchasePayments($from, $to) as $item) {
            $checked++;
            $this->incrementJenisStats($statsByJenis, $item);
            if ($item['is_mismatch']) {
                $discrepancies[] = $item;
            }
        }

        foreach ($this->auditSalesReceipts($from, $to) as $item) {
            $checked++;
            $this->incrementJenisStats($statsByJenis, $item);
            if ($item['is_mismatch']) {
                $discrepancies[] = $item;
            }
        }

        foreach ($this->auditDirectPurchaseInvoices($from, $to) as $item) {
            $checked++;
            $this->incrementJenisStats($statsByJenis, $item);
            if ($item['is_mismatch']) {
                $discrepancies[] = $item;
            }
        }

        foreach ($this->auditCashExpenses($from, $to) as $item) {
            $checked++;
            $this->incrementJenisStats($statsByJenis, $item);
            if ($item['is_mismatch']) {
                $discrepancies[] = $item;
            }
        }

        $tableRows = array_map(
            fn (array $d) => [
                $d['jenis'],
                $d['dokumen'],
                $d['tanggal'],
                $this->formatAccountSet($d['doc_account_ids']),
                $this->formatAccountSet($d['journal_account_ids']),
                $d['selisih'],
            ],
            $discrepancies
        );

        if ($tableRows !== []) {
            $this->table(['Jenis', 'Dokumen', 'Tanggal', 'Akun dokumen', 'Akun jurnal', 'Selisih'], $tableRows);
        } else {
            $this->info('Tidak ada selisih akun kas/bank pada rentang tanggal ini.');
        }

        $this->newLine();
        $this->printJenisSummaryTable($statsByJenis, $checked, count($discrepancies));
        $this->newLine();
        $this->line("Dokumen diperiksa: {$checked}");
        $this->line('Selisih ditemukan: '.count($discrepancies));

        $doFix = (bool) $this->option('fix');
        if (! $doFix) {
            $this->warn('Mode baca saja — tidak ada data yang diubah. Gunakan --fix untuk memindahkan baris jurnal kas/bank ke akun dokumen (satu akun per dokumen).');

            return self::SUCCESS;
        }

        if ($discrepancies === []) {
            return self::SUCCESS;
        }

        $force = (bool) $this->option('force');
        $fixedCount = 0;

        try {
            DB::transaction(function () use ($discrepancies, $periodClose, $force, &$fixedCount): void {
                foreach ($discrepancies as $doc) {
                    $result = $this->fixDocument($doc, $periodClose, $force);
                    if ($result !== null) {
                        $fixedCount++;
                        $this->info(sprintf(
                            '%s %s: %d baris dipindah | delta saldo akun lama %s, akun baru %s',
                            $doc['jenis'],
                            $doc['dokumen'],
                            $result['lines_moved'],
                            $result['old_delta'],
                            $result['new_delta']
                        ));
                    }
                }

                $net = (float) DB::table('journal_lines')->selectRaw('COALESCE(SUM(debit - credit), 0) as net')->value('net');
                if (abs($net) > self::IMBALANCE_TOLERANCE) {
                    throw new \RuntimeException(sprintf(
                        'Validasi gagal: total net journal_lines = %.4f (harus 0). Semua perubahan di-rollback.',
                        $net
                    ));
                }
            });
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info("Perbaikan selesai untuk {$fixedCount} dokumen.");

        return self::SUCCESS;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function resolveDateRange(): array
    {
        $from = $this->option('from')
            ? Carbon::parse((string) $this->option('from'))->toDateString()
            : now()->startOfYear()->toDateString();
        $to = $this->option('to')
            ? Carbon::parse((string) $this->option('to'))->toDateString()
            : now()->toDateString();

        return [$from, $to];
    }

    private function loadCashAccounts(): void
    {
        $rows = DB::table('accounts')
            ->where('code', 'like', '1.1.1.%')
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        foreach ($rows as $row) {
            $id = (int) $row->id;
            $this->cashAccountIdSet[$id] = true;
            $this->accountLabels[$id] = trim($row->code.' '.$row->name);
        }
    }

    /**
     * @param  array<int>  $accountIds
     */
    private function formatAccountSet(array $accountIds): string
    {
        if ($accountIds === []) {
            return '';
        }

        $labels = [];
        foreach ($accountIds as $id) {
            $labels[] = $this->accountLabels[$id] ?? (string) $id;
        }

        return implode(', ', $labels);
    }

    /**
     * @param  array<string, array{checked: int, selisih: int}>  $statsByJenis
     * @param  array<string, mixed>  $item
     */
    private function incrementJenisStats(array &$statsByJenis, array $item): void
    {
        $jenis = (string) $item['jenis'];
        if (! isset($statsByJenis[$jenis])) {
            $statsByJenis[$jenis] = ['checked' => 0, 'selisih' => 0];
        }
        $statsByJenis[$jenis]['checked']++;
        if ($item['is_mismatch']) {
            $statsByJenis[$jenis]['selisih']++;
        }
    }

    /**
     * @param  array<string, array{checked: int, selisih: int}>  $statsByJenis
     */
    private function printJenisSummaryTable(array $statsByJenis, int $totalChecked, int $totalSelisih): void
    {
        $rows = [];
        foreach ($statsByJenis as $jenis => $stats) {
            $rows[] = [$jenis, (string) $stats['checked'], (string) $stats['selisih']];
        }
        usort($rows, fn (array $a, array $b) => strcmp($a[0], $b[0]));
        $rows[] = ['Total', (string) $totalChecked, (string) $totalSelisih];

        $this->table(['Jenis', 'Dokumen diperiksa', 'Selisih'], $rows);
    }

    /**
     * @return \Generator<int, array<string, mixed>>
     */
    private function auditPurchasePayments(string $from, string $to): \Generator
    {
        $payments = PurchasePayment::query()
            ->where('status', 'posted')
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to)
            ->with('lines')
            ->orderBy('id')
            ->get();

        foreach ($payments as $payment) {
            $docIds = $this->uniqueCashIdsFromLineAccounts(
                $payment->lines->pluck('account_id')->all()
            );
            $journalIds = $this->journalEffectiveCashAccountIds('purchase_payment', (int) $payment->id);
            yield $this->buildAuditRow(
                jenis: 'Pembayaran Pembelian',
                dokumen: $payment->payment_no ?: '#'.$payment->id,
                tanggal: $payment->date->toDateString(),
                docAccountIds: $docIds,
                journalAccountIds: $journalIds,
                sourceType: 'purchase_payment',
                sourceId: (int) $payment->id,
                journalSide: 'credit',
            );
        }
    }

    /**
     * @return \Generator<int, array<string, mixed>>
     */
    private function auditSalesReceipts(string $from, string $to): \Generator
    {
        $receipts = SalesReceipt::query()
            ->where('status', 'posted')
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to)
            ->with('lines')
            ->orderBy('id')
            ->get();

        foreach ($receipts as $receipt) {
            $docIds = $this->uniqueCashIdsFromLineAccounts(
                $receipt->lines->pluck('account_id')->all()
            );
            $journalIds = $this->journalEffectiveCashAccountIds('sales_receipt', (int) $receipt->id);
            yield $this->buildAuditRow(
                jenis: 'Penerimaan Penjualan',
                dokumen: $receipt->receipt_no ?: '#'.$receipt->id,
                tanggal: $receipt->date->toDateString(),
                docAccountIds: $docIds,
                journalAccountIds: $journalIds,
                sourceType: 'sales_receipt',
                sourceId: (int) $receipt->id,
                journalSide: 'debit',
            );
        }
    }

    /**
     * @return \Generator<int, array<string, mixed>>
     */
    private function auditDirectPurchaseInvoices(string $from, string $to): \Generator
    {
        $invoices = DB::table('purchase_invoices')
            ->where('status', 'posted')
            ->where('is_direct_purchase', 1)
            ->whereNotNull('cash_account_id')
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to)
            ->orderBy('id')
            ->get(['id', 'invoice_no', 'date', 'cash_account_id']);

        foreach ($invoices as $invoice) {
            $cashId = (int) $invoice->cash_account_id;
            $docIds = isset($this->cashAccountIdSet[$cashId]) ? [$cashId] : [];
            $journalIds = $this->journalEffectiveCashAccountIds('purchase_invoice', (int) $invoice->id);
            yield $this->buildAuditRow(
                jenis: 'Faktur Pembelian Tunai',
                dokumen: $invoice->invoice_no ?: '#'.$invoice->id,
                tanggal: Carbon::parse($invoice->date)->toDateString(),
                docAccountIds: $docIds,
                journalAccountIds: $journalIds,
                sourceType: 'purchase_invoice',
                sourceId: (int) $invoice->id,
                journalSide: 'credit',
            );
        }
    }

    /**
     * @return \Generator<int, array<string, mixed>>
     */
    private function auditCashExpenses(string $from, string $to): \Generator
    {
        $expenses = CashExpense::query()
            ->where('status', 'posted')
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to)
            ->orderBy('id')
            ->get();

        foreach ($expenses as $expense) {
            if ($expense->cash_account_id === null) {
                continue;
            }

            $cashId = (int) $expense->cash_account_id;
            $docIds = isset($this->cashAccountIdSet[$cashId]) ? [$cashId] : [];
            $journalIds = $this->journalEffectiveCashAccountIds('cash_expense', (int) $expense->id);
            yield $this->buildCashExpenseAuditRow(
                dokumen: $expense->expense_no ?: '#'.$expense->id,
                tanggal: Carbon::parse($expense->date)->toDateString(),
                docAccountIds: $docIds,
                journalAccountIds: $journalIds,
                sourceId: (int) $expense->id,
            );
        }
    }

    /**
     * @param  array<int>  $docAccountIds
     * @param  array<int>  $journalAccountIds
     * @return array<string, mixed>
     */
    private function buildCashExpenseAuditRow(
        string $dokumen,
        string $tanggal,
        array $docAccountIds,
        array $journalAccountIds,
        int $sourceId,
    ): array {
        $hasJournal = DB::table('journals')
            ->where('source_type', 'cash_expense')
            ->where('source_id', $sourceId)
            ->exists();

        $noCashCreditInJournal = $journalAccountIds === [];
        $isMismatch = $docAccountIds !== []
            && ($noCashCreditInJournal || $this->setsDiffer($docAccountIds, $journalAccountIds));

        $selisih = 'akun kas/bank tidak sesuai';
        if ($noCashCreditInJournal && $docAccountIds !== []) {
            $selisih = 'jurnal tidak ditemukan';
        }

        return [
            'jenis' => 'Pengeluaran Kas',
            'dokumen' => $dokumen,
            'tanggal' => $tanggal,
            'doc_account_ids' => $docAccountIds,
            'journal_account_ids' => $journalAccountIds,
            'selisih' => $selisih,
            'is_mismatch' => $isMismatch,
            'source_type' => 'cash_expense',
            'source_id' => $sourceId,
            'journal_side' => 'credit',
            'has_journal' => $hasJournal,
        ];
    }

    /**
     * @param  array<int|string>  $rawAccountIds
     * @return array<int>
     */
    private function uniqueCashIdsFromLineAccounts(array $rawAccountIds): array
    {
        $ids = [];
        foreach ($rawAccountIds as $rawId) {
            $id = (int) $rawId;
            if (isset($this->cashAccountIdSet[$id])) {
                $ids[$id] = true;
            }
        }

        $list = array_keys($ids);
        sort($list);

        return $list;
    }

    /**
     * @return array<int>
     */
    private function journalEffectiveCashAccountIds(string $sourceType, int $sourceId): array
    {
        if (! DB::table('journals')->where('source_type', $sourceType)->where('source_id', $sourceId)->exists()) {
            return [];
        }

        $nets = $this->cashAccountNetsForSource($sourceType, $sourceId);
        $ids = [];
        foreach ($nets as $accountId => $net) {
            if (abs($net) > self::NET_EFFECTIVE_TOLERANCE) {
                $ids[] = $accountId;
            }
        }
        sort($ids);

        return $ids;
    }

    /**
     * @return array<int, float>
     */
    private function cashAccountNetsForSource(string $sourceType, int $sourceId): array
    {
        $rows = DB::table('journal_lines as jl')
            ->join('journals as j', 'j.id', '=', 'jl.journal_id')
            ->where('j.source_type', $sourceType)
            ->where('j.source_id', $sourceId)
            ->whereIn('jl.account_id', array_keys($this->cashAccountIdSet))
            ->groupBy('jl.account_id')
            ->selectRaw('jl.account_id as account_id, COALESCE(SUM(jl.debit), 0) - COALESCE(SUM(jl.credit), 0) as net')
            ->get();

        $nets = [];
        foreach ($rows as $row) {
            $nets[(int) $row->account_id] = (float) $row->net;
        }

        return $nets;
    }

    /**
     * @param  list<object{id: int|string, debit: float|string, credit: float|string}>  $linesChronological
     * @return list<int>
     */
    private function aliveJournalLineIds(array $linesChronological): array
    {
        /** @var list{array{id: int, amount: float}>} $open */
        $open = [];
        foreach ($linesChronological as $line) {
            $amount = (float) $line->debit - (float) $line->credit;
            $paired = false;
            foreach ($open as $idx => $entry) {
                if (abs($entry['amount'] + $amount) <= self::NET_EFFECTIVE_TOLERANCE) {
                    array_splice($open, $idx, 1);
                    $paired = true;
                    break;
                }
            }
            if (! $paired) {
                $open[] = ['id' => (int) $line->id, 'amount' => $amount];
            }
        }

        return array_map(fn (array $entry): int => $entry['id'], $open);
    }

    /**
     * @param  array<int>  $docAccountIds
     * @param  array<int>  $journalAccountIds
     * @return array<string, mixed>
     */
    private function buildAuditRow(
        string $jenis,
        string $dokumen,
        string $tanggal,
        array $docAccountIds,
        array $journalAccountIds,
        string $sourceType,
        int $sourceId,
        string $journalSide,
    ): array {
        $hasJournal = DB::table('journals')
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->exists();

        $isMismatch = $this->setsDiffer($docAccountIds, $journalAccountIds) || ! $hasJournal;

        $selisih = 'akun kas/bank tidak sesuai';
        if (! $hasJournal) {
            $selisih = 'jurnal tidak ditemukan';
        }

        return [
            'jenis' => $jenis,
            'dokumen' => $dokumen,
            'tanggal' => $tanggal,
            'doc_account_ids' => $docAccountIds,
            'journal_account_ids' => $journalAccountIds,
            'selisih' => $selisih,
            'is_mismatch' => $isMismatch,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'journal_side' => $journalSide,
            'has_journal' => $hasJournal,
        ];
    }

    /**
     * @param  array<int>  $a
     * @param  array<int>  $b
     */
    private function setsDiffer(array $a, array $b): bool
    {
        return array_values($a) !== array_values($b);
    }

    /**
     * @param  array<string, mixed>  $doc
     * @return array{lines_moved: int, old_delta: string, new_delta: string}|null
     */
    private function fixDocument(array $doc, PeriodCloseService $periodClose, bool $force): ?array
    {
        if (! $doc['has_journal']) {
            $this->warn("{$doc['jenis']} {$doc['dokumen']}: dilewati — jurnal tidak ditemukan.");

            return null;
        }

        $docAccountIds = $doc['doc_account_ids'];
        if (count($docAccountIds) !== 1) {
            $this->warn("{$doc['jenis']} {$doc['dokumen']}: dilewati — perlu manual (dokumen punya ".count($docAccountIds).' akun kas/bank).');

            return null;
        }

        $targetAccountId = $docAccountIds[0];
        $side = $doc['journal_side'];

        $journalDates = DB::table('journals')
            ->where('source_type', $doc['source_type'])
            ->where('source_id', $doc['source_id'])
            ->pluck('date')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->unique()
            ->all();

        foreach ($journalDates as $journalDate) {
            if ($periodClose->isDateClosed($journalDate) && ! $force) {
                $this->warn("{$doc['jenis']} {$doc['dokumen']}: dilewati — periode jurnal {$journalDate} tertutup (gunakan --force untuk memaksa).");

                return null;
            }
        }

        $allCashLines = DB::table('journal_lines as jl')
            ->join('journals as j', 'j.id', '=', 'jl.journal_id')
            ->where('j.source_type', $doc['source_type'])
            ->where('j.source_id', $doc['source_id'])
            ->whereIn('jl.account_id', array_keys($this->cashAccountIdSet))
            ->orderBy('j.id')
            ->orderBy('jl.id')
            ->get(['jl.id', 'jl.journal_id', 'jl.account_id', 'jl.debit', 'jl.credit']);

        $netsByAccount = $this->cashAccountNetsForSource($doc['source_type'], $doc['source_id']);

        /** @var array<int, list<object>> $linesByAccount */
        $linesByAccount = [];
        foreach ($allCashLines as $line) {
            $linesByAccount[(int) $line->account_id][] = $line;
        }

        $linesToMove = [];
        $wrongAccountsWithAlive = [];

        foreach ($linesByAccount as $accountId => $accountLines) {
            if ($accountId === $targetAccountId) {
                continue;
            }

            $accountNet = $netsByAccount[$accountId] ?? 0.0;
            if (abs($accountNet) <= self::NET_EFFECTIVE_TOLERANCE) {
                continue;
            }

            $aliveIds = $this->aliveJournalLineIds($accountLines);
            $aliveIdSet = array_flip($aliveIds);

            $aliveOnDocSide = [];
            $aliveNetOnSide = 0.0;
            foreach ($accountLines as $line) {
                if (! isset($aliveIdSet[(int) $line->id])) {
                    continue;
                }

                $onSide = $side === 'credit'
                    ? (float) $line->credit > 0
                    : (float) $line->debit > 0;

                if (! $onSide) {
                    continue;
                }

                $aliveOnDocSide[] = $line;
                $aliveNetOnSide += $side === 'credit' ? (float) $line->credit : (float) $line->debit;
            }

            if ($aliveOnDocSide === []) {
                continue;
            }

            if (abs($aliveNetOnSide - abs($accountNet)) > self::NET_EFFECTIVE_TOLERANCE) {
                $this->warn("{$doc['jenis']} {$doc['dokumen']}: dilewati — perlu manual (baris hidup tidak jelas untuk akun ".($this->accountLabels[$accountId] ?? $accountId).').');

                return null;
            }

            $wrongAccountsWithAlive[$accountId] = true;
            foreach ($aliveOnDocSide as $line) {
                $linesToMove[] = $line;
            }
        }

        if (count($wrongAccountsWithAlive) > 1) {
            $this->warn("{$doc['jenis']} {$doc['dokumen']}: dilewati — perlu manual (lebih dari satu akun kas/bank bersisa di jurnal).");

            return null;
        }

        if ($linesToMove === []) {
            $this->warn("{$doc['jenis']} {$doc['dokumen']}: tidak ada baris jurnal kas/bank untuk dipindah (mungkin sudah diperbaiki).");

            return null;
        }

        $lines = collect($linesToMove);

        $linesMoved = 0;
        $oldDeltaByAccount = [];
        $newDeltaAmount = 0.0;

        foreach ($lines as $line) {
            $oldAccountId = (int) $line->account_id;
            $amount = $side === 'credit' ? (float) $line->credit : (float) $line->debit;

            $updated = DB::table('journal_lines')
                ->where('id', (int) $line->id)
                ->where('account_id', $oldAccountId)
                ->update([
                    'account_id' => $targetAccountId,
                    'updated_at' => now(),
                ]);

            if ($updated !== 1) {
                throw new \RuntimeException(
                    "Baris jurnal #{$line->id} tidak dapat diperbarui — akun sudah berubah atau baris tidak ditemukan."
                );
            }

            $linesMoved++;
            $oldDeltaByAccount[$oldAccountId] = ($oldDeltaByAccount[$oldAccountId] ?? 0.0) + $amount;
            $newDeltaAmount += $amount;
        }

        $oldParts = [];
        foreach ($oldDeltaByAccount as $accountId => $amount) {
            $label = $this->accountLabels[$accountId] ?? (string) $accountId;
            $sign = $side === 'credit' ? '+' : '-';
            $oldParts[] = "{$label} {$sign}".number_format($amount, 2, '.', '');
        }

        $newLabel = $this->accountLabels[$targetAccountId] ?? (string) $targetAccountId;
        $newSign = $side === 'credit' ? '-' : '+';

        return [
            'lines_moved' => $linesMoved,
            'old_delta' => implode('; ', $oldParts) ?: '0',
            'new_delta' => "{$newLabel} {$newSign}".number_format($newDeltaAmount, 2, '.', ''),
        ];
    }
}
