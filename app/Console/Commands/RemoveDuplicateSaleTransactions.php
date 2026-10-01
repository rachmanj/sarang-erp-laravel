<?php

namespace App\Console\Commands;

use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Services\InventoryService;
use App\Services\InventoryWarehouseStockService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RemoveDuplicateSaleTransactions extends Command
{
    protected $signature = 'inventory:remove-duplicate-sale-transactions
                            {--item= : Limit to a single item code}
                            {--limit= : Maximum number of duplicate groups to process}
                            {--execute : Apply changes (requires --force)}
                            {--force : Confirm writing changes together with --execute}';

    protected $description = 'Remove duplicate sale inventory rows for the same delivery order line, restore warehouse stock, and recalculate valuation';

    private int $duplicateGroupCount = 0;

    private int $rowsDeleted = 0;

    private int $totalUnitsRestored = 0;

    private int $itemsRevalued = 0;

    private int $itemsWouldRevalue = 0;

    /** @var list<array<string, mixed>> */
    private array $reportRows = [];

    public function handle(): int
    {
        $execute = (bool) $this->option('execute');
        $force = (bool) $this->option('force');

        if ($execute && ! $force) {
            $this->error('Refusing to write: pass both --execute and --force to apply changes.');

            return self::FAILURE;
        }

        $itemFilter = $this->resolveItemFilter();
        if ($itemFilter === false) {
            return self::FAILURE;
        }

        $groups = $this->findDuplicateGroups($itemFilter);

        if ($groups->isEmpty()) {
            $this->info('No duplicate sale transactions found for delivery order lines.');

            return self::SUCCESS;
        }

        $limit = $this->option('limit');
        if ($limit !== null && $limit !== '') {
            $groups = $groups->take(max(1, (int) $limit));
        }

        $this->buildReport($groups);

        if ($this->reportRows !== []) {
            $this->table(
                ['Item', 'DO Line Ref', 'Txn IDs', 'Kept', 'Deleted', 'Units Restored', 'Aksi'],
                array_map(static fn (array $row): array => [
                    $row['item_code'],
                    $row['reference_id'],
                    $row['txn_ids'],
                    $row['kept_id'],
                    $row['deleted_ids'],
                    $row['units_restored'],
                    $row['aksi'],
                ], $this->reportRows)
            );
        }

        $transactionsBackup = null;
        $warehouseStockBackup = null;

        if ($execute) {
            [$transactionsBackup, $warehouseStockBackup] = $this->createBackupTables();
            $this->info("Backup tables created: {$transactionsBackup}, {$warehouseStockBackup}");

            $this->applyFixes($groups);
        } else {
            $this->comment('DRY-RUN mode: no database writes will be performed.');
        }

        $modeLabel = $execute ? 'EXECUTE' : 'DRY-RUN';

        $this->newLine();
        $this->info("Summary ({$modeLabel}):");
        $this->line("Duplicate groups: {$this->duplicateGroupCount}");
        $this->line('Transaction rows '.($execute ? 'deleted' : 'would delete').": {$this->rowsDeleted}");
        $this->line('Total units restored to warehouse stock: '.$this->totalUnitsRestored);
        $revaluedLabel = $execute
            ? (string) $this->itemsRevalued
            : "{$this->itemsWouldRevalue} (would revalue, DRY-RUN)";
        $this->line("Items revalued: {$revaluedLabel}");

        if ($transactionsBackup !== null && $warehouseStockBackup !== null) {
            $this->line("Backup inventory_transactions: {$transactionsBackup}");
            $this->line("Backup inventory_warehouse_stock: {$warehouseStockBackup}");
        }

        return self::SUCCESS;
    }

    /**
     * @return int|null false when --item code was not found
     */
    private function resolveItemFilter(): int|null|false
    {
        $itemCode = $this->option('item');

        if ($itemCode === null || $itemCode === '') {
            return null;
        }

        $item = InventoryItem::query()->where('code', $itemCode)->first();

        if ($item === null) {
            $this->error("Item with code '{$itemCode}' not found.");

            return false;
        }

        return (int) $item->id;
    }

    /**
     * @return Collection<int, Collection<int, InventoryTransaction>>
     */
    private function findDuplicateGroups(?int $itemId): Collection
    {
        $referenceIds = InventoryTransaction::query()
            ->select('reference_id')
            ->where('reference_type', 'delivery_order_line')
            ->where('transaction_type', 'sale')
            ->when($itemId !== null, fn ($query) => $query->where('item_id', $itemId))
            ->groupBy('reference_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('reference_id');

        if ($referenceIds->isEmpty()) {
            return collect();
        }

        $transactions = InventoryTransaction::query()
            ->where('reference_type', 'delivery_order_line')
            ->where('transaction_type', 'sale')
            ->whereIn('reference_id', $referenceIds)
            ->when($itemId !== null, fn ($query) => $query->where('item_id', $itemId))
            ->orderBy('reference_id')
            ->orderBy('id')
            ->get()
            ->groupBy('reference_id');

        return $transactions->filter(fn (Collection $group) => $group->count() > 1);
    }

    /**
     * @param  Collection<int, Collection<int, InventoryTransaction>>  $groups
     */
    private function buildReport(Collection $groups): void
    {
        $itemCodes = InventoryItem::query()
            ->whereIn('id', $groups->flatten(1)->pluck('item_id')->unique())
            ->pluck('code', 'id');

        $this->itemsWouldRevalue = $groups->flatten(1)->pluck('item_id')->unique()->count();

        foreach ($groups as $referenceId => $group) {
            $sorted = $group->sortBy('id')->values();
            $keep = $sorted->first();
            $toDelete = $sorted->slice(1);
            $unitsRestored = 0;
            $aksiParts = [];

            foreach ($toDelete as $transaction) {
                if ($transaction->warehouse_id !== null) {
                    $unitsRestored += abs((int) $transaction->quantity);
                    $aksiParts[] = "restore wh {$transaction->warehouse_id} +".abs((int) $transaction->quantity);
                } else {
                    $aksiParts[] = 'delete txn '.$transaction->id.' (no stock restore: warehouse_id NULL)';
                }
            }

            $this->duplicateGroupCount++;
            $this->rowsDeleted += $toDelete->count();
            $this->totalUnitsRestored += $unitsRestored;

            $this->reportRows[] = [
                'item_code' => $itemCodes[$keep->item_id] ?? (string) $keep->item_id,
                'reference_id' => $referenceId,
                'txn_ids' => $sorted->pluck('id')->implode(', '),
                'kept_id' => $keep->id,
                'deleted_ids' => $toDelete->pluck('id')->implode(', '),
                'units_restored' => $unitsRestored,
                'aksi' => $aksiParts !== [] ? implode('; ', $aksiParts) : ($this->option('execute') ? 'DELETE' : 'DRY-RUN'),
            ];
        }
    }

    /**
     * @param  Collection<int, Collection<int, InventoryTransaction>>  $groups
     */
    private function applyFixes(Collection $groups): void
    {
        $inventoryService = app(InventoryService::class);
        $warehouseStockService = app(InventoryWarehouseStockService::class);

        $byItem = $groups->flatten(1)->groupBy('item_id');

        foreach ($byItem as $itemId => $transactions) {
            $item = InventoryItem::query()->findOrFail((int) $itemId);

            $deleteIds = collect();
            $restoreDeltas = [];

            foreach ($transactions->groupBy('reference_id') as $group) {
                $sorted = $group->sortBy('id')->values();
                foreach ($sorted->slice(1) as $transaction) {
                    $deleteIds->push($transaction->id);

                    if ($transaction->warehouse_id === null) {
                        continue;
                    }

                    $restoreDeltas[] = [
                        'warehouse_id' => (int) $transaction->warehouse_id,
                        'qty' => abs((int) $transaction->quantity),
                    ];
                }
            }

            if ($deleteIds->isEmpty()) {
                continue;
            }

            DB::transaction(function () use (
                $item,
                $deleteIds,
                $restoreDeltas,
                $warehouseStockService,
                $inventoryService,
            ): void {
                InventoryTransaction::query()->whereIn('id', $deleteIds)->delete();

                foreach ($restoreDeltas as $delta) {
                    $warehouseStockService->applyDelta(
                        (int) $item->id,
                        (int) $delta['warehouse_id'],
                        (int) $delta['qty']
                    );
                }

                $inventoryService->updateItemValuationAfterDataRepair($item->fresh());
            });

            $this->itemsRevalued++;
        }
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function createBackupTables(): array
    {
        $suffix = now()->format('Ymd_His');
        $transactionsTable = 'inventory_transactions_bak_'.$suffix;
        $warehouseStockTable = 'inventory_warehouse_stock_bak_'.$suffix;

        if (Schema::hasTable($transactionsTable)) {
            throw new \RuntimeException("Backup table already exists: {$transactionsTable}");
        }

        if (Schema::hasTable($warehouseStockTable)) {
            throw new \RuntimeException("Backup table already exists: {$warehouseStockTable}");
        }

        DB::statement("CREATE TABLE `{$transactionsTable}` AS SELECT * FROM `inventory_transactions`");
        DB::statement("CREATE TABLE `{$warehouseStockTable}` AS SELECT * FROM `inventory_warehouse_stock`");

        return [$transactionsTable, $warehouseStockTable];
    }
}
