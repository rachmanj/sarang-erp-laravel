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

        foreach ($this->auditPurchasePayments($from, $to) as $item) {
            $checked++;
            if ($item['is_mismatch']) {
                $discrepancies[] = $item;
            }
        }

        foreach ($this->auditSalesReceipts($from, $to) as $item) {
            $checked++;
            if ($item['is_mismatch']) {
                $discrepancies[] = $item;
            }
        }

        foreach ($this->auditDirectPurchaseInvoices($from, $to) as $item) {
            $checked++;
            if ($item['is_mismatch']) {
                $discrepancies[] = $item;
            }
        }

        foreach ($this->auditCashExpenses($from, $to) as $item) {
            $checked++;
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
            $journalIds = $this->journalCashAccountIds('purchase_payment', (int) $payment->id, 'credit');
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
            $journalIds = $this->journalCashAccountIds('sales_receipt', (int) $receipt->id, 'debit');
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
            $journalIds = $this->journalCashAccountIds('purchase_invoice', (int) $invoice->id, 'credit');
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
            ->with('lines')
            ->orderBy('id')
            ->get();

        foreach ($expenses as $expense) {
            $docIds = $this->uniqueCashIdsFromLineAccounts(
                $expense->lines->pluck('account_id')->all()
            );
            $journalIds = $this->journalCashAccountIds('cash_expense', (int) $expense->id, 'credit');
            yield $this->buildAuditRow(
                jenis: 'Pengeluaran Kas',
                dokumen: $expense->expense_no ?: '#'.$expense->id,
                tanggal: Carbon::parse($expense->date)->toDateString(),
                docAccountIds: $docIds,
                journalAccountIds: $journalIds,
                sourceType: 'cash_expense',
                sourceId: (int) $expense->id,
                journalSide: 'credit',
            );
        }
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
    private function journalCashAccountIds(string $sourceType, int $sourceId, string $side): array
    {
        if (! DB::table('journals')->where('source_type', $sourceType)->where('source_id', $sourceId)->exists()) {
            return [];
        }

        $query = DB::table('journal_lines as jl')
            ->join('journals as j', 'j.id', '=', 'jl.journal_id')
            ->where('j.source_type', $sourceType)
            ->where('j.source_id', $sourceId)
            ->whereIn('jl.account_id', array_keys($this->cashAccountIdSet));

        if ($side === 'credit') {
            $query->where('jl.credit', '>', 0);
        } else {
            $query->where('jl.debit', '>', 0);
        }

        $ids = $query->distinct()->pluck('jl.account_id')->map(fn ($id) => (int) $id)->all();
        sort($ids);

        return $ids;
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

        $linesQuery = DB::table('journal_lines as jl')
            ->join('journals as j', 'j.id', '=', 'jl.journal_id')
            ->where('j.source_type', $doc['source_type'])
            ->where('j.source_id', $doc['source_id'])
            ->whereIn('jl.account_id', array_keys($this->cashAccountIdSet))
            ->where('jl.account_id', '!=', $targetAccountId);

        if ($side === 'credit') {
            $linesQuery->where('jl.credit', '>', 0);
        } else {
            $linesQuery->where('jl.debit', '>', 0);
        }

        $lines = $linesQuery->get(['jl.id', 'jl.account_id', 'jl.debit', 'jl.credit']);

        if ($lines->isEmpty()) {
            $this->warn("{$doc['jenis']} {$doc['dokumen']}: tidak ada baris jurnal kas/bank untuk dipindah (mungkin sudah diperbaiki).");

            return null;
        }

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
