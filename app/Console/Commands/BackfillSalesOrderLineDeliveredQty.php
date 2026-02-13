<?php

namespace App\Console\Commands;

use App\Models\DeliveryOrderLine;
use App\Models\SalesOrderLine;
use Illuminate\Console\Command;

class BackfillSalesOrderLineDeliveredQty extends Command
{
    protected $signature = 'sales-orders:backfill-delivered-qty {--dry-run : Show what would be done without making changes}';

    protected $description = 'Backfill SalesOrderLine.delivered_qty and pending_qty from Delivery Order deliveries';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('Dry run mode - no changes will be made');
        }

        $lines = SalesOrderLine::whereHas('order')->get();
        $updated = 0;
        $unchanged = 0;

        foreach ($lines as $line) {
            $delivered = (float) DeliveryOrderLine::where('sales_order_line_id', $line->id)
                ->whereHas('deliveryOrder', fn($q) => $q->where('status', '!=', 'cancelled'))
                ->sum('delivered_qty');

            $currentDelivered = (float) $line->delivered_qty;
            if (abs($delivered - $currentDelivered) < 0.001) {
                $unchanged++;
                continue;
            }

            if (!$dryRun) {
                $line->updateDeliveredQuantity($delivered);
            }

            $this->info(sprintf(
                '%s SO line %d (order %s): delivered_qty %s -> %s',
                $dryRun ? '[DRY-RUN] Would update' : 'Updated',
                $line->id,
                $line->order_id,
                $currentDelivered,
                $delivered
            ));
            $updated++;
        }

        $this->newLine();
        $this->info("Processed: {$lines->count()}, Updated: {$updated}, Unchanged: {$unchanged}");

        return 0;
    }
}
