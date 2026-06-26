<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\InventoryWarehouseStock;
use App\Models\Warehouse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InventoryFifoRepairService
{
    public function __construct(private InventoryService $inventoryService) {}

    /**
     * @return array<string, mixed>
     */
    public function diagnose(InventoryItem $item): array
    {
        $stock = (float) $item->current_stock;
        $transactionNet = (float) $item->transactions()->sum('quantity');

        if ($item->valuation_method !== 'fifo' || $item->item_type === 'service') {
            return [
                'status' => 'not_applicable',
                'item_id' => $item->id,
                'current_stock' => $stock,
                'transaction_net' => $transactionNet,
            ];
        }

        $error = $this->inventoryService->getFifoReplayError($item);
        $tolerantQty = $this->inventoryService->getTolerantFifoLayerQuantity($item);
        $deficits = $error !== null ? $this->inventoryService->findFifoLayerDeficits($item) : [];
        $totalShortfall = (float) array_sum(array_column($deficits, 'shortfall'));

        if ($error === null && abs($tolerantQty - $stock) < 0.01) {
            return [
                'status' => 'ok',
                'item_id' => $item->id,
                'current_stock' => $stock,
                'transaction_net' => $transactionNet,
                'tolerant_fifo_qty' => $tolerantQty,
                'deficits' => [],
                'total_shortfall' => 0.0,
                'stock_after_repair' => $stock,
            ];
        }

        return [
            'status' => $error !== null ? 'strict_replay_failed' : 'layer_stock_mismatch',
            'item_id' => $item->id,
            'current_stock' => $stock,
            'transaction_net' => $transactionNet,
            'tolerant_fifo_qty' => $tolerantQty,
            'error' => $error,
            'deficits' => $deficits,
            'total_shortfall' => $totalShortfall,
            'stock_after_repair' => $stock + $totalShortfall,
        ];
    }

    /**
     * @return array{messages: list<string>, adjustments_created: int}
     */
    public function repair(InventoryItem $item, int $userId): array
    {
        $diagnosis = $this->diagnose($item);

        if (in_array($diagnosis['status'], ['ok', 'not_applicable'], true)) {
            return [
                'messages' => ['FIFO layers are already consistent for this item.'],
                'adjustments_created' => 0,
            ];
        }

        /** @var list<array<string, mixed>> $deficits */
        $deficits = $diagnosis['deficits'];

        if ($deficits === []) {
            throw new \RuntimeException('Unable to determine FIFO repair adjustments for this item.');
        }

        $messages = [];

        DB::transaction(function () use ($item, $deficits, $userId, &$messages): void {
            foreach ($deficits as $deficit) {
                $qty = (float) $deficit['shortfall'];
                $unitCost = (float) $deficit['unit_cost'];
                $repairDate = Carbon::parse((string) $deficit['transaction_date'])->subDay()->toDateString();

                InventoryTransaction::create([
                    'item_id' => $item->id,
                    'warehouse_id' => $deficit['warehouse_id'],
                    'transaction_type' => 'adjustment',
                    'quantity' => $qty,
                    'unit_cost' => $unitCost,
                    'total_cost' => round($qty * $unitCost, 2),
                    'reference_type' => 'fifo_layer_repair',
                    'reference_id' => (int) $deficit['before_transaction_id'],
                    'transaction_date' => $repairDate,
                    'notes' => 'FIFO layer repair before transaction #'.$deficit['before_transaction_id'],
                    'created_by' => $userId,
                ]);

                $messages[] = sprintf(
                    'Added FIFO repair adjustment +%s @ %s before transaction #%s.',
                    rtrim(rtrim(number_format($qty, 2, '.', ''), '0'), '.'),
                    number_format($unitCost, 2, '.', ''),
                    $deficit['before_transaction_id']
                );
            }

            $this->reconcileWarehouseStock($item);
            $this->inventoryService->updateItemValuation($item->fresh());
        });

        if ($this->inventoryService->getFifoReplayError($item->fresh()) !== null) {
            throw new \RuntimeException('FIFO repair was applied but replay still fails. Please contact support.');
        }

        return [
            'messages' => $messages,
            'adjustments_created' => count($deficits),
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function findItemsNeedingRepair(?string $search = null, int $limit = 200): Collection
    {
        $query = InventoryItem::query()
            ->where('is_active', true)
            ->where('valuation_method', 'fifo')
            ->where('item_type', 'item')
            ->orderBy('code');

        if ($search !== null && $search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('code', 'like', '%'.$search.'%')
                    ->orWhere('name', 'like', '%'.$search.'%');

                if (is_numeric($search)) {
                    $builder->orWhere('id', (int) $search);
                }
            });
        }

        $issues = collect();

        foreach ($query->limit($limit)->get() as $item) {
            $diagnosis = $this->diagnose($item);
            if (! in_array($diagnosis['status'], ['ok', 'not_applicable'], true)) {
                $issues->push(array_merge($diagnosis, [
                    'code' => $item->code,
                    'name' => $item->name,
                ]));
            }
        }

        return $issues;
    }

    private function reconcileWarehouseStock(InventoryItem $item): void
    {
        $transactions = InventoryTransaction::query()->where('item_id', $item->id)->get();
        $fallbackWarehouseId = $item->default_warehouse_id ?? Warehouse::query()->min('id');

        $warehouseTotals = $transactions
            ->groupBy(fn (InventoryTransaction $transaction) => $transaction->warehouse_id ?? $fallbackWarehouseId)
            ->map(fn (Collection $group) => $group->sum('quantity'));

        $warehouseIds = $warehouseTotals->keys()->filter()->all();

        foreach ($warehouseTotals as $warehouseId => $quantity) {
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

            $stock->quantity_on_hand = $quantity;
            $stock->available_quantity = $quantity - $stock->reserved_quantity;
            $stock->save();
        }

        InventoryWarehouseStock::query()
            ->where('item_id', $item->id)
            ->when($warehouseIds !== [], fn ($query) => $query->whereNotIn('warehouse_id', $warehouseIds))
            ->update([
                'quantity_on_hand' => 0,
                'available_quantity' => 0,
            ]);
    }
}
