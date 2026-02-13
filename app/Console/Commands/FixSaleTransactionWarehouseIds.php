<?php

namespace App\Console\Commands;

use App\Models\DeliveryOrderLine;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\InventoryWarehouseStock;
use App\Models\SalesOrder;
use App\Models\Warehouse;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixSaleTransactionWarehouseIds extends Command
{
    protected $signature = 'inventory:fix-sale-transaction-warehouse-ids 
                            {--dry-run : Show what would be done without making changes}
                            {--recalculate : Recalculate warehouse stock for affected items}
                            {--recalculate-item= : Recalculate warehouse stock for specific item code (e.g. WOR000086)}';

    protected $description = 'Fix sale transactions with null warehouse_id by backfilling from delivery order or sales order';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $recalculate = $this->option('recalculate');

        if ($dryRun) {
            $this->warn('Dry run mode - no changes will be made');
        }

        $transactions = InventoryTransaction::where('transaction_type', 'sale')
            ->whereNull('warehouse_id')
            ->get();

        $fixed = 0;
        $affectedItemIds = [];

        foreach ($transactions as $transaction) {
            $warehouseId = null;

            if ($transaction->reference_type === 'delivery_order_line' && $transaction->reference_id) {
                $line = DeliveryOrderLine::with('deliveryOrder')->find($transaction->reference_id);
                if ($line?->deliveryOrder) {
                    $warehouseId = $line->deliveryOrder->warehouse_id;
                }
            } elseif ($transaction->reference_type === 'sales_order' && $transaction->reference_id) {
                $so = SalesOrder::find($transaction->reference_id);
                if ($so) {
                    $warehouseId = $so->warehouse_id;
                }
            }

            if (!$warehouseId && $transaction->item_id) {
                $item = InventoryItem::find($transaction->item_id);
                $warehouseId = $item?->default_warehouse_id ?? Warehouse::min('id');
            }

            if ($warehouseId) {
                if (!$dryRun) {
                    $transaction->update(['warehouse_id' => $warehouseId]);
                }
                $this->line(sprintf(
                    'Transaction %d (item %d): warehouse_id set to %d',
                    $transaction->id,
                    $transaction->item_id,
                    $warehouseId
                ));
                $fixed++;
                $affectedItemIds[$transaction->item_id] = true;
            } else {
                $this->warn("Transaction {$transaction->id}: could not determine warehouse_id, skipping");
            }
        }

        $this->info($dryRun ? "Would fix {$fixed} transaction(s)" : "Fixed {$fixed} transaction(s)");

        if (!$dryRun) {
            $recalculateItemCode = $this->option('recalculate-item');
            if ($recalculateItemCode) {
                $item = InventoryItem::where('code', $recalculateItemCode)->first();
                if ($item) {
                    $this->info("Recalculating warehouse stock for {$recalculateItemCode}...");
                    $this->recalculateItemWarehouseStock($item->id);
                } else {
                    $this->error("Item with code '{$recalculateItemCode}' not found.");
                }
            } elseif ($recalculate && !empty($affectedItemIds)) {
                $this->info('Recalculating warehouse stock for affected items...');
                foreach (array_keys($affectedItemIds) as $itemId) {
                    $this->recalculateItemWarehouseStock($itemId);
                }
            }
        }

        return 0;
    }

    private function recalculateItemWarehouseStock(int $itemId): void
    {
        $item = InventoryItem::find($itemId);
        if (!$item || $item->item_type === 'service') {
            return;
        }

        DB::transaction(function () use ($item) {
            $transactions = InventoryTransaction::where('item_id', $item->id)->get();
            $fallbackWarehouseId = $item->default_warehouse_id ?? Warehouse::min('id');

            $warehouseTotals = $transactions->groupBy(fn ($t) => $t->warehouse_id ?? $fallbackWarehouseId)
                ->map(fn ($group) => $group->sum('quantity'));

            foreach ($warehouseTotals as $warehouseId => $totalQuantity) {
                $warehouseStock = InventoryWarehouseStock::firstOrCreate(
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
                $warehouseStock->quantity_on_hand = $totalQuantity;
                $warehouseStock->updateAvailableQuantity();
                $warehouseStock->save();
            }
        });

        $this->line("  Recalculated: {$item->code} - {$item->name}");
    }
}
