<?php

namespace App\Console\Commands;

use App\Models\DeliveryOrder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RepairParentAccountPostingsCommand extends Command
{
    private const REVENUE_PARENT_CODE = '4.1.1';

    private const HPP_PARENT_CODE = '5.1';

    private const PPN_PARENT_CODE = '2.1.2';

    private const PPN_LEAF_CODE = '2.1.2.01';

    private const STATIONERY_REVENUE_LEAF_CODE = '4.1.1.01';

    private const STATIONERY_HPP_LEAF_CODE = '5.1.01';

    protected $signature = 'accounts:repair-parent-account-postings
                            {--dry-run : Print planned changes without writing}';

    protected $description = 'One-time repair: re-point revenue, HPP, and PPN journal lines and document lines from non-postable parent accounts to category leaf accounts';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $suffix = now('Asia/Singapore')->format('Ymd');

        $accountIds = $this->resolveAccountIds();
        if ($accountIds === null) {
            return self::FAILURE;
        }

        $parentAccountIds = [
            $accountIds['revenue_parent'],
            $accountIds['hpp_parent'],
            $accountIds['ppn_parent'],
        ];

        $this->info($dryRun ? 'Dry run — no data will be modified.' : 'Repairing parent account postings…');
        $this->newLine();

        $backupCounts = $this->backupAffectedRows($suffix, $parentAccountIds, $dryRun);
        $this->reportStep('Backup', $backupCounts);

        $ppnChanged = $this->repairPpnJournalLines($accountIds, $dryRun);
        $this->reportStep('PPN journal lines', ['updated' => $ppnChanged]);

        $revenueChanged = $this->repairRevenueJournalLines($accountIds, $dryRun);
        $this->reportStep('Revenue journal lines (DO)', ['updated' => $revenueChanged]);

        $hppChanged = $this->repairHppJournalLines($accountIds, $dryRun);
        $this->reportStep('HPP journal lines (DO)', ['updated' => $hppChanged]);

        $salesOrderLineChanged = $this->repairSalesOrderLineAccounts($accountIds, $dryRun);
        $deliveryOrderLineChanged = $this->repairDeliveryOrderLineAccounts($accountIds, $dryRun);
        $this->reportStep('Document line account_id (sales + delivery)', [
            'sales_order_lines' => $salesOrderLineChanged,
            'delivery_order_lines' => $deliveryOrderLineChanged,
        ]);

        $this->reportUnmappedRows($accountIds);

        $this->newLine();
        $this->info($dryRun ? 'Dry run complete.' : 'Repair complete.');

        return self::SUCCESS;
    }

    /**
     * @return array{
     *     revenue_parent: int,
     *     hpp_parent: int,
     *     ppn_parent: int,
     *     ppn_leaf: int,
     *     stationery_revenue_leaf: int,
     *     stationery_hpp_leaf: int
     * }|null
     */
    private function resolveAccountIds(): ?array
    {
        $codes = [
            'revenue_parent' => self::REVENUE_PARENT_CODE,
            'hpp_parent' => self::HPP_PARENT_CODE,
            'ppn_parent' => self::PPN_PARENT_CODE,
            'ppn_leaf' => self::PPN_LEAF_CODE,
            'stationery_revenue_leaf' => self::STATIONERY_REVENUE_LEAF_CODE,
            'stationery_hpp_leaf' => self::STATIONERY_HPP_LEAF_CODE,
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
     * @param  list<int>  $parentAccountIds
     * @return array{journal_lines: int, sales_order_lines: int, delivery_order_lines: int}
     */
    private function backupAffectedRows(string $suffix, array $parentAccountIds, bool $dryRun): array
    {
        $placeholders = implode(',', array_fill(0, count($parentAccountIds), '?'));
        $counts = [
            'journal_lines' => 0,
            'sales_order_lines' => 0,
            'delivery_order_lines' => 0,
        ];

        $tables = [
            'journal_lines' => 'journal_lines',
            'sales_order_lines' => 'sales_order_lines',
            'delivery_order_lines' => 'delivery_order_lines',
        ];

        foreach ($tables as $key => $baseTable) {
            $backupTable = "{$baseTable}_{$suffix}";

            $rowCount = (int) DB::selectOne(
                "SELECT COUNT(*) AS aggregate FROM `{$baseTable}` WHERE account_id IN ({$placeholders})",
                $parentAccountIds
            )->aggregate;

            if ($rowCount === 0) {
                $counts[$key] = 0;

                continue;
            }

            if ($dryRun) {
                $counts[$key] = $rowCount;
                $this->line("  [dry-run] Would back up {$rowCount} row(s) from {$baseTable} to {$backupTable}");

                continue;
            }

            if (! Schema::hasTable($backupTable)) {
                DB::statement("CREATE TABLE `{$backupTable}` LIKE `{$baseTable}`");
            }

            DB::insert(
                "INSERT INTO `{$backupTable}` SELECT * FROM `{$baseTable}` WHERE account_id IN ({$placeholders})",
                $parentAccountIds
            );

            $counts[$key] = $rowCount;
            $this->line("  Backed up {$rowCount} row(s) from {$baseTable} to {$backupTable}");
        }

        return $counts;
    }

    /**
     * @param  array{
     *     revenue_parent: int,
     *     hpp_parent: int,
     *     ppn_parent: int,
     *     ppn_leaf: int,
     *     stationery_revenue_leaf: int,
     *     stationery_hpp_leaf: int
     * }  $accountIds
     */
    private function repairPpnJournalLines(array $accountIds, bool $dryRun): int
    {
        if ($dryRun) {
            return (int) DB::selectOne(
                'SELECT COUNT(*) AS aggregate FROM journal_lines WHERE account_id = ?',
                [$accountIds['ppn_parent']]
            )->aggregate;
        }

        return DB::update(
            'UPDATE journal_lines SET account_id = ? WHERE account_id = ?',
            [$accountIds['ppn_leaf'], $accountIds['ppn_parent']]
        );
    }

    /**
     * @param  array{
     *     revenue_parent: int,
     *     hpp_parent: int,
     *     ppn_parent: int,
     *     ppn_leaf: int,
     *     stationery_revenue_leaf: int,
     *     stationery_hpp_leaf: int
     * }  $accountIds
     */
    private function repairRevenueJournalLines(array $accountIds, bool $dryRun): int
    {
        $sourceType = DeliveryOrder::class;

        if ($dryRun) {
            return (int) DB::selectOne(
                'SELECT COUNT(*) AS aggregate
                 FROM journal_lines jl
                 INNER JOIN journals j ON j.id = jl.journal_id
                 INNER JOIN delivery_order_lines dol ON dol.delivery_order_id = j.source_id
                     AND dol.item_name = SUBSTRING(jl.memo, LOCATE(\' - \', jl.memo) + 3)
                 LEFT JOIN inventory_items ii ON ii.id = dol.inventory_item_id
                 LEFT JOIN product_categories pc ON pc.id = ii.category_id
                 WHERE j.source_type = ?
                   AND jl.account_id = ?
                   AND LOCATE(\' - \', jl.memo) > 0',
                [$sourceType, $accountIds['revenue_parent']]
            )->aggregate;
        }

        return DB::update(
            'UPDATE journal_lines jl
             INNER JOIN journals j ON j.id = jl.journal_id
             INNER JOIN delivery_order_lines dol ON dol.delivery_order_id = j.source_id
                 AND dol.item_name = SUBSTRING(jl.memo, LOCATE(\' - \', jl.memo) + 3)
             LEFT JOIN inventory_items ii ON ii.id = dol.inventory_item_id
             LEFT JOIN product_categories pc ON pc.id = ii.category_id
             SET jl.account_id = COALESCE(pc.sales_account_id, ?)
             WHERE j.source_type = ?
               AND jl.account_id = ?
               AND LOCATE(\' - \', jl.memo) > 0',
            [
                $accountIds['stationery_revenue_leaf'],
                $sourceType,
                $accountIds['revenue_parent'],
            ]
        );
    }

    /**
     * @param  array{
     *     revenue_parent: int,
     *     hpp_parent: int,
     *     ppn_parent: int,
     *     ppn_leaf: int,
     *     stationery_revenue_leaf: int,
     *     stationery_hpp_leaf: int
     * }  $accountIds
     */
    private function repairHppJournalLines(array $accountIds, bool $dryRun): int
    {
        $sourceType = DeliveryOrder::class;

        if ($dryRun) {
            return (int) DB::selectOne(
                'SELECT COUNT(*) AS aggregate
                 FROM journal_lines jl
                 INNER JOIN journals j ON j.id = jl.journal_id
                 INNER JOIN delivery_order_lines dol ON dol.delivery_order_id = j.source_id
                     AND dol.item_name = SUBSTRING(jl.memo, LOCATE(\' - \', jl.memo) + 3)
                 LEFT JOIN inventory_items ii ON ii.id = dol.inventory_item_id
                 LEFT JOIN product_categories pc ON pc.id = ii.category_id
                 WHERE j.source_type = ?
                   AND jl.account_id = ?
                   AND LOCATE(\' - \', jl.memo) > 0',
                [$sourceType, $accountIds['hpp_parent']]
            )->aggregate;
        }

        return DB::update(
            'UPDATE journal_lines jl
             INNER JOIN journals j ON j.id = jl.journal_id
             INNER JOIN delivery_order_lines dol ON dol.delivery_order_id = j.source_id
                 AND dol.item_name = SUBSTRING(jl.memo, LOCATE(\' - \', jl.memo) + 3)
             LEFT JOIN inventory_items ii ON ii.id = dol.inventory_item_id
             LEFT JOIN product_categories pc ON pc.id = ii.category_id
             SET jl.account_id = COALESCE(pc.cogs_account_id, ?)
             WHERE j.source_type = ?
               AND jl.account_id = ?
               AND LOCATE(\' - \', jl.memo) > 0',
            [
                $accountIds['stationery_hpp_leaf'],
                $sourceType,
                $accountIds['hpp_parent'],
            ]
        );
    }

    /**
     * @param  array{
     *     revenue_parent: int,
     *     hpp_parent: int,
     *     ppn_parent: int,
     *     ppn_leaf: int,
     *     stationery_revenue_leaf: int,
     *     stationery_hpp_leaf: int
     * }  $accountIds
     */
    private function repairSalesOrderLineAccounts(array $accountIds, bool $dryRun): int
    {
        if ($dryRun) {
            return (int) DB::selectOne(
                'SELECT COUNT(*) AS aggregate
                 FROM sales_order_lines sol
                 INNER JOIN inventory_items ii ON ii.id = sol.inventory_item_id
                 WHERE sol.account_id = ?',
                [$accountIds['revenue_parent']]
            )->aggregate;
        }

        return DB::update(
            'UPDATE sales_order_lines sol
             INNER JOIN inventory_items ii ON ii.id = sol.inventory_item_id
             LEFT JOIN product_categories pc ON pc.id = ii.category_id
             SET sol.account_id = COALESCE(pc.sales_account_id, ?)
             WHERE sol.account_id = ?',
            [$accountIds['stationery_revenue_leaf'], $accountIds['revenue_parent']]
        );
    }

    /**
     * @param  array{
     *     revenue_parent: int,
     *     hpp_parent: int,
     *     ppn_parent: int,
     *     ppn_leaf: int,
     *     stationery_revenue_leaf: int,
     *     stationery_hpp_leaf: int
     * }  $accountIds
     */
    private function repairDeliveryOrderLineAccounts(array $accountIds, bool $dryRun): int
    {
        if ($dryRun) {
            return (int) DB::selectOne(
                'SELECT COUNT(*) AS aggregate
                 FROM delivery_order_lines dol
                 INNER JOIN inventory_items ii ON ii.id = dol.inventory_item_id
                 WHERE dol.account_id = ?',
                [$accountIds['revenue_parent']]
            )->aggregate;
        }

        return DB::update(
            'UPDATE delivery_order_lines dol
             INNER JOIN inventory_items ii ON ii.id = dol.inventory_item_id
             LEFT JOIN product_categories pc ON pc.id = ii.category_id
             SET dol.account_id = COALESCE(pc.sales_account_id, ?)
             WHERE dol.account_id = ?',
            [$accountIds['stationery_revenue_leaf'], $accountIds['revenue_parent']]
        );
    }

    /**
     * @param  array{
     *     revenue_parent: int,
     *     hpp_parent: int,
     *     ppn_parent: int,
     *     ppn_leaf: int,
     *     stationery_revenue_leaf: int,
     *     stationery_hpp_leaf: int
     * }  $accountIds
     */
    private function reportUnmappedRows(array $accountIds): void
    {
        $this->newLine();
        $this->info('Remaining rows on parent accounts (could not be mapped or are outside DO join scope):');

        $journalUnmapped = DB::select(
            'SELECT jl.id, jl.account_id, jl.memo, j.source_type, j.source_id
             FROM journal_lines jl
             LEFT JOIN journals j ON j.id = jl.journal_id
             WHERE jl.account_id IN (?, ?, ?)
             ORDER BY jl.id
             LIMIT 50',
            [
                $accountIds['revenue_parent'],
                $accountIds['hpp_parent'],
                $accountIds['ppn_parent'],
            ]
        );

        $salesOrderUnmapped = DB::select(
            'SELECT id, order_id, account_id, item_name
             FROM sales_order_lines
             WHERE account_id = ?
             ORDER BY id
             LIMIT 50',
            [$accountIds['revenue_parent']]
        );

        $deliveryOrderUnmapped = DB::select(
            'SELECT id, delivery_order_id, account_id, item_name
             FROM delivery_order_lines
             WHERE account_id = ?
             ORDER BY id
             LIMIT 50',
            [$accountIds['revenue_parent']]
        );

        $journalCount = (int) DB::selectOne(
            'SELECT COUNT(*) AS aggregate FROM journal_lines WHERE account_id IN (?, ?, ?)',
            [
                $accountIds['revenue_parent'],
                $accountIds['hpp_parent'],
                $accountIds['ppn_parent'],
            ]
        )->aggregate;

        $salesOrderCount = (int) DB::selectOne(
            'SELECT COUNT(*) AS aggregate FROM sales_order_lines WHERE account_id = ?',
            [$accountIds['revenue_parent']]
        )->aggregate;

        $deliveryOrderCount = (int) DB::selectOne(
            'SELECT COUNT(*) AS aggregate FROM delivery_order_lines WHERE account_id = ?',
            [$accountIds['revenue_parent']]
        )->aggregate;

        $this->line("  journal_lines: {$journalCount}");
        if ($journalUnmapped !== []) {
            $this->table(
                ['id', 'account_id', 'memo', 'source_type', 'source_id'],
                array_map(fn ($row) => [
                    $row->id,
                    $row->account_id,
                    $row->memo,
                    $row->source_type,
                    $row->source_id,
                ], $journalUnmapped)
            );
            if ($journalCount > 50) {
                $this->warn('  …and '.($journalCount - 50).' more journal line(s).');
            }
        }

        $this->line("  sales_order_lines: {$salesOrderCount}");
        if ($salesOrderUnmapped !== []) {
            $this->table(
                ['id', 'order_id', 'account_id', 'item_name'],
                array_map(fn ($row) => [
                    $row->id,
                    $row->order_id,
                    $row->account_id,
                    $row->item_name,
                ], $salesOrderUnmapped)
            );
        }

        $this->line("  delivery_order_lines: {$deliveryOrderCount}");
        if ($deliveryOrderUnmapped !== []) {
            $this->table(
                ['id', 'delivery_order_id', 'account_id', 'item_name'],
                array_map(fn ($row) => [
                    $row->id,
                    $row->delivery_order_id,
                    $row->account_id,
                    $row->item_name,
                ], $deliveryOrderUnmapped)
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
