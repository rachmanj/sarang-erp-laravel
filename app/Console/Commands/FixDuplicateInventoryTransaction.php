<?php

namespace App\Console\Commands;

use App\Models\Accounting\PurchaseInvoice;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\InventoryWarehouseStock;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FixDuplicateInventoryTransaction extends Command
{
    protected $signature = 'inventory:fix-duplicate-transaction
                            {--item= : Item code or ID to fix}
                            {--invoice= : Purchase invoice ID or invoice number (e.g. 71260300107) — fixes all duplicated items on that PI}
                            {--dry-run : Show what would be done without making changes}
                            {--force : Apply without confirmation when using --invoice}';

    protected $description = 'Remove duplicate purchase inventory rows (same PI + item), then recalculate warehouse stock and valuation';

    public function handle(InventoryService $inventoryService): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $invoiceOption = $this->option('invoice');
        $itemOption = $this->option('item');

        if ($invoiceOption !== null && $invoiceOption !== '') {
            return $this->fixPurchaseInvoice((string) $invoiceOption, $inventoryService, $dryRun);
        }

        if ($itemOption === null || $itemOption === '') {
            $this->error('Provide --item=CODE|ID or --invoice=PI_NUMBER|ID.');

            return self::FAILURE;
        }

        $item = is_numeric($itemOption)
            ? InventoryItem::find((int) $itemOption)
            : InventoryItem::where('code', $itemOption)->first();

        if (! $item) {
            $this->error("Item not found: {$itemOption}");

            return self::FAILURE;
        }

        return $this->fixItems(collect([$item]), $inventoryService, $dryRun);
    }

    private function fixPurchaseInvoice(string $identifier, InventoryService $inventoryService, bool $dryRun): int
    {
        $identifier = trim($identifier);

        $invoice = is_numeric($identifier)
            ? PurchaseInvoice::query()->find((int) $identifier)
            : PurchaseInvoice::query()->where('invoice_no', $identifier)->first();

        if (! $invoice) {
            $this->error("Purchase invoice not found: {$identifier}");

            return self::FAILURE;
        }

        $this->info("Purchase Invoice: {$invoice->invoice_no} (ID {$invoice->id})");

        $itemIds = InventoryTransaction::query()
            ->where('reference_type', 'purchase_invoice')
            ->where('reference_id', $invoice->id)
            ->where('transaction_type', 'purchase')
            ->pluck('item_id')
            ->unique()
            ->values();

        $items = InventoryItem::query()->whereIn('id', $itemIds)->orderBy('code')->get();

        if ($items->isEmpty()) {
            $this->warn('No purchase inventory transactions found for this invoice.');

            return self::SUCCESS;
        }

        if (! $dryRun && ! $this->option('force') && ! $this->confirm('Remove duplicate purchase rows and recalculate stock for all affected items?', true)) {
            $this->comment('Cancelled.');

            return self::SUCCESS;
        }

        return $this->fixItems($items, $inventoryService, $dryRun, (int) $invoice->id);
    }

    /**
     * @param  Collection<int, InventoryItem>  $items
     */
    private function fixItems(Collection $items, InventoryService $inventoryService, bool $dryRun, ?int $purchaseInvoiceId = null): int
    {
        $totalDeleted = 0;
        $itemsFixed = 0;
        $failures = [];

        foreach ($items as $item) {
            $toDelete = $this->findDuplicatesToDelete($item, $purchaseInvoiceId);

            if ($toDelete->isEmpty()) {
                continue;
            }

            $itemsFixed++;
            $this->info("Item {$item->code} — {$item->name} (ID {$item->id})");

            foreach ($toDelete as $transaction) {
                $this->warn("  Delete duplicate txn #{$transaction->id} (ref: {$transaction->reference_type} #{$transaction->reference_id}, created {$transaction->created_at})");
            }

            if ($dryRun) {
                $totalDeleted += $toDelete->count();

                continue;
            }

            try {
                DB::transaction(function () use ($item, $toDelete, $inventoryService): void {
                    InventoryTransaction::query()->whereIn('id', $toDelete->pluck('id'))->delete();
                    $this->recalculateItemStock($item);
                    $inventoryService->updateItemValuationAfterDataRepair($item->fresh());
                });

                $totalDeleted += $toDelete->count();
            } catch (\Throwable $exception) {
                $failures[] = "{$item->code} (#{$item->id}): {$exception->getMessage()}";
                $this->error("  Failed: {$exception->getMessage()}");
            }
        }

        if ($totalDeleted === 0) {
            $this->info('No duplicate transactions found.');

            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->info("[DRY RUN] Would delete {$totalDeleted} duplicate transaction(s) across {$itemsFixed} item(s).");

            return self::SUCCESS;
        }

        $this->info("Fixed {$itemsFixed} item(s); removed {$totalDeleted} duplicate transaction(s).");

        if ($failures !== []) {
            $this->newLine();
            $this->error(count($failures).' item(s) failed:');
            foreach ($failures as $failure) {
                $this->line("  - {$failure}");
            }

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * @return Collection<int, InventoryTransaction>
     */
    private function findDuplicatesToDelete(InventoryItem $item, ?int $purchaseInvoiceId = null): Collection
    {
        $query = InventoryTransaction::query()
            ->where('item_id', $item->id)
            ->where('transaction_type', 'purchase')
            ->orderBy('transaction_date')
            ->orderBy('id');

        if ($purchaseInvoiceId !== null) {
            $query->where('reference_type', 'purchase_invoice')
                ->where('reference_id', $purchaseInvoiceId);
        }

        $duplicateGroups = $query->get()
            ->groupBy(fn (InventoryTransaction $transaction) => ($transaction->reference_type ?? '').'|'.($transaction->reference_id ?? ''))
            ->filter(fn (Collection $group) => $group->count() > 1);

        $toDelete = collect();

        foreach ($duplicateGroups as $group) {
            $keep = $this->selectTransactionToKeep($group);
            foreach ($group as $transaction) {
                if ($transaction->id !== $keep->id) {
                    $toDelete->push($transaction);
                }
            }
        }

        return $toDelete;
    }

    /**
     * @param  Collection<int, InventoryTransaction>  $group
     */
    private function selectTransactionToKeep(Collection $group): InventoryTransaction
    {
        $withLineId = $group->filter(fn (InventoryTransaction $transaction) => $transaction->purchase_invoice_line_id !== null);

        if ($withLineId->isNotEmpty()) {
            return $withLineId->sortBy('id')->first();
        }

        return $group->sortBy('id')->first();
    }

    private function recalculateItemStock(InventoryItem $item): void
    {
        $transactions = InventoryTransaction::query()->where('item_id', $item->id)->get();
        $fallbackWarehouseId = $item->default_warehouse_id ?? Warehouse::query()->min('id');

        $warehouseTotals = $transactions
            ->groupBy(fn (InventoryTransaction $transaction) => $transaction->warehouse_id ?? $fallbackWarehouseId)
            ->map(fn (Collection $group) => $group->sum('quantity'));

        $warehouseIds = $warehouseTotals->keys()->filter()->all();

        foreach ($warehouseTotals as $warehouseId => $quantity) {
            $stock = InventoryWarehouseStock::query()->firstOrCreate(
                ['item_id' => $item->id, 'warehouse_id' => $warehouseId],
                [
                    'quantity_on_hand' => 0,
                    'reserved_quantity' => 0,
                    'available_quantity' => 0,
                    'min_stock_level' => 0,
                    'max_stock_level' => 0,
                    'reorder_point' => 0,
                ]
            );

            $stock->quantity_on_hand = $quantity;
            $stock->available_quantity = $quantity - $stock->reserved_quantity;
            $stock->save();
        }

        InventoryWarehouseStock::query()
            ->where('item_id', $item->id)
            ->when($warehouseIds !== [], fn ($query) => $query->whereNotIn('warehouse_id', $warehouseIds))
            ->update([
                'quantity_on_hand' => 0,
                'available_quantity' => 0,
            ]);
    }
}
