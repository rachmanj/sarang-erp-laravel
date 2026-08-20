<?php

namespace App\Console\Commands;

use App\Models\DeliveryOrder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RepairInventoryReserveJournalAccountsCommand extends Command
{
    private const INVENTORY_PARENT_CODE = '1.1.3.01';

    private const DALAM_PERJALANAN_CODE = '1.1.3.02';

    private const STATIONERY_INVENTORY_LEAF_CODE = '1.1.3.01.01';

    protected $signature = 'inventory:repair-reserve-journal-accounts
                            {--dry-run : Print planned changes without writing}';

    protected $description = 'One-time repair: remove obsolete reserve/available intermediate journals and re-point COGS release credits to category leaf inventory accounts';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $suffix = now('Asia/Singapore')->format('Ymd');

        $accountIds = $this->resolveAccountIds();
        if ($accountIds === null) {
            return self::FAILURE;
        }

        $this->info($dryRun ? 'Dry run — no data will be modified.' : 'Repairing inventory reserve journal accounts…');
        $this->newLine();

        $backupCounts = $this->backupAffectedRows($suffix, $accountIds, $dryRun);
        $this->reportStep('Backup', $backupCounts);

        $reserveDeleted = $this->deleteReserveJournals($accountIds, $dryRun);
        $this->reportStep('Delete reserve journals', $reserveDeleted);

        $cogsRepointed = $this->repairCogsReleaseCredits($accountIds, $dryRun);
        $this->reportStep('Re-point COGS release credits (DO)', ['updated' => $cogsRepointed]);

        $directSaleRepointed = $this->repairDirectSaleReleaseCredits($accountIds, $dryRun);
        $this->reportStep('Re-point direct sale release credits (SI)', ['updated' => $directSaleRepointed]);

        $reversalRepointed = $this->repairReversalLines($accountIds, $dryRun);
        $this->reportStep('Re-point reversal lines', ['updated' => $reversalRepointed]);

        $this->reportUnmappedRows($accountIds);

        $this->newLine();
        $this->info($dryRun ? 'Dry run complete.' : 'Repair complete.');

        return self::SUCCESS;
    }

    /**
     * @return array{
     *     inventory_parent: int,
     *     dalam_perjalanan: int,
     *     stationery_inventory_leaf: int
     * }|null
     */
    private function resolveAccountIds(): ?array
    {
        $codes = [
            'inventory_parent' => self::INVENTORY_PARENT_CODE,
            'dalam_perjalanan' => self::DALAM_PERJALANAN_CODE,
            'stationery_inventory_leaf' => self::STATIONERY_INVENTORY_LEAF_CODE,
        ];

        $resolved = [];

        foreach ($codes as $key => $code) {
            $id = DB::table('accounts')->where('code', $code)->value('id');
            if ($id === null) {
                $this->error("Required account not found: {$code}");

                return null;
            }
            $resolved[$key] = (int) $id;
        }

        return $resolved;
    }

    /**
     * @param  array{
     *     inventory_parent: int,
     *     dalam_perjalanan: int,
     *     stationery_inventory_leaf: int
     * }  $accountIds
     * @return array{journals: int, journal_lines: int}
     */
    private function backupAffectedRows(string $suffix, array $accountIds, bool $dryRun): array
    {
        $sourceTypeDo = DeliveryOrder::class;
        $parentIds = [$accountIds['inventory_parent'], $accountIds['dalam_perjalanan']];
        $parentPlaceholders = implode(',', array_fill(0, count($parentIds), '?'));

        $reserveJournalIds = $this->reserveJournalIds($sourceTypeDo);

        $journalLineIds = DB::select(
            "SELECT jl.id
             FROM journal_lines jl
             INNER JOIN journals j ON j.id = jl.journal_id
             WHERE (
                 jl.journal_id IN (
                     SELECT id FROM journals
                     WHERE source_type = ?
                       AND description LIKE '%Reservation%'
                 )
                 OR (
                     j.source_type = ?
                     AND jl.account_id = ?
                     AND jl.memo LIKE 'Release reserved inventory - DO%'
                 )
                 OR (
                     j.source_type = 'sales_invoice'
                     AND jl.account_id = ?
                     AND jl.memo LIKE 'Release inventory - Direct sale%'
                 )
                 OR (
                     jl.memo LIKE 'Reversal of line %'
                     AND jl.account_id IN ({$parentPlaceholders})
                 )
             )",
            array_merge(
                [$sourceTypeDo, $sourceTypeDo, $accountIds['inventory_parent'], $accountIds['dalam_perjalanan']],
                $parentIds
            )
        );

        $lineIds = array_map(fn ($row) => (int) $row->id, $journalLineIds);

        $journalIds = array_values(array_unique(array_merge(
            $reserveJournalIds,
            $lineIds === []
                ? []
                : array_map(
                    fn ($row) => (int) $row->journal_id,
                    DB::select(
                        'SELECT DISTINCT journal_id FROM journal_lines WHERE id IN ('.implode(',', array_fill(0, count($lineIds), '?')).')',
                        $lineIds
                    )
                )
        )));

        $counts = ['journals' => 0, 'journal_lines' => 0];

        if ($journalIds !== []) {
            $counts['journals'] = count($journalIds);
            if ($dryRun) {
                $this->line('  [dry-run] Would back up '.$counts['journals'].' journal(s) to journals_'.$suffix);
            } else {
                $this->backupTableRows('journals', $suffix, 'id', $journalIds);
            }
        }

        if ($lineIds !== []) {
            $counts['journal_lines'] = count($lineIds);
            if ($dryRun) {
                $this->line('  [dry-run] Would back up '.$counts['journal_lines'].' journal line(s) to journal_lines_'.$suffix);
            } else {
                $this->backupTableRows('journal_lines', $suffix, 'id', $lineIds);
            }
        }

        return $counts;
    }

    /**
     * @param  list<int>  $ids
     */
    private function backupTableRows(string $baseTable, string $suffix, string $idColumn, array $ids): void
    {
        $backupTable = "{$baseTable}_{$suffix}";
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        if (! Schema::hasTable($backupTable)) {
            DB::statement("CREATE TABLE `{$backupTable}` LIKE `{$baseTable}`");
        }

        DB::insert(
            "INSERT INTO `{$backupTable}` SELECT * FROM `{$baseTable}` WHERE `{$idColumn}` IN ({$placeholders})",
            $ids
        );

        $this->line('  Backed up '.count($ids)." row(s) from {$baseTable} to {$backupTable}");
    }

    /**
     * @return list<int>
     */
    private function reserveJournalIds(string $sourceTypeDo): array
    {
        return array_map(
            fn ($row) => (int) $row->id,
            DB::select(
                'SELECT id FROM journals WHERE source_type = ? AND description LIKE ?',
                [$sourceTypeDo, '%Reservation%']
            )
        );
    }

    /**
     * @param  array{
     *     inventory_parent: int,
     *     dalam_perjalanan: int,
     *     stationery_inventory_leaf: int
     * }  $accountIds
     * @return array{journals: int, journal_lines: int}
     */
    private function deleteReserveJournals(array $accountIds, bool $dryRun): array
    {
        $sourceTypeDo = DeliveryOrder::class;
        $journalIds = $this->reserveJournalIds($sourceTypeDo);

        if ($journalIds === []) {
            return ['journals' => 0, 'journal_lines' => 0];
        }

        $placeholders = implode(',', array_fill(0, count($journalIds), '?'));

        $lineCount = (int) DB::selectOne(
            "SELECT COUNT(*) AS aggregate FROM journal_lines WHERE journal_id IN ({$placeholders})",
            $journalIds
        )->aggregate;

        if ($dryRun) {
            return ['journals' => count($journalIds), 'journal_lines' => $lineCount];
        }

        DB::delete("DELETE FROM journal_lines WHERE journal_id IN ({$placeholders})", $journalIds);
        $deletedJournals = DB::delete("DELETE FROM journals WHERE id IN ({$placeholders})", $journalIds);

        return ['journals' => $deletedJournals, 'journal_lines' => $lineCount];
    }

    /**
     * @param  array{
     *     inventory_parent: int,
     *     dalam_perjalanan: int,
     *     stationery_inventory_leaf: int
     * }  $accountIds
     */
    private function repairCogsReleaseCredits(array $accountIds, bool $dryRun): int
    {
        $sourceTypeDo = DeliveryOrder::class;

        if ($dryRun) {
            return (int) DB::selectOne(
                "SELECT COUNT(*) AS aggregate
                 FROM journal_lines jl
                 INNER JOIN journals j ON j.id = jl.journal_id
                 INNER JOIN delivery_order_lines dol ON dol.delivery_order_id = j.source_id
                     AND dol.item_name = SUBSTRING_INDEX(jl.memo, ' - ', -1)
                 LEFT JOIN inventory_items ii ON ii.id = dol.inventory_item_id
                 LEFT JOIN product_categories pc ON pc.id = ii.category_id
                 WHERE j.source_type = ?
                   AND jl.account_id = ?
                   AND jl.memo LIKE 'Release reserved inventory - DO%'",
                [$sourceTypeDo, $accountIds['inventory_parent']]
            )->aggregate;
        }

        return DB::update(
            "UPDATE journal_lines jl
             INNER JOIN journals j ON j.id = jl.journal_id
             INNER JOIN delivery_order_lines dol ON dol.delivery_order_id = j.source_id
                 AND dol.item_name = SUBSTRING_INDEX(jl.memo, ' - ', -1)
             LEFT JOIN inventory_items ii ON ii.id = dol.inventory_item_id
             LEFT JOIN product_categories pc ON pc.id = ii.category_id
             SET jl.account_id = COALESCE(pc.inventory_account_id, ?)
             WHERE j.source_type = ?
               AND jl.account_id = ?
               AND jl.memo LIKE 'Release reserved inventory - DO%'",
            [
                $accountIds['stationery_inventory_leaf'],
                $sourceTypeDo,
                $accountIds['inventory_parent'],
            ]
        );
    }

    /**
     * @param  array{
     *     inventory_parent: int,
     *     dalam_perjalanan: int,
     *     stationery_inventory_leaf: int
     * }  $accountIds
     */
    private function repairDirectSaleReleaseCredits(array $accountIds, bool $dryRun): int
    {
        if ($dryRun) {
            return (int) DB::selectOne(
                "SELECT COUNT(*) AS aggregate
                 FROM journal_lines jl
                 INNER JOIN journals j ON j.id = jl.journal_id
                 INNER JOIN sales_invoice_lines sil ON sil.invoice_id = j.source_id
                     AND sil.item_name = SUBSTRING_INDEX(jl.memo, ' - ', -1)
                 LEFT JOIN inventory_items ii ON ii.id = sil.inventory_item_id
                 LEFT JOIN product_categories pc ON pc.id = ii.category_id
                 WHERE j.source_type = 'sales_invoice'
                   AND jl.account_id = ?
                   AND jl.memo LIKE 'Release inventory - Direct sale%'",
                [$accountIds['dalam_perjalanan']]
            )->aggregate;
        }

        return DB::update(
            "UPDATE journal_lines jl
             INNER JOIN journals j ON j.id = jl.journal_id
             INNER JOIN sales_invoice_lines sil ON sil.invoice_id = j.source_id
                 AND sil.item_name = SUBSTRING_INDEX(jl.memo, ' - ', -1)
             LEFT JOIN inventory_items ii ON ii.id = sil.inventory_item_id
             LEFT JOIN product_categories pc ON pc.id = ii.category_id
             SET jl.account_id = COALESCE(pc.inventory_account_id, ?)
             WHERE j.source_type = 'sales_invoice'
               AND jl.account_id = ?
               AND jl.memo LIKE 'Release inventory - Direct sale%'",
            [
                $accountIds['stationery_inventory_leaf'],
                $accountIds['dalam_perjalanan'],
            ]
        );
    }

    /**
     * @param  array{
     *     inventory_parent: int,
     *     dalam_perjalanan: int,
     *     stationery_inventory_leaf: int
     * }  $accountIds
     */
    private function repairReversalLines(array $accountIds, bool $dryRun): int
    {
        $parentIds = [$accountIds['inventory_parent'], $accountIds['dalam_perjalanan']];
        $parentPlaceholders = implode(',', array_fill(0, count($parentIds), '?'));

        if ($dryRun) {
            return (int) DB::selectOne(
                "SELECT COUNT(*) AS aggregate
                 FROM journal_lines reversal
                 INNER JOIN journal_lines original
                     ON original.id = CAST(SUBSTRING(reversal.memo, 19) AS UNSIGNED)
                 WHERE reversal.memo LIKE 'Reversal of line %'
                   AND reversal.account_id IN ({$parentPlaceholders})",
                $parentIds
            )->aggregate;
        }

        return DB::update(
            "UPDATE journal_lines reversal
             INNER JOIN journal_lines original
                 ON original.id = CAST(SUBSTRING(reversal.memo, 19) AS UNSIGNED)
             SET reversal.account_id = original.account_id
             WHERE reversal.memo LIKE 'Reversal of line %'
               AND reversal.account_id IN ({$parentPlaceholders})",
            $parentIds
        );
    }

    /**
     * @param  array{
     *     inventory_parent: int,
     *     dalam_perjalanan: int,
     *     stationery_inventory_leaf: int
     * }  $accountIds
     */
    private function reportUnmappedRows(array $accountIds): void
    {
        $this->newLine();
        $this->info('Remaining unmapped rows on inventory parent / dalam-perjalanan accounts:');

        $sourceTypeDo = DeliveryOrder::class;
        $parentIds = [$accountIds['inventory_parent'], $accountIds['dalam_perjalanan']];
        $parentPlaceholders = implode(',', array_fill(0, count($parentIds), '?'));

        $reserveJournalCount = (int) DB::selectOne(
            'SELECT COUNT(*) AS aggregate FROM journals WHERE source_type = ? AND description LIKE ?',
            [$sourceTypeDo, '%Reservation%']
        )->aggregate;

        $cogsUnmapped = DB::select(
            "SELECT jl.id, jl.account_id, jl.memo, j.source_type, j.source_id
             FROM journal_lines jl
             INNER JOIN journals j ON j.id = jl.journal_id
             WHERE j.source_type = ?
               AND jl.account_id = ?
               AND jl.memo LIKE 'Release reserved inventory - DO%'
             ORDER BY jl.id
             LIMIT 25",
            [$sourceTypeDo, $accountIds['inventory_parent']]
        );

        $cogsUnmappedCount = (int) DB::selectOne(
            "SELECT COUNT(*) AS aggregate
             FROM journal_lines jl
             INNER JOIN journals j ON j.id = jl.journal_id
             WHERE j.source_type = ?
               AND jl.account_id = ?
               AND jl.memo LIKE 'Release reserved inventory - DO%'",
            [$sourceTypeDo, $accountIds['inventory_parent']]
        )->aggregate;

        $directSaleUnmapped = DB::select(
            "SELECT jl.id, jl.account_id, jl.memo, j.source_type, j.source_id
             FROM journal_lines jl
             INNER JOIN journals j ON j.id = jl.journal_id
             WHERE j.source_type = 'sales_invoice'
               AND jl.account_id = ?
               AND jl.memo LIKE 'Release inventory - Direct sale%'
             ORDER BY jl.id
             LIMIT 25",
            [$accountIds['dalam_perjalanan']]
        );

        $directSaleUnmappedCount = (int) DB::selectOne(
            "SELECT COUNT(*) AS aggregate
             FROM journal_lines jl
             INNER JOIN journals j ON j.id = jl.journal_id
             WHERE j.source_type = 'sales_invoice'
               AND jl.account_id = ?
               AND jl.memo LIKE 'Release inventory - Direct sale%'",
            [$accountIds['dalam_perjalanan']]
        )->aggregate;

        $reversalUnmapped = DB::select(
            "SELECT jl.id, jl.account_id, jl.memo
             FROM journal_lines jl
             WHERE jl.memo LIKE 'Reversal of line %'
               AND jl.account_id IN ({$parentPlaceholders})
             ORDER BY jl.id
             LIMIT 25",
            $parentIds
        );

        $reversalUnmappedCount = (int) DB::selectOne(
            "SELECT COUNT(*) AS aggregate
             FROM journal_lines
             WHERE memo LIKE 'Reversal of line %'
               AND account_id IN ({$parentPlaceholders})",
            $parentIds
        )->aggregate;

        $this->line("  reserve journals remaining: {$reserveJournalCount}");
        $this->line("  COGS release on parent (1.1.3.01): {$cogsUnmappedCount}");
        if ($cogsUnmapped !== []) {
            $this->table(
                ['id', 'account_id', 'memo', 'source_type', 'source_id'],
                array_map(fn ($row) => [
                    $row->id,
                    $row->account_id,
                    $row->memo,
                    $row->source_type,
                    $row->source_id,
                ], $cogsUnmapped)
            );
        }

        $this->line("  direct sale release on dalam-perjalanan (1.1.3.02): {$directSaleUnmappedCount}");
        if ($directSaleUnmapped !== []) {
            $this->table(
                ['id', 'account_id', 'memo', 'source_type', 'source_id'],
                array_map(fn ($row) => [
                    $row->id,
                    $row->account_id,
                    $row->memo,
                    $row->source_type,
                    $row->source_id,
                ], $directSaleUnmapped)
            );
        }

        $this->line("  reversal lines on parent accounts: {$reversalUnmappedCount}");
        if ($reversalUnmapped !== []) {
            $this->table(
                ['id', 'account_id', 'memo'],
                array_map(fn ($row) => [
                    $row->id,
                    $row->account_id,
                    $row->memo,
                ], $reversalUnmapped)
            );
        }
    }

    /**
     * @param  array<string, int>  $counts
     */
    private function reportStep(string $label, array $counts): void
    {
        $this->info($label.':');
        foreach ($counts as $key => $count) {
            $this->line("  {$key}: {$count}");
        }
    }
}
