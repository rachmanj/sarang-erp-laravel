<?php

namespace App\Console\Commands;

use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\InventoryValuation;
use App\Models\InventoryWarehouseStock;
use App\Models\Warehouse;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixDuplicateInventoryTransaction extends Command
{
    protected $signature = 'inventory:fix-duplicate-transaction
                            {--item=WOR000037 : Item code or ID to fix}
                            {--dry-run : Show what would be done without making changes}';

    protected $description = 'Find and remove duplicate inventory transactions for an item, then recalculate stock and valuation';

    public function handle(): int
    {
        $itemIdentifier = $this->option('item');
        $dryRun = $this->option('dry-run');

        $item = is_numeric($itemIdentifier)
            ? InventoryItem::find($itemIdentifier)
            : InventoryItem::where('code', $itemIdentifier)->first();

        if (!$item) {
            $this->error("Item not found: {$itemIdentifier}");
            return 1;
        }

        $this->info("Checking item: {$item->code} - {$item->name} (ID: {$item->id})");

        $transactions = InventoryTransaction::where('item_id', $item->id)
            ->orderBy('transaction_date')
            ->orderBy('created_at')
            ->get();

        $duplicateGroups = $transactions->groupBy(fn ($t) => ($t->reference_type ?? '') . '|' . ($t->reference_id ?? ''))
            ->filter(fn ($group) => $group->count() > 1);

        if ($duplicateGroups->isEmpty()) {
            $this->info('No duplicate transactions found.');
            return 0;
        }

        $toDelete = collect();
        foreach ($duplicateGroups as $key => $group) {
            $keep = $group->first();
            $duplicates = $group->slice(1);
            foreach ($duplicates as $dup) {
                $toDelete->push($dup);
                $this->warn("  Duplicate: transaction #{$dup->id} (ref: {$dup->reference_type} #{$dup->reference_id}, created {$dup->created_at})");
            }
        }

        if ($dryRun) {
            $this->info('[DRY RUN] Would delete ' . $toDelete->count() . ' duplicate transaction(s).');
            return 0;
        }

        $deleteIds = $toDelete->pluck('id')->toArray();

        DB::transaction(function () use ($item, $deleteIds) {
            InventoryTransaction::whereIn('id', $deleteIds)->delete();

            $transactions = InventoryTransaction::where('item_id', $item->id)->get();
            $totalQuantity = $transactions->sum('quantity');
            $totalValue = $transactions->sum('total_cost');

            $fallbackWarehouseId = $item->default_warehouse_id ?? Warehouse::min('id');

            $warehouseTotals = $transactions->groupBy(fn ($t) => $t->warehouse_id ?? $fallbackWarehouseId)
                ->map(fn ($group) => $group->sum('quantity'));

            foreach ($warehouseTotals as $warehouseId => $qty) {
                $stock = InventoryWarehouseStock::firstOrCreate(
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
                $stock->quantity_on_hand = $qty;
                $stock->available_quantity = $qty - $stock->reserved_quantity;
                $stock->save();
            }

            $latestValuation = InventoryValuation::where('item_id', $item->id)
                ->orderByDesc('valuation_date')
                ->orderByDesc('id')
                ->first();

            if ($latestValuation && $totalQuantity > 0) {
                $unitCost = $totalValue / $totalQuantity;
                $latestValuation->update([
                    'quantity_on_hand' => $totalQuantity,
                    'unit_cost' => $unitCost,
                    'total_value' => $totalValue,
                ]);
            }
        });

        $this->info('Fixed: removed ' . count($deleteIds) . ' duplicate transaction(s).');
        return 0;
    }
}
