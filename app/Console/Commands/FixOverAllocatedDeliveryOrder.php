<?php

namespace App\Console\Commands;

use App\Models\DeliveryOrder;
use App\Services\DeliveryService;
use Illuminate\Console\Command;

class FixOverAllocatedDeliveryOrder extends Command
{
    protected $signature = 'delivery-orders:fix-over-allocated {do_id : Delivery Order ID to cancel} {--dry-run : Show what would be done without making changes}';

    protected $description = 'Cancel an over-allocated DO and backfill SO line delivered_qty';

    public function handle(DeliveryService $deliveryService): int
    {
        $doId = (int) $this->argument('do_id');
        $dryRun = $this->option('dry-run');

        $do = DeliveryOrder::find($doId);
        if (!$do) {
            $this->error("Delivery Order {$doId} not found.");
            return 1;
        }

        if (!$do->canBeCancelled()) {
            $this->error("Delivery Order {$do->do_number} cannot be cancelled (status: {$do->status}).");
            return 1;
        }

        if ($dryRun) {
            $this->warn('Dry run - would cancel DO ' . $do->do_number . ' and backfill SO lines.');
            return 0;
        }

        $this->info("Cancelling DO {$do->do_number}...");
        $deliveryService->cancelDeliveryOrder($doId, 'Cancelled by fix-over-allocated command');
        $this->info('DO cancelled. Running backfill for SO lines...');

        $this->call('sales-orders:backfill-delivered-qty');

        $this->info('Done.');
        return 0;
    }
}
