<?php

namespace App\Services;

use App\Models\GoodsReceiptPO;
use App\Models\PurchaseOrderLine;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseInvoiceGrpoAggregationService
{
    public function __construct(
        private GrpoLinePurchaseOrderPricingService $grpoLinePurchaseOrderPricing,
    ) {}

    /**
     * @return list<int>
     */
    public function normalizeGrpoIdsFromRequest(?int $goodsReceiptIdField, mixed $goodsReceiptIdsField): array
    {
        $ids = collect($goodsReceiptIdsField ?? [])->filter()->map(fn ($id) => (int) $id)->filter(fn ($id) => $id > 0);

        if ($goodsReceiptIdField) {
            $ids->prepend((int) $goodsReceiptIdField);
        }

        return $ids->unique()->values()->all();
    }

    /**
     * GRPO IDs already referenced by any purchase invoice (legacy column or pivot).
     *
     * @param  list<int>  $grpoIds
     * @return list<int>
     */
    public function grpoIdsAlreadyLinkedToPurchaseInvoice(array $grpoIds): array
    {
        if ($grpoIds === []) {
            return [];
        }

        $fromPivot = DB::table('goods_receipt_po_purchase_invoice')->whereIn('grpo_id', $grpoIds)->pluck('grpo_id');
        $fromColumn = DB::table('purchase_invoices')->whereIn('goods_receipt_id', $grpoIds)->pluck('goods_receipt_id');

        return $fromPivot->merge($fromColumn)->unique()->filter()->values()->all();
    }

    /**
     * Open GRPOs for a supplier (not yet linked to any purchase invoice).
     *
     * @param  ?int  $companyEntityId  When set, limits to GRPOs for that entity only (optional narrowing).
     * @return Collection<int, GoodsReceiptPO>
     */
    public function getAvailableGrposForSupplier(int $businessPartnerId, ?int $companyEntityId = null): Collection
    {
        $blocked = DB::table('purchase_invoices')
            ->whereNotNull('goods_receipt_id')
            ->pluck('goods_receipt_id');

        $blockedPivot = DB::table('goods_receipt_po_purchase_invoice')->pluck('grpo_id');

        $exclude = $blocked->merge($blockedPivot)->unique()->filter()->values()->all();

        return GoodsReceiptPO::query()
            ->with('companyEntity:id,code,name')
            ->where('business_partner_id', $businessPartnerId)
            ->when($companyEntityId !== null, fn ($q) => $q->where('company_entity_id', $companyEntityId))
            ->when($exclude !== [], fn ($q) => $q->whereNotIn('id', $exclude))
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get(['id', 'grn_no', 'date', 'total_amount', 'status', 'company_entity_id']);
    }

    /**
     * @param  list<int>  $grpoIds
     * @param  ?int  $invoiceCompanyEntityId  When null (e.g. prefill preview), GRPO entities are only checked for consistency among themselves.
     */
    public function assertGrposCanBeMergedIntoInvoice(array $grpoIds, int $businessPartnerId, ?int $invoiceCompanyEntityId = null): Collection
    {
        if ($grpoIds === []) {
            throw ValidationException::withMessages([
                'goods_receipt_ids' => ['Select at least one Goods Receipt PO.'],
            ]);
        }

        $uniqueIds = array_values(array_unique($grpoIds));
        if (count($uniqueIds) !== count($grpoIds)) {
            throw ValidationException::withMessages([
                'goods_receipt_ids' => ['Duplicate GRPO selection is not allowed.'],
            ]);
        }

        $grpos = GoodsReceiptPO::with('lines.item')->whereIn('id', $grpoIds)->orderBy('id')->get()->keyBy('id');

        if ($grpos->count() !== count($grpoIds)) {
            throw ValidationException::withMessages([
                'goods_receipt_ids' => ['One or more Goods Receipt POs were not found.'],
            ]);
        }

        $busy = collect($this->grpoIdsAlreadyLinkedToPurchaseInvoice($grpoIds));
        $colliding = $busy->intersect($grpoIds);
        if ($colliding->isNotEmpty()) {
            throw ValidationException::withMessages([
                'goods_receipt_ids' => ['One or more selected GRPOs are already linked to a purchase invoice: #'.$colliding->implode(', #').'.'],
            ]);
        }

        $mergedEntityId = null;
        foreach ($grpos as $grpo) {
            if ((int) $grpo->business_partner_id !== $businessPartnerId) {
                throw ValidationException::withMessages([
                    'business_partner_id' => ['All selected GRPOs must belong to this vendor ('.$grpo->grn_no.').'],
                ]);
            }
            $eid = (int) $grpo->company_entity_id;
            if ($mergedEntityId === null) {
                $mergedEntityId = $eid;
            } elseif ($mergedEntityId !== $eid) {
                throw ValidationException::withMessages([
                    'goods_receipt_ids' => ['All selected GRPOs must use the same company entity.'],
                ]);
            }
        }

        if ($invoiceCompanyEntityId !== null && $mergedEntityId !== null && $mergedEntityId !== $invoiceCompanyEntityId) {
            throw ValidationException::withMessages([
                'company_entity_id' => ['Company on the invoice must match the selected GRPOs. Use “Pull lines from selected GRPOs” to align the form, or pick GRPOs for the current company.'],
            ]);
        }

        return $grpos;
    }

    /**
     * First PO line per (order_id, inventory_item_id); used when linking GRPO → PI pricing to the PO.
     *
     * @param  Collection<int, GoodsReceiptPO>  $grpos
     * @return array<string, PurchaseOrderLine>
     */
    public function lookupPurchaseOrderLinesForGrpos(Collection $grpos): array
    {
        $orderIds = $grpos->pluck('purchase_order_id')->filter()->unique()->values()->all();
        if ($orderIds === []) {
            return [];
        }

        return $this->grpoLinePurchaseOrderPricing->mapFirstLinesKeyedByOrderAndInventoryItem(
            array_map('intval', $orderIds)
        );
    }

    /**
     * @param  Collection<int, GoodsReceiptPO>  $grpos
     * @return array{date?: string, business_partner_id: int|null, company_entity_id: int|null, description: string, lines: list<array<string, mixed>>}
     */
    public function buildPrefillFromGrpos(Collection $grpos): array
    {
        $first = $grpos->first();
        $numbers = $grpos->pluck('grn_no')->filter()->values()->implode(', ');
        if ($numbers === '') {
            $numbers = $grpos->keys()->implode(', ');
        }

        $poLineLookup = $this->lookupPurchaseOrderLinesForGrpos($grpos);

        $lines = [];
        foreach ($grpos->sortBy('id')->values() as $grpo) {
            foreach ($grpo->lines as $l) {
                $poLineKey = ($grpo->purchase_order_id && $l->item_id)
                    ? $grpo->purchase_order_id.'|'.$l->item_id
                    : null;

                /** @var PurchaseOrderLine|null $poLine */
                $poLine = $poLineKey !== null ? ($poLineLookup[$poLineKey] ?? null) : null;

                $unitPrice = $poLine !== null
                    ? $poLine->effectivePurchasingUnitPrice()
                    : (float) $l->unit_price;

                $lines[] = [
                    'account_id' => $poLine !== null ? (int) $poLine->account_id : (int) $l->account_id,
                    'inventory_item_id' => $l->item_id,
                    'warehouse_id' => $grpo->warehouse_id,
                    'description' => $grpo->grn_no ? ($grpo->grn_no.': '.($l->description ?? '')) : ($l->description ?? ''),
                    'qty' => (float) $l->qty,
                    'unit_price' => $unitPrice,
                    'tax_code_id' => $poLine !== null ? ($poLine->tax_code_id ?? $l->tax_code_id) : $l->tax_code_id,
                ];
            }
        }

        return [
            'date' => now()->toDateString(),
            'business_partner_id' => $first ? (int) $first->business_partner_id : null,
            'company_entity_id' => $first ? (int) $first->company_entity_id : null,
            'description' => 'From GRPO '.$numbers,
            'lines' => $lines,
        ];
    }

    /**
     * @param  List<int>  $grpoIds
     */
    public function persistInvoiceGrpoPivot(\App\Models\Accounting\PurchaseInvoice $invoice, array $grpoIds): void
    {
        if ($grpoIds === []) {
            $invoice->grpos()->detach();
            $invoice->forceFill(['goods_receipt_id' => null])->save();

            return;
        }

        sort($grpoIds);
        $invoice->grpos()->sync(collect($grpoIds)->mapWithKeys(fn (int $id) => [$id => []])->all());

        $invoice->forceFill([
            'goods_receipt_id' => count($grpoIds) === 1 ? $grpoIds[0] : null,
        ])->save();
    }
}
