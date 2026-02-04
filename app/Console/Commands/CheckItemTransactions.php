<?php

namespace App\Console\Commands;

use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use Illuminate\Console\Command;

class CheckItemTransactions extends Command
{
    protected $signature = 'inventory:check-transactions {item_code}';
    protected $description = 'Show all transactions for an item with detailed information';

    public function handle()
    {
        $itemCode = $this->argument('item_code');
        $item = InventoryItem::where('code', $itemCode)->first();

        if (!$item) {
            $this->error("Item with code '{$itemCode}' not found.");
            return 1;
        }

        $this->info("Transactions for: {$item->code} - {$item->name}");
        $this->line("Item ID: {$item->id}");
        $this->newLine();

        $transactions = InventoryTransaction::where('item_id', $item->id)
            ->orderBy('transaction_date')
            ->orderBy('created_at')
            ->get();

        if ($transactions->isEmpty()) {
            $this->warn("No transactions found for this item.");
            return 0;
        }

        $this->table(
            ['ID', 'Date', 'Type', 'Quantity', 'Warehouse ID', 'Warehouse Code', 'Unit Cost', 'Total Cost', 'Reference Type', 'Reference ID', 'Notes'],
            $transactions->map(function ($t) {
                $warehouse = $t->warehouse;
                return [
                    $t->id,
                    $t->transaction_date,
                    $t->transaction_type,
                    $t->quantity,
                    $t->warehouse_id ?? 'NULL',
                    $warehouse ? $warehouse->code : 'N/A',
                    $t->unit_cost,
                    $t->total_cost,
                    $t->reference_type ?? '-',
                    $t->reference_id ?? '-',
                    substr($t->notes ?? '-', 0, 30)
                ];
            })->toArray()
        );

        $this->newLine();
        $this->info("Summary:");
        $this->line("Total transactions: " . $transactions->count());
        $this->line("Transactions with warehouse: " . $transactions->whereNotNull('warehouse_id')->count());
        $this->line("Transactions without warehouse: " . $transactions->whereNull('warehouse_id')->count());

        $byWarehouse = $transactions->whereNotNull('warehouse_id')->groupBy('warehouse_id');
        foreach ($byWarehouse as $warehouseId => $group) {
            $warehouse = \App\Models\Warehouse::find($warehouseId);
            $total = $group->sum('quantity');
            $this->line("  Warehouse {$warehouse->code} ({$warehouseId}): {$total} units (" . $group->count() . " transactions)");
        }

        $withoutWarehouse = $transactions->whereNull('warehouse_id');
        if ($withoutWarehouse->isNotEmpty()) {
            $total = $withoutWarehouse->sum('quantity');
            $this->line("  No Warehouse: {$total} units (" . $withoutWarehouse->count() . " transactions)");
        }

        return 0;
    }
}
