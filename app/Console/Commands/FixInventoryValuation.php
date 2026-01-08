<?php

namespace App\Console\Commands;

use App\Models\InventoryItem;
use App\Models\InventoryValuation;
use App\Services\InventoryService;
use Illuminate\Console\Command;

class FixInventoryValuation extends Command
{
    protected $signature = 'inventory:fix-valuation 
                            {item_id? : Specific item ID to fix, or leave empty to fix all items}
                            {--date= : Specific valuation date to fix (Y-m-d format)}';

    protected $description = 'Fix incorrect inventory valuation records by recalculating from transactions';

    public function handle(InventoryService $inventoryService)
    {
        $itemId = $this->argument('item_id');
        $date = $this->option('date');

        if ($itemId) {
            $items = collect([InventoryItem::findOrFail($itemId)]);
        } else {
            $items = InventoryItem::where('item_type', 'item')->get();
        }

        $fixedCount = 0;
        $totalCount = 0;

        foreach ($items as $item) {
            $this->info("Processing item: {$item->code} - {$item->name}");

            // Get latest valuation
            $latestValuation = InventoryValuation::where('item_id', $item->id)
                ->orderBy('valuation_date', 'desc')
                ->first();

            if (!$latestValuation) {
                $this->warn("  No valuation found, skipping...");
                continue;
            }

            // Calculate correct stock
            $correctStock = $item->current_stock;
            $recordedStock = $latestValuation->quantity_on_hand;

            if ($correctStock != $recordedStock) {
                $this->warn("  Stock mismatch detected!");
                $this->line("    Recorded: {$recordedStock}");
                $this->line("    Correct: {$correctStock}");

                // Recalculate valuation
                $inventoryService->updateItemValuation($item);

                // Verify fix
                $latestValuation->refresh();
                if ($latestValuation->quantity_on_hand == $correctStock) {
                    $this->info("  ✓ Fixed! New quantity: {$latestValuation->quantity_on_hand}");
                    $fixedCount++;
                } else {
                    $this->error("  ✗ Failed to fix!");
                }
            } else {
                $this->line("  ✓ Stock is correct: {$correctStock}");
            }

            $totalCount++;
        }

        $this->newLine();
        $this->info("Summary: Fixed {$fixedCount} out of {$totalCount} item(s)");

        return Command::SUCCESS;
    }
}
