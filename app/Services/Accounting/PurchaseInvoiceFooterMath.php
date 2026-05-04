<?php

namespace App\Services\Accounting;

use App\Models\Accounting\PurchaseInvoice;
use App\Models\Accounting\PurchaseInvoiceLine;
use Illuminate\Support\Facades\DB;

final class PurchaseInvoiceFooterMath
{
    /**
     * Footer aligned with AP posting: DPP after header scaling; PPN and WTax use that DPP.
     * Header discount on the payable total is modeled as a uniform scale on line payables.
     *
     * @return array{
     *     exclusive_subtotal: float,
     *     total_vat: float,
     *     total_wtax: float,
     *     sum_line_amount_after_vat: float,
     *     line_discount_sum: float,
     *     header_discount: float,
     *     amount_due: float
     * }
     */
    public static function invoiceFooterTotals(PurchaseInvoice $invoice): array
    {
        $invoice->loadMissing(['lines' => fn ($q) => $q->orderBy('id')]);

        $scaledRows = HeaderDiscountAllocation::purchaseInvoiceLineScaled($invoice);
        $dppByLineId = collect($scaledRows)->keyBy('line_id');

        $exclusiveSubtotal = 0.0;
        $totalVat = 0.0;
        $totalWtax = 0.0;
        $sumLineAmountAfterVat = 0.0;

        foreach ($invoice->lines->sortBy('id') as $line) {
            /** @var PurchaseInvoiceLine $line */
            $dpp = (float) ($dppByLineId[$line->id]['dpp'] ?? HeaderDiscountAllocation::piLineNetBeforeHeader($line));
            $exclusiveSubtotal += $dpp;

            if (! $line->tax_code_id) {
                $sumLineAmountAfterVat += round((float) ($line->amount_after_vat ?? 0), 2);

                continue;
            }

            $tax = $line->relationLoaded('taxCode') && $line->taxCode
                ? $line->taxCode
                : DB::table('tax_codes')->where('id', $line->tax_code_id)->first();

            if (! $tax) {
                $sumLineAmountAfterVat += round((float) ($line->amount_after_vat ?? 0), 2);

                continue;
            }

            $rate = (float) $tax->rate;
            $type = strtolower((string) ($tax->type ?? ''));
            $name = strtolower((string) ($tax->name ?? ''));

            if (str_contains($name, 'ppn') || $type === 'ppn_input') {
                $vatPart = round($dpp * ($rate / 100), 2);
                $totalVat += $vatPart;
                $sumLineAmountAfterVat += round($dpp + $vatPart, 2);
            } elseif ($type === 'withholding') {
                $totalWtax += round($dpp * ($rate / 100), 2);
                $sumLineAmountAfterVat += round($dpp, 2);
            } else {
                $sumLineAmountAfterVat += round((float) ($line->amount_after_vat ?? 0), 2);
            }
        }

        $lineDiscountSum = round((float) $invoice->lines->sum('discount_amount'), 2);
        $invoiceDiscount = round((float) ($invoice->discount_amount ?? 0), 2);
        $headerDiscount = round(max(0.0, $invoiceDiscount - $lineDiscountSum), 2);

        $computedPayable = round($sumLineAmountAfterVat, 2);
        $storedPayable = round((float) $invoice->total_amount, 2);

        $amountDue = abs($computedPayable - $storedPayable) <= 0.05 ? $computedPayable : $storedPayable;

        return [
            'exclusive_subtotal' => round($exclusiveSubtotal, 2),
            'total_vat' => round($totalVat, 2),
            'total_wtax' => round($totalWtax, 2),
            'sum_line_amount_after_vat' => round($sumLineAmountAfterVat, 2),
            'line_discount_sum' => $lineDiscountSum,
            'header_discount' => $headerDiscount,
            'amount_due' => $amountDue,
        ];
    }
}
