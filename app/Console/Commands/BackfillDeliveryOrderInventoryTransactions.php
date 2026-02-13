<?php

namespace App\Console\Commands;

use App\Models\DeliveryOrderLine;
use App\Models\InventoryTransaction;
use App\Services\InventoryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillDeliveryOrderInventoryTransactions extends Command
{
    protected $signature = 'delivery-orders:backfill-inventory-transactions {--dry-run : Show what would be done without making changes}';

    protected $description = 'Backfill inventory sale transactions for Delivery Order lines with picked/delivered qty';

    public function handle(InventoryService $inventoryService): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('Dry run mode - no changes will be made');
        }

        $lines = DeliveryOrderLine::with(['inventoryItem', 'deliveryOrder'])
            ->whereNotNull('inventory_item_id')
            ->where(function ($q) {
                $q->where('picked_qty', '>', 0)
                    ->orWhere('delivered_qty', '>', 0);
            })
            ->get();

        $processed = 0;
        $created = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($lines as $line) {
            $processed++;

            $shouldReduce = (int) round(max($line->picked_qty, $line->delivered_qty));
            if ($shouldReduce <= 0) {
                $skipped++;
                continue;
            }

            $alreadyReduced = (int) abs(
                InventoryTransaction::where('reference_type', 'delivery_order_line')
                    ->where('reference_id', $line->id)
                    ->where('transaction_type', 'sale')
                    ->sum('quantity')
            );

            $delta = $shouldReduce - $alreadyReduced;
            if ($delta <= 0) {
                $skipped++;
                continue;
            }

            if (!$line->inventoryItem) {
                $this->warn("Line {$line->id}: Inventory item not found, skipping");
                $skipped++;
                continue;
            }

            $do = $line->deliveryOrder;
            $transactionDate = $do->actual_delivery_date
                ?? $do->planned_delivery_date
                ?? $do->created_at;

            try {
                if (!$dryRun) {
                    DB::transaction(function () use ($inventoryService, $line, $delta, $transactionDate, $do) {
                        $unitCost = $inventoryService->calculateUnitCost($line->inventoryItem);
                        $inventoryService->processSaleTransaction(
                            $line->inventory_item_id,
                            $delta,
                            (float) $unitCost,
                            'delivery_order_line',
                            $line->id,
                            "Backfill: Picked/Delivered from DO {$do->do_number} - {$line->item_name}",
                            $do->warehouse_id
                        );
                    });
                }

                $this->info(sprintf(
                    '%s Line %d (DO %s): %d units for item %s',
                    $dryRun ? '[DRY-RUN] Would create' : 'Created',
                    $line->id,
                    $do->do_number ?? $do->id,
                    $delta,
                    $line->item_code ?? $line->item_name
                ));
                $created++;
            } catch (\Exception $e) {
                $this->error("Line {$line->id}: {$e->getMessage()}");
                $errors++;
            }
        }

        $this->newLine();
        $this->info("Processed: {$processed}, Created: {$created}, Skipped: {$skipped}, Errors: {$errors}");

        return $errors > 0 ? 1 : 0;
    }
}
