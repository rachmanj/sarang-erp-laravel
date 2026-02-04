<?php

namespace App\Console\Commands;

use App\Models\InventoryItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DeleteInventoryItems extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:delete-items {--codes= : Comma-separated list of item codes to delete} {--force : Force deletion even if items have transactions}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete inventory items by code. Optionally force delete items with transactions.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $codesInput = $this->option('codes');
        $force = $this->option('force');

        if (!$codesInput) {
            $this->error('Please provide item codes using --codes option (comma-separated)');
            $this->line('Example: php artisan inventory:delete-items --codes=ITEM001,ITEM002');
            return 1;
        }

        $codes = array_map('trim', explode(',', $codesInput));
        $this->info('Items to delete: ' . implode(', ', $codes));
        $this->newLine();

        // Find items
        $items = InventoryItem::whereIn('code', $codes)->get();

        if ($items->isEmpty()) {
            $this->error('No items found with the provided codes.');
            return 1;
        }

        // Show items that will be deleted
        $this->table(
            ['Code', 'Name', 'Type', 'Transactions', 'Warehouse Stock', 'Valuations'],
            $items->map(function ($item) {
                return [
                    $item->code,
                    $item->name,
                    $item->item_type,
                    $item->transactions()->count(),
                    $item->warehouseStock()->count(),
                    $item->valuations()->count(),
                ];
            })->toArray()
        );

        // Check for items with transactions
        $itemsWithTransactions = $items->filter(function ($item) {
            return $item->transactions()->count() > 0;
        });

        if ($itemsWithTransactions->isNotEmpty() && !$force) {
            $this->error('Some items have transactions and cannot be deleted:');
            foreach ($itemsWithTransactions as $item) {
                $this->line("  - {$item->code}: {$item->transactions()->count()} transactions");
            }
            $this->newLine();
            $this->warn('Use --force flag to delete items with transactions (WARNING: This will delete all related records!)');
            return 1;
        }

        // Confirm deletion
        if (!$this->confirm('Are you sure you want to delete these items? This action cannot be undone!', false)) {
            $this->info('Deletion cancelled.');
            return 0;
        }

        if ($force && $itemsWithTransactions->isNotEmpty()) {
            if (!$this->confirm('WARNING: Items have transactions. This will delete ALL related records (transactions, valuations, warehouse stock, etc.). Continue?', false)) {
                $this->info('Deletion cancelled.');
                return 0;
            }
        }

        // Delete items
        $deleted = 0;
        $failed = [];

        foreach ($items as $item) {
            try {
                DB::transaction(function () use ($item, $force) {
                    $itemCode = $item->code;
                    $itemName = $item->name;

                    // Delete related records if force is enabled
                    if ($force) {
                        // Delete GR/GI lines (no cascade, need explicit delete)
                        DB::table('gr_gi_lines')->where('item_id', $item->id)->delete();

                        // Delete transactions (cascade should handle this, but explicit for safety)
                        $item->transactions()->delete();

                        // Delete valuations (cascade should handle this)
                        $item->valuations()->delete();

                        // Delete warehouse stock (cascade should handle this)
                        $item->warehouseStock()->delete();

                        // Delete customer price levels (cascade should handle this)
                        $item->customerPriceLevels()->delete();

                        // Delete item units (cascade should handle this)
                        $item->itemUnits()->delete();
                    }

                    // Delete the item
                    $item->delete();

                    $this->info("✓ Deleted: {$itemCode} - {$itemName}");
                });

                $deleted++;
            } catch (\Exception $e) {
                $failed[] = [
                    'code' => $item->code,
                    'error' => $e->getMessage()
                ];
                $this->error("✗ Failed to delete {$item->code}: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info("Deletion Summary:");
        $this->line("Successfully deleted: {$deleted} item(s)");

        if (!empty($failed)) {
            $this->error("Failed to delete: " . count($failed) . " item(s)");
            foreach ($failed as $fail) {
                $this->line("  - {$fail['code']}: {$fail['error']}");
            }
        }

        return 0;
    }
}
