<?php

namespace App\Services\Accounting;

use App\Models\Accounting\PurchaseInvoice;
use Illuminate\Support\Facades\DB;

final class PurchaseDocumentHeaderDiscountApplier
{
    public function recalculatePurchaseInvoiceLines(PurchaseInvoice $invoice): void
    {
        $invoice->load(['lines' => fn ($q) => $q->orderBy('id')]);
        $scaled = HeaderDiscountAllocation::purchaseInvoiceLineScaled($invoice);
        $byId = collect($scaled)->keyBy('line_id');

        foreach ($invoice->lines as $line) {
            $row = $byId->get($line->id);
            if (! $row) {
                continue;
            }
            $dpp = (float) $row['dpp'];
            $vatAmount = $this->ppnOnDpp($dpp, $line->tax_code_id);

            $line->update([
                'header_discount_allocated' => (float) $row['header_share'],
                'vat_amount' => $vatAmount,
                'amount_after_vat' => round($dpp + $vatAmount, 2),
            ]);
        }
    }

    public function ppnOnDpp(float $dpp, ?int $taxCodeId): float
    {
        if (! $taxCodeId || $dpp <= 0) {
            return 0.0;
        }
        $tax = DB::table('tax_codes')->where('id', $taxCodeId)->first();
        if (! $tax) {
            return 0.0;
        }
        $rate = (float) $tax->rate;
        $name = strtolower((string) ($tax->name ?? ''));
        $type = strtolower((string) ($tax->type ?? ''));
        if (str_contains($name, 'ppn') || $type === 'ppn_input') {
            return round($dpp * ($rate / 100), 2);
        }

        return 0.0;
    }
}
