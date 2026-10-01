<?php

namespace App\Console\Commands;

use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\InventoryWarehouseStock;
use App\Models\Warehouse;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RepairWarehouseStockDrift extends Command
{
    protected $signature = 'inventory:repair-warehouse-stock-drift
                            {--item= : Limit to a single item code}
                            {--limit= : Maximum number of drifting items to process}
                            {--mode=per-warehouse : Repair mode: per-warehouse or zero-empty-ledger}
                            {--execute : Apply changes (requires --force)}
                            {--force : Confirm writing changes together with --execute}';

    protected $description = 'Repair inventory_warehouse_stock to match inventory transaction totals per warehouse (ledger is source of truth)';

    private int $itemsProcessed = 0;

    private int $itemsSkippedLedgerNonZero = 0;

    private int $rowsUpdated = 0;

    private int $rowsCreated = 0;

    private int $totalUnitDelta = 0;

    /** @var list<array<string, mixed>> */
    private array $reportRows = [];

    public function handle(): int
    {
        $mode = (string) $this->option('mode');

        if (! in_array($mode, ['per-warehouse', 'zero-empty-ledger'], true)) {
            $this->error("Invalid --mode '{$mode}'. Allowed: per-warehouse, zero-empty-ledger.");

            return self::FAILURE;
        }

        $execute = (bool) $this->option('execute');
        $force = (bool) $this->option('force');

        if ($execute && ! $force) {
            $this->error('Refusing to write: pass both --execute and --force to apply changes.');

            return self::FAILURE;
        }

        $items = $this->resolveItemsToProcess();

        if ($items === null) {
            return self::FAILURE;
        }

        if ($items->isEmpty()) {
            $this->info('No drifting inventory items found.');

            return self::SUCCESS;
        }

        $backupTable = null;

        if ($execute) {
            $backupTable = $this->createBackupTable();
            $this->info("Backup table created: {$backupTable}");
        } else {
            $this->comment('DRY-RUN mode: no database writes will be performed.');
        }

        foreach ($items as $item) {
            try {
                if ($execute) {
                    DB::transaction(function () use ($item, $mode): void {
                        if ($mode === 'zero-empty-ledger') {
                            $this->repairItemZeroEmptyLedger($item, true);
                        } else {
                            $this->repairItem($item, true);
                        }
                    });
                } elseif ($mode === 'zero-empty-ledger') {
                    $this->repairItemZeroEmptyLedger($item, false);
                } else {
                    $this->repairItem($item, false);
                }
            } catch (\Throwable $exception) {
                $this->error("Failed item {$item->code}: {$exception->getMessage()}");

                return self::FAILURE;
            }
        }

        if ($this->reportRows !== []) {
            if ($mode === 'zero-empty-ledger') {
                $this->table(
                    ['Code', 'Name', 'Old WH Stock', 'Aksi'],
                    array_map(static fn (array $row): array => [
                        $row['code'],
                        $row['name'],
                        $row['old_wh_total'],
                        $row['aksi'],
                    ], $this->reportRows)
                );
            } else {
                $this->table(
                    ['Code', 'Name', 'Old WH Stock', 'New WH Stock', 'Selisih', 'Aksi'],
                    array_map(static fn (array $row): array => [
                        $row['code'],
                        $row['name'],
                        $row['old_wh_total'],
                        $row['new_wh_total'],
                        $row['selisih'],
                        $row['aksi'],
                    ], $this->reportRows)
                );
            }
        }

        $modeLabel = $execute ? 'EXECUTE' : 'DRY-RUN';

        $this->newLine();
        $this->info("Summary ({$modeLabel}, mode={$mode}):");
        $this->line("Items processed: {$this->itemsProcessed}");

        if ($mode === 'zero-empty-ledger') {
            $this->line("Items skipped (ledger total not zero): {$this->itemsSkippedLedgerNonZero}");
            $this->line("Warehouse stock rows updated: {$this->rowsUpdated}");
            $this->line("Total units removed from warehouse stock: {$this->totalUnitDelta}");
        } else {
            $this->line("Warehouse stock rows updated: {$this->rowsUpdated}");
            $this->line("Warehouse stock rows created: {$this->rowsCreated}");
            $this->line("Total unit change (sum of |delta| per row): {$this->totalUnitDelta}");
        }

        if ($backupTable !== null) {
            $this->line("Backup table: {$backupTable}");
        }

        return self::SUCCESS;
    }

    /**
     * @return Collection<int, InventoryItem>|null null when --item code was not found
     */
    private function resolveItemsToProcess(): ?Collection
    {
        $itemCode = $this->option('item');

        if ($itemCode !== null && $itemCode !== '') {
            $item = InventoryItem::query()->where('code', $itemCode)->first();

            if ($item === null) {
                $this->error("Item with code '{$itemCode}' not found.");

                return null;
            }

            if ($item->item_type === 'service') {
                $this->warn('Service items have no warehouse stock.');

                return collect();
            }

            return collect([$item]);
        }

        $limit = $this->option('limit');
        $limitInt = $limit !== null && $limit !== '' ? max(1, (int) $limit) : null;

        $drifting = InventoryItem::query()
            ->where('item_type', '!=', 'service')
            ->orderBy('id')
            ->get()
            ->map(function (InventoryItem $item): ?array {
                $ledgerStock = (int) $item->current_stock;
                $warehouseTotal = (int) InventoryWarehouseStock::query()
                    ->where('item_id', $item->id)
                    ->sum('quantity_on_hand');

                if ($ledgerStock === $warehouseTotal) {
                    return null;
                }

                return [
                    'item' => $item,
                    'abs_diff' => abs($ledgerStock - $warehouseTotal),
                ];
            })
            ->filter()
            ->sortByDesc('abs_diff')
            ->values();

        if ($limitInt !== null) {
            $drifting = $drifting->take($limitInt);
        }

        return $drifting->pluck('item');
    }

    private function createBackupTable(): string
    {
        $suffix = now()->format('Ymd_His');
        $tableName = 'inventory_warehouse_stock_bak_'.$suffix;

        if (Schema::hasTable($tableName)) {
            throw new \RuntimeException("Backup table already exists: {$tableName}");
        }

        DB::statement("CREATE TABLE `{$tableName}` AS SELECT * FROM `inventory_warehouse_stock`");

        return $tableName;
    }

    private function ledgerTotalFromTransactions(InventoryItem $item): int
    {
        return (int) InventoryTransaction::query()
            ->where('item_id', $item->id)
            ->sum('quantity');
    }

    private function repairItemZeroEmptyLedger(InventoryItem $item, bool $persist): void
    {
        $ledgerTotal = $this->ledgerTotalFromTransactions($item);

        if ($ledgerTotal !== 0) {
            $this->itemsSkippedLedgerNonZero++;

            return;
        }

        $existingStocks = InventoryWarehouseStock::query()
            ->where('item_id', $item->id)
            ->get();

        $oldWhTotal = (int) $existingStocks->sum('quantity_on_hand');

        if ($oldWhTotal === 0) {
            return;
        }

        $this->itemsProcessed++;

        $actions = [];

        foreach ($existingStocks as $warehouseStock) {
            $oldQty = (int) $warehouseStock->quantity_on_hand;

            if ($oldQty === 0) {
                continue;
            }

            $actions[] = "set wh {$warehouseStock->warehouse_id}: {$oldQty} => 0";

            if ($persist) {
                $warehouseStock->quantity_on_hand = 0;
                $warehouseStock->updateAvailableQuantity();
                $warehouseStock->save();
            }

            $this->rowsUpdated++;
            $this->totalUnitDelta += $oldQty;
        }

        $this->reportRows[] = [
            'code' => $item->code,
            'name' => $item->name,
            'old_wh_total' => $oldWhTotal,
            'aksi' => $actions !== [] ? implode('; ', $actions) : 'no row changes',
        ];
    }

    /**
     * @return array<int, int>
     */
    private function warehouseTotalsFromTransactions(InventoryItem $item): array
    {
        $transactions = InventoryTransaction::query()->where('item_id', $item->id)->get();

        $fallbackWarehouseId = $item->default_warehouse_id ?? Warehouse::query()->min('id');

        $totals = [];

        foreach ($transactions->groupBy(function ($transaction) use ($fallbackWarehouseId) {
            return $transaction->warehouse_id ?? $fallbackWarehouseId;
        }) as $warehouseId => $group) {
            $totals[(int) $warehouseId] = (int) $group->sum('quantity');
        }

        return $totals;
    }

    private function repairItem(InventoryItem $item, bool $persist): void
    {
        $warehouseTotals = $this->warehouseTotalsFromTransactions($item);
        $newWhTotal = array_sum($warehouseTotals);

        $oldWhTotal = (int) InventoryWarehouseStock::query()
            ->where('item_id', $item->id)
            ->sum('quantity_on_hand');

        if ($oldWhTotal === $newWhTotal && $this->warehouseRowsMatchTotals($item, $warehouseTotals)) {
            return;
        }

        $this->itemsProcessed++;

        $actions = [];

        $existingStocks = InventoryWarehouseStock::query()
            ->where('item_id', $item->id)
            ->get();

        $processedWarehouseIds = [];

        foreach ($warehouseTotals as $warehouseId => $targetQuantity) {
            $processedWarehouseIds[] = $warehouseId;

            $warehouseStock = $existingStocks->firstWhere('warehouse_id', $warehouseId);

            if ($warehouseStock === null) {
                $actions[] = "create wh {$warehouseId} => {$targetQuantity}";
                if ($persist) {
                    $warehouseStock = InventoryWarehouseStock::query()->create([
                        'item_id' => $item->id,
                        'warehouse_id' => $warehouseId,
                        'quantity_on_hand' => $targetQuantity,
                        'reserved_quantity' => 0,
                        'available_quantity' => $targetQuantity,
                        'min_stock_level' => 0,
                        'max_stock_level' => 0,
                        'reorder_point' => 0,
                    ]);
                    $this->rowsCreated++;
                    $this->totalUnitDelta += abs($targetQuantity);
                } else {
                    $this->rowsCreated++;
                    $this->totalUnitDelta += abs($targetQuantity);
                }

                continue;
            }

            $oldQty = (int) $warehouseStock->quantity_on_hand;

            if ($oldQty === $targetQuantity) {
                continue;
            }

            $actions[] = "update wh {$warehouseId}: {$oldQty} => {$targetQuantity}";

            if ($persist) {
                $warehouseStock->quantity_on_hand = $targetQuantity;
                $warehouseStock->updateAvailableQuantity();
                $warehouseStock->save();
            }

            $this->rowsUpdated++;
            $this->totalUnitDelta += abs($targetQuantity - $oldQty);
        }

        foreach ($existingStocks as $existingStock) {
            if (in_array($existingStock->warehouse_id, $processedWarehouseIds, true)) {
                continue;
            }

            $oldQty = (int) $existingStock->quantity_on_hand;

            if ($oldQty === 0) {
                continue;
            }

            $actions[] = "zero wh {$existingStock->warehouse_id}: {$oldQty} => 0";

            if ($persist) {
                $existingStock->quantity_on_hand = 0;
                $existingStock->updateAvailableQuantity();
                $existingStock->save();
            }

            $this->rowsUpdated++;
            $this->totalUnitDelta += abs($oldQty);
        }

        $this->reportRows[] = [
            'code' => $item->code,
            'name' => $item->name,
            'old_wh_total' => $oldWhTotal,
            'new_wh_total' => $newWhTotal,
            'selisih' => $newWhTotal - $oldWhTotal,
            'aksi' => $actions !== [] ? implode('; ', $actions) : 'no row changes',
        ];
    }

    /**
     * @param  array<int, int>  $warehouseTotals
     */
    private function warehouseRowsMatchTotals(InventoryItem $item, array $warehouseTotals): bool
    {
        $existingStocks = InventoryWarehouseStock::query()
            ->where('item_id', $item->id)
            ->get();

        foreach ($warehouseTotals as $warehouseId => $targetQuantity) {
            $row = $existingStocks->firstWhere('warehouse_id', $warehouseId);

            if ($row === null || (int) $row->quantity_on_hand !== $targetQuantity) {
                return false;
            }
        }

        foreach ($existingStocks as $existingStock) {
            if (! array_key_exists((int) $existingStock->warehouse_id, $warehouseTotals)
                && (int) $existingStock->quantity_on_hand !== 0) {
                return false;
            }
        }

        return true;
    }
}
