<?php

namespace App\Console\Commands;

use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\InventoryWarehouseStock;
use App\Services\InventoryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixPlasticWrapeFifoCorrectionCommand extends Command
{
    protected $signature = 'inventory:fix-plastic-wrape-fifo
                            {--item=CON000022 : Inventory item code or ID}
                            {--out-id=2998 : Outbound warehouse transfer transaction ID}
                            {--in-id=2999 : Inbound warehouse transfer transaction ID}
                            {--correct-qty=1 : Corrected transfer quantity (absolute value)}
                            {--dry-run : Preview changes without saving}
                            {--reconcile-warehouse : Recalculate warehouse stock from transactions after applying}';

    protected $description = 'Correct Plastic Wrape FIFO warehouse transfer mismatch (tx #2998/#2999) and verify FIFO replay';

    public function handle(InventoryService $inventoryService): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $reconcileWarehouse = (bool) $this->option('reconcile-warehouse');
        $correctQty = (float) $this->option('correct-qty');
        $outboundId = (int) $this->option('out-id');
        $inboundId = (int) $this->option('in-id');

        if ($correctQty <= 0) {
            $this->error('Correct quantity must be greater than zero.');

            return self::FAILURE;
        }

        $item = $this->resolveItem((string) $this->option('item'));

        if (! $item) {
            $this->error('Inventory item not found: '.$this->option('item'));

            return self::FAILURE;
        }

        if ($item->valuation_method !== 'fifo') {
            $this->error("Item {$item->code} does not use FIFO valuation.");

            return self::FAILURE;
        }

        $outbound = InventoryTransaction::query()->find($outboundId);
        $inbound = InventoryTransaction::query()->find($inboundId);

        if (! $outbound || ! $inbound) {
            $this->error('One or both warehouse transfer transactions were not found.');

            return self::FAILURE;
        }

        if (! $this->validateTransferPair($item, $outbound, $inbound)) {
            return self::FAILURE;
        }

        $this->info("Item: {$item->code} — {$item->name} (ID {$item->id})");
        $this->line('');

        $fifoErrorBefore = $this->getFifoReplayError($inventoryService, $item);

        if ($fifoErrorBefore === null) {
            $this->info('FIFO replay already succeeds for this item. No correction required.');

            return self::SUCCESS;
        }

        $this->warn('FIFO replay currently fails: '.$fifoErrorBefore);
        $this->line('');

        if ($this->isAlreadyCorrected($outbound, $inbound, $correctQty)) {
            $this->error('Transfer transactions appear corrected by quantity, but FIFO replay still fails.');
            $this->line('Review later transactions or run with different transaction IDs.');

            return self::FAILURE;
        }

        try {
            $unitCost = $inventoryService->calculateFifoConsumptionUnitCostBeforeTransaction(
                $item,
                $correctQty,
                $outbound->id
            );
        } catch (\Exception $e) {
            $this->error('Unable to derive FIFO unit cost for corrected transfer: '.$e->getMessage());

            return self::FAILURE;
        }

        $outboundUpdate = [
            'quantity' => -$correctQty,
            'unit_cost' => $unitCost,
            'total_cost' => -($correctQty * $unitCost),
            'notes' => trim(($outbound->notes ?? '').' [Corrected '.now()->toDateString().': FIFO layer mismatch, was '.$outbound->quantity.']'),
        ];

        $inboundUpdate = [
            'quantity' => $correctQty,
            'unit_cost' => $unitCost,
            'total_cost' => $correctQty * $unitCost,
            'notes' => trim(($inbound->notes ?? '').' [Corrected '.now()->toDateString().': matched WH outbound, was '.$inbound->quantity.']'),
        ];

        $this->table(
            ['Transaction', 'Field', 'Current', 'New'],
            [
                ["#{$outbound->id} OUT", 'quantity', $outbound->quantity, $outboundUpdate['quantity']],
                ["#{$outbound->id} OUT", 'unit_cost', $outbound->unit_cost, $outboundUpdate['unit_cost']],
                ["#{$outbound->id} OUT", 'total_cost', $outbound->total_cost, $outboundUpdate['total_cost']],
                ["#{$inbound->id} IN", 'quantity', $inbound->quantity, $inboundUpdate['quantity']],
                ["#{$inbound->id} IN", 'unit_cost', $inbound->unit_cost, $inboundUpdate['unit_cost']],
                ["#{$inbound->id} IN", 'total_cost', $inbound->total_cost, $inboundUpdate['total_cost']],
            ]
        );

        $this->line('');
        $this->info('Net stock impact: '.($inbound->quantity + $outbound->quantity).' → '.($correctQty + (-$correctQty)).' (should be 0).');

        if ($dryRun) {
            $this->line('');
            $this->comment('[DRY RUN] No changes saved. Re-run without --dry-run to apply.');

            return self::SUCCESS;
        }

        if (! $this->confirm('Apply these transaction corrections?', true)) {
            $this->comment('Cancelled.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($outbound, $inbound, $outboundUpdate, $inboundUpdate, $item, $reconcileWarehouse): void {
            $outbound->update($outboundUpdate);
            $inbound->update($inboundUpdate);

            if ($reconcileWarehouse) {
                $this->reconcileWarehouseStock($item);
            }
        });

        $item->refresh();
        $fifoErrorAfter = $this->getFifoReplayError($inventoryService, $item);

        if ($fifoErrorAfter !== null) {
            $this->error('Correction applied, but FIFO replay still fails: '.$fifoErrorAfter);

            return self::FAILURE;
        }

        $this->info('Correction applied successfully. FIFO replay now passes.');
        $this->line('Available FIFO quantity: '.(int) $inventoryService->getAvailableFifoQuantity($item));
        $this->line('Current stock: '.(int) $item->current_stock);

        if ($reconcileWarehouse) {
            $this->info('Warehouse stock reconciled from transactions.');
        } else {
            $this->comment('Tip: re-run with --reconcile-warehouse to refresh warehouse stock totals.');
        }

        return self::SUCCESS;
    }

    private function resolveItem(string $identifier): ?InventoryItem
    {
        if (is_numeric($identifier)) {
            return InventoryItem::query()->find((int) $identifier);
        }

        return InventoryItem::query()->where('code', $identifier)->first();
    }

    private function validateTransferPair(InventoryItem $item, InventoryTransaction $outbound, InventoryTransaction $inbound): bool
    {
        $valid = true;

        foreach ([['OUT', $outbound], ['IN', $inbound]] as [$label, $transaction]) {
            if ((int) $transaction->item_id !== (int) $item->id) {
                $this->error("Transaction #{$transaction->id} ({$label}) belongs to item {$transaction->item_id}, not {$item->id}.");
                $valid = false;
            }

            if ($transaction->transaction_type !== 'transfer') {
                $this->error("Transaction #{$transaction->id} ({$label}) is not a transfer transaction.");
                $valid = false;
            }

            if ($transaction->reference_type !== 'warehouse_transfer') {
                $this->error("Transaction #{$transaction->id} ({$label}) is not a warehouse_transfer (found {$transaction->reference_type}).");
                $valid = false;
            }
        }

        if ((float) $outbound->quantity >= 0) {
            $this->error("Transaction #{$outbound->id} must be an outbound transfer (negative quantity).");
            $valid = false;
        }

        if ((float) $inbound->quantity <= 0) {
            $this->error("Transaction #{$inbound->id} must be an inbound transfer (positive quantity).");
            $valid = false;
        }

        if (abs((float) $outbound->quantity) !== abs((float) $inbound->quantity)) {
            $this->warn('Outbound and inbound transfer quantities differ; both will be set to the corrected absolute quantity.');
        }

        return $valid;
    }

    private function isAlreadyCorrected(InventoryTransaction $outbound, InventoryTransaction $inbound, float $correctQty): bool
    {
        return abs((float) $outbound->quantity) === $correctQty
            && abs((float) $inbound->quantity) === $correctQty
            && (float) $outbound->unit_cost > 0
            && (float) $inbound->unit_cost > 0;
    }

    private function getFifoReplayError(InventoryService $inventoryService, InventoryItem $item): ?string
    {
        try {
            $inventoryService->calculateUnitCost($item->fresh());

            return null;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    private function reconcileWarehouseStock(InventoryItem $item): void
    {
        $transactions = InventoryTransaction::query()
            ->where('item_id', $item->id)
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();

        $fallbackWarehouseId = $item->default_warehouse_id
            ?? InventoryTransaction::query()->where('item_id', $item->id)->whereNotNull('warehouse_id')->value('warehouse_id');

        $warehouseTotals = $transactions
            ->groupBy(fn (InventoryTransaction $transaction) => $transaction->warehouse_id ?? $fallbackWarehouseId)
            ->map(fn ($group) => $group->sum('quantity'));

        foreach ($warehouseTotals as $warehouseId => $quantityOnHand) {
            if (! $warehouseId) {
                continue;
            }

            $stock = InventoryWarehouseStock::query()->firstOrCreate(
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

            $stock->quantity_on_hand = (int) $quantityOnHand;
            $stock->available_quantity = (int) $quantityOnHand - (int) $stock->reserved_quantity;
            $stock->save();
        }

        $inventoryService = app(InventoryService::class);
        $inventoryService->updateItemValuation($item->fresh());
    }
}
