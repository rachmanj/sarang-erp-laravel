<?php

namespace App\Console\Commands;

use App\Models\InventoryItem;
use App\Models\InventoryWarehouseStock;
use Illuminate\Console\Command;

class CheckInventoryAccuracy extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:check-accuracy {item_code? : Specific item code to check}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check accuracy between inventory item current_stock and warehouse stock totals';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $itemCode = $this->argument('item_code');

        if ($itemCode) {
            $this->checkItem($itemCode);
        } else {
            $this->checkAllItems();
        }

        return 0;
    }

    /**
     * Check a specific item
     */
    private function checkItem($itemCode)
    {
        $item = InventoryItem::where('code', $itemCode)->first();

        if (!$item) {
            $this->error("Item with code '{$itemCode}' not found.");
            return;
        }

        $this->info("Checking item: {$item->code} - {$item->name}");
        $this->line("Item ID: {$item->id}");
        $this->line("Item Type: {$item->item_type}");

        // Skip services
        if ($item->item_type === 'service') {
            $this->warn("Skipping service item (services don't have stock).");
            return;
        }

        // Get current stock from transactions
        $currentStock = $item->current_stock;
        $this->line("Current Stock (from transactions): {$currentStock}");

        // Get warehouse stock total
        $warehouseStockTotal = InventoryWarehouseStock::where('item_id', $item->id)
            ->sum('quantity_on_hand');
        $this->line("Warehouse Stock Total: {$warehouseStockTotal}");

        // Calculate difference
        $difference = $currentStock - $warehouseStockTotal;
        $this->line("Difference: {$difference}");

        // Show warehouse breakdown
        $warehouseStocks = InventoryWarehouseStock::where('item_id', $item->id)
            ->with('warehouse')
            ->get();

        if ($warehouseStocks->isEmpty()) {
            $this->warn("No warehouse stock records found for this item.");
            if ($currentStock > 0) {
                $this->error("⚠️  ISSUE: Item has stock ({$currentStock}) but no warehouse records!");
            }
        } else {
            $this->line("\nWarehouse Breakdown:");
            $this->table(
                ['Warehouse Code', 'Warehouse Name', 'Quantity On Hand'],
                $warehouseStocks->map(function ($ws) {
                    return [
                        $ws->warehouse->code ?? 'N/A',
                        $ws->warehouse->name ?? 'N/A',
                        $ws->quantity_on_hand
                    ];
                })->toArray()
            );
        }

        // Show transaction breakdown
        $transactions = \App\Models\InventoryTransaction::where('item_id', $item->id)
            ->orderBy('transaction_date')
            ->orderBy('created_at')
            ->get(['id', 'transaction_type', 'quantity', 'warehouse_id', 'transaction_date']);

        if ($transactions->isNotEmpty()) {
            $this->line("\nTransaction Breakdown:");
            $transactionsByWarehouse = $transactions->groupBy(function ($t) {
                return $t->warehouse_id ?? 'NULL';
            });

            foreach ($transactionsByWarehouse as $warehouseId => $group) {
                $warehouseName = $warehouseId === 'NULL' ? 'No Warehouse' : (\App\Models\Warehouse::find($warehouseId)->name ?? "Warehouse ID: {$warehouseId}");
                $total = $group->sum('quantity');
                $this->line("  {$warehouseName}: {$total} units (" . $group->count() . " transactions)");
            }

            $transactionsWithoutWarehouse = $transactions->whereNull('warehouse_id');
            if ($transactionsWithoutWarehouse->isNotEmpty()) {
                $this->warn("\n⚠️  Found " . $transactionsWithoutWarehouse->count() . " transactions without warehouse_id!");
                $this->warn("   These transactions contribute to current_stock but not to warehouse stock.");
            }
        }

        // Check for discrepancies
        if ($difference != 0) {
            $this->error("\n⚠️  DISCREPANCY DETECTED!");
            if ($difference > 0) {
                $this->warn("Current stock is {$difference} units MORE than warehouse stock total.");
                $this->warn("This suggests stock exists in transactions but not allocated to warehouses.");
            } else {
                $this->warn("Current stock is " . abs($difference) . " units LESS than warehouse stock total.");
                $this->warn("This suggests warehouse stock records exist but transactions don't match.");
            }
        } else {
            $this->info("\n✅ Stock accuracy verified: Current stock matches warehouse stock total.");
        }
    }

    /**
     * Check all items
     */
    private function checkAllItems()
    {
        $this->info("Checking inventory accuracy for all items...\n");

        $items = InventoryItem::where('item_type', '!=', 'service')->get();
        $totalItems = $items->count();
        $discrepancies = [];
        $itemsWithoutWarehouseStock = [];

        $bar = $this->output->createProgressBar($totalItems);
        $bar->start();

        foreach ($items as $item) {
            $currentStock = $item->current_stock;
            $warehouseStockTotal = InventoryWarehouseStock::where('item_id', $item->id)
                ->sum('quantity_on_hand');
            $difference = $currentStock - $warehouseStockTotal;

            if ($difference != 0) {
                $discrepancies[] = [
                    'code' => $item->code,
                    'name' => $item->name,
                    'current_stock' => $currentStock,
                    'warehouse_stock' => $warehouseStockTotal,
                    'difference' => $difference
                ];
            }

            if ($currentStock > 0 && $warehouseStockTotal == 0) {
                $itemsWithoutWarehouseStock[] = [
                    'code' => $item->code,
                    'name' => $item->name,
                    'current_stock' => $currentStock
                ];
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Summary
        $this->info("Summary:");
        $this->line("Total items checked: {$totalItems}");
        $this->line("Items with discrepancies: " . count($discrepancies));
        $this->line("Items with stock but no warehouse records: " . count($itemsWithoutWarehouseStock));

        // Show discrepancies
        if (!empty($discrepancies)) {
            $this->error("\n⚠️  Items with discrepancies:");
            $this->table(
                ['Code', 'Name', 'Current Stock', 'Warehouse Stock', 'Difference'],
                array_map(function ($item) {
                    return [
                        $item['code'],
                        $item['name'],
                        $item['current_stock'],
                        $item['warehouse_stock'],
                        $item['difference']
                    ];
                }, array_slice($discrepancies, 0, 20)) // Show first 20
            );

            if (count($discrepancies) > 20) {
                $this->warn("... and " . (count($discrepancies) - 20) . " more items with discrepancies.");
            }
        }

        // Show items without warehouse stock
        if (!empty($itemsWithoutWarehouseStock)) {
            $this->warn("\n⚠️  Items with stock but no warehouse records:");
            $this->table(
                ['Code', 'Name', 'Current Stock'],
                array_map(function ($item) {
                    return [
                        $item['code'],
                        $item['name'],
                        $item['current_stock']
                    ];
                }, array_slice($itemsWithoutWarehouseStock, 0, 20)) // Show first 20
            );

            if (count($itemsWithoutWarehouseStock) > 20) {
                $this->warn("... and " . (count($itemsWithoutWarehouseStock) - 20) . " more items.");
            }
        }

        if (empty($discrepancies) && empty($itemsWithoutWarehouseStock)) {
            $this->info("\n✅ All items verified: No discrepancies found!");
        }
    }
}
