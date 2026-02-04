<?php

namespace App\Console\Commands;

use App\Models\InventoryItem;
use App\Models\InventoryWarehouseStock;
use App\Models\InventoryTransaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReconcileWarehouseStock extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:reconcile-warehouse-stock {item_code? : Specific item code to reconcile} {--warehouse_id= : Default warehouse ID for transactions without warehouse}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reconcile warehouse stock from inventory transactions. Allocates stock to warehouses for transactions missing warehouse_id.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $itemCode = $this->argument('item_code');
        $defaultWarehouseId = $this->option('warehouse_id');

        if ($itemCode) {
            $this->reconcileItem($itemCode, $defaultWarehouseId);
        } else {
            if (!$this->confirm('This will reconcile ALL items. Do you want to continue?')) {
                return 0;
            }
            $this->reconcileAllItems($defaultWarehouseId);
        }

        return 0;
    }

    /**
     * Reconcile a specific item
     */
    private function reconcileItem($itemCode, $defaultWarehouseId = null)
    {
        $item = InventoryItem::where('code', $itemCode)->first();

        if (!$item) {
            $this->error("Item with code '{$itemCode}' not found.");
            return;
        }

        if ($item->item_type === 'service') {
            $this->warn("Skipping service item (services don't have stock).");
            return;
        }

        $this->info("Reconciling item: {$item->code} - {$item->name}");

        return DB::transaction(function () use ($item, $defaultWarehouseId) {
            // Get all transactions
            $transactions = InventoryTransaction::where('item_id', $item->id)
                ->orderBy('transaction_date')
                ->orderBy('created_at')
                ->get();

            // Group transactions by warehouse (including those with warehouse_id)
            $warehouseTotals = [];
            $transactionsWithoutWarehouse = [];

            foreach ($transactions as $transaction) {
                if ($transaction->warehouse_id) {
                    if (!isset($warehouseTotals[$transaction->warehouse_id])) {
                        $warehouseTotals[$transaction->warehouse_id] = 0;
                    }
                    $warehouseTotals[$transaction->warehouse_id] += $transaction->quantity;
                } else {
                    $transactionsWithoutWarehouse[] = $transaction;
                }
            }

            $this->info("Found transactions in " . count($warehouseTotals) . " warehouse(s)");

            // Handle transactions without warehouse_id
            if (!empty($transactionsWithoutWarehouse)) {
                $this->warn("Found " . count($transactionsWithoutWarehouse) . " transactions without warehouse_id.");

                if ($defaultWarehouseId) {
                    $this->info("Using default warehouse ID: {$defaultWarehouseId}");
                    $totalUnallocated = array_sum(array_column($transactionsWithoutWarehouse, 'quantity'));

                    // Update transactions to have warehouse_id
                    foreach ($transactionsWithoutWarehouse as $transaction) {
                        $transaction->warehouse_id = $defaultWarehouseId;
                        $transaction->save();
                    }

                    if (!isset($warehouseTotals[$defaultWarehouseId])) {
                        $warehouseTotals[$defaultWarehouseId] = 0;
                    }
                    $warehouseTotals[$defaultWarehouseId] += $totalUnallocated;
                    $this->info("Allocated {$totalUnallocated} units to warehouse {$defaultWarehouseId}");
                } else {
                    // Use item's default warehouse
                    if ($item->default_warehouse_id) {
                        $this->info("Using item's default warehouse ID: {$item->default_warehouse_id}");
                        $totalUnallocated = array_sum(array_column($transactionsWithoutWarehouse, 'quantity'));

                        foreach ($transactionsWithoutWarehouse as $transaction) {
                            $transaction->warehouse_id = $item->default_warehouse_id;
                            $transaction->save();
                        }

                        if (!isset($warehouseTotals[$item->default_warehouse_id])) {
                            $warehouseTotals[$item->default_warehouse_id] = 0;
                        }
                        $warehouseTotals[$item->default_warehouse_id] += $totalUnallocated;
                        $this->info("Allocated {$totalUnallocated} units to default warehouse {$item->default_warehouse_id}");
                    } else {
                        $this->error("Cannot reconcile: Item has no default_warehouse_id and no --warehouse_id provided.");
                        $this->error("Please specify a warehouse using --warehouse_id option.");
                        return;
                    }
                }
            }

            // Get all existing warehouse stock records for this item
            $existingWarehouseStocks = InventoryWarehouseStock::where('item_id', $item->id)->get();
            $processedWarehouseIds = [];

            // Update warehouse stock records
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

                $oldQuantity = $warehouseStock->quantity_on_hand;
                $warehouseStock->quantity_on_hand = $totalQuantity;
                $warehouseStock->updateAvailableQuantity();
                $warehouseStock->save();

                $warehouse = \App\Models\Warehouse::find($warehouseId);
                $this->info("Updated warehouse '{$warehouse->code}' stock: {$oldQuantity} -> {$totalQuantity}");

                $processedWarehouseIds[] = $warehouseId;
            }

            // Remove or zero out warehouse stock records that have no transactions
            foreach ($existingWarehouseStocks as $existingStock) {
                if (!in_array($existingStock->warehouse_id, $processedWarehouseIds)) {
                    $warehouse = \App\Models\Warehouse::find($existingStock->warehouse_id);
                    if ($existingStock->quantity_on_hand != 0) {
                        $this->warn("Warehouse '{$warehouse->code}' has stock ({$existingStock->quantity_on_hand}) but no transactions. Zeroing out.");
                        $existingStock->quantity_on_hand = 0;
                        $existingStock->updateAvailableQuantity();
                        $existingStock->save();
                    }
                }
            }

            // Verify reconciliation
            $currentStock = $item->current_stock;
            $warehouseStockTotal = InventoryWarehouseStock::where('item_id', $item->id)
                ->sum('quantity_on_hand');

            $this->newLine();
            $this->info("Reconciliation Summary:");
            $this->line("Current Stock (from transactions): {$currentStock}");
            $this->line("Warehouse Stock Total: {$warehouseStockTotal}");
            $this->line("Difference: " . ($currentStock - $warehouseStockTotal));

            if ($currentStock == $warehouseStockTotal) {
                $this->info("✅ Reconciliation successful!");
            } else {
                $this->warn("⚠️  Still have discrepancy. Check for transactions with warehouse_id issues.");
            }
        });
    }

    /**
     * Reconcile all items
     */
    private function reconcileAllItems($defaultWarehouseId = null)
    {
        $items = InventoryItem::where('item_type', '!=', 'service')->get();
        $totalItems = $items->count();
        $reconciled = 0;
        $failed = [];

        $bar = $this->output->createProgressBar($totalItems);
        $bar->start();

        foreach ($items as $item) {
            try {
                DB::transaction(function () use ($item, $defaultWarehouseId) {
                    // Get ALL transactions (including those without warehouse_id)
                    $transactions = InventoryTransaction::where('item_id', $item->id)
                        ->orderBy('transaction_date')
                        ->orderBy('created_at')
                        ->get();

                    // Group transactions by warehouse
                    $warehouseTotals = [];
                    $transactionsWithoutWarehouse = [];

                    foreach ($transactions as $transaction) {
                        if ($transaction->warehouse_id) {
                            if (!isset($warehouseTotals[$transaction->warehouse_id])) {
                                $warehouseTotals[$transaction->warehouse_id] = 0;
                            }
                            $warehouseTotals[$transaction->warehouse_id] += $transaction->quantity;
                        } else {
                            $transactionsWithoutWarehouse[] = $transaction;
                        }
                    }

                    // Handle transactions without warehouse_id
                    if (!empty($transactionsWithoutWarehouse)) {
                        $targetWarehouseId = null;

                        if ($defaultWarehouseId) {
                            $targetWarehouseId = $defaultWarehouseId;
                        } elseif ($item->default_warehouse_id) {
                            $targetWarehouseId = $item->default_warehouse_id;
                        } else {
                            // Skip items without default warehouse and no --warehouse_id provided
                            return;
                        }

                        $totalUnallocated = array_sum(array_column($transactionsWithoutWarehouse, 'quantity'));

                        // Update transactions to have warehouse_id
                        foreach ($transactionsWithoutWarehouse as $transaction) {
                            $transaction->warehouse_id = $targetWarehouseId;
                            $transaction->save();
                        }

                        if (!isset($warehouseTotals[$targetWarehouseId])) {
                            $warehouseTotals[$targetWarehouseId] = 0;
                        }
                        $warehouseTotals[$targetWarehouseId] += $totalUnallocated;
                    }

                    // Get all existing warehouse stock records for this item
                    $existingWarehouseStocks = InventoryWarehouseStock::where('item_id', $item->id)->get();
                    $processedWarehouseIds = [];

                    // Update warehouse stock for each warehouse
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

                        $processedWarehouseIds[] = $warehouseId;
                    }

                    // Remove or zero out warehouse stock records that have no transactions
                    foreach ($existingWarehouseStocks as $existingStock) {
                        if (!in_array($existingStock->warehouse_id, $processedWarehouseIds)) {
                            if ($existingStock->quantity_on_hand != 0) {
                                $existingStock->quantity_on_hand = 0;
                                $existingStock->updateAvailableQuantity();
                                $existingStock->save();
                            }
                        }
                    }
                });
                $reconciled++;
            } catch (\Exception $e) {
                $failed[] = ['code' => $item->code, 'error' => $e->getMessage()];
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Reconciliation complete!");
        $this->line("Items reconciled: {$reconciled}/{$totalItems}");

        if (!empty($failed)) {
            $this->error("Failed items:");
            foreach ($failed as $fail) {
                $this->line("  - {$fail['code']}: {$fail['error']}");
            }
        }
    }
}
