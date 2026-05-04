<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\PurchaseOrderLine;
use Illuminate\Validation\ValidationException;

class GrpoLinePurchaseOrderPricingService
{
    /**
     * First PO line per (purchase order, inventory item), ordered by line id for stable matching.
     *
     * @param  list<int>  $purchaseOrderIds
     * @return array<string, PurchaseOrderLine> Keys are "{order_id}|{inventory_item_id}"
     */
    public function mapFirstLinesKeyedByOrderAndInventoryItem(array $purchaseOrderIds): array
    {
        $purchaseOrderIds = array_values(array_unique(array_filter(array_map('intval', $purchaseOrderIds))));
        if ($purchaseOrderIds === []) {
            return [];
        }

        $map = [];
        PurchaseOrderLine::query()
            ->whereIn('order_id', $purchaseOrderIds)
            ->whereNotNull('inventory_item_id')
            ->orderBy('id')
            ->get()
            ->each(function (PurchaseOrderLine $line) use (&$map): void {
                $key = $line->order_id.'|'.$line->inventory_item_id;
                if (! isset($map[$key])) {
                    $map[$key] = $line;
                }
            });

        return $map;
    }

    /**
     * When a GRPO is linked to a PO, monetary fields come from the PO line per item (qty still from the request).
     *
     * @return array{unit_price: float, account_id: int, tax_code_id: ?int}
     */
    public function economicsForIncomingGrpoLine(
        ?int $purchaseOrderId,
        int $inventoryItemId,
        ?InventoryItem $item,
        int $requestLineIndex = 0,
    ): array {
        if ($purchaseOrderId !== null && $purchaseOrderId > 0) {
            $indexed = $this->mapFirstLinesKeyedByOrderAndInventoryItem([$purchaseOrderId]);
            /** @var PurchaseOrderLine|null $poLine */
            $poLine = $indexed[$purchaseOrderId.'|'.$inventoryItemId] ?? null;
            if ($poLine === null) {
                throw ValidationException::withMessages([
                    'lines.'.$requestLineIndex.'.item_id' => [
                        'This item is not on the selected purchase order. Remove it or pick a PO that includes this item.',
                    ],
                ]);
            }

            return [
                'unit_price' => $poLine->effectivePurchasingUnitPrice(),
                'account_id' => (int) $poLine->account_id,
                'tax_code_id' => $poLine->tax_code_id,
            ];
        }

        if ($item === null) {
            throw ValidationException::withMessages([
                'lines.'.$requestLineIndex.'.item_id' => ['Invalid inventory item.'],
            ]);
        }

        return [
            'unit_price' => (float) $item->purchase_price,
            'account_id' => 0,
            'tax_code_id' => null,
        ];
    }
}
