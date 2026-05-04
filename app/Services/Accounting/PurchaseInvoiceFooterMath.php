<?php

namespace App\Services\Accounting;

use App\Models\Accounting\PurchaseInvoice;
use App\Models\Accounting\PurchaseInvoiceLine;
use Illuminate\Support\Facades\DB;

final class PurchaseInvoiceFooterMath
{
    /**
     * Footer aligned with AP posting: DPP = net after line discount; PPN and WTax are rates on that DPP.
     * Grand total payable uses persisted {@see PurchaseInvoice::$total_amount} so payment allocations stay consistent.
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
        $exclusiveSubtotal = 0.0;
        $totalVat = 0.0;
        $totalWtax = 0.0;
        $sumLineAmountAfterVat = 0.0;

        foreach ($invoice->lines as $line) {
            $dpp = self::lineDpp($line);
            $exclusiveSubtotal += $dpp;
            $sumLineAmountAfterVat += self::lineAmountAfterTax($line);

            if (! $line->tax_code_id) {
                continue;
            }

            $tax = $line->relationLoaded('taxCode') && $line->taxCode
                ? $line->taxCode
                : DB::table('tax_codes')->where('id', $line->tax_code_id)->first();

            if (! $tax) {
                continue;
            }

            $rate = (float) $tax->rate;
            $type = strtolower((string) ($tax->type ?? ''));
            $name = strtolower((string) ($tax->name ?? ''));

            if (str_contains($name, 'ppn') || $type === 'ppn_input') {
                $totalVat += round($dpp * ($rate / 100), 2);
            } elseif ($type === 'withholding') {
                $totalWtax += round($dpp * ($rate / 100), 2);
            }
        }

        $lineDiscountSum = round((float) $invoice->lines->sum('discount_amount'), 2);
        $invoiceDiscount = round((float) ($invoice->discount_amount ?? 0), 2);
        $headerDiscount = round(max(0.0, $invoiceDiscount - $lineDiscountSum), 2);

        $linesTotalAfterVat = round($sumLineAmountAfterVat, 2);
        $computedPayable = round($linesTotalAfterVat - $headerDiscount, 2);
        $storedPayable = round((float) $invoice->total_amount, 2);

        $amountDue = abs($computedPayable - $storedPayable) <= 0.05 ? $computedPayable : $storedPayable;

        return [
            'exclusive_subtotal' => round($exclusiveSubtotal, 2),
            'total_vat' => round($totalVat, 2),
            'total_wtax' => round($totalWtax, 2),
            'sum_line_amount_after_vat' => $linesTotalAfterVat,
            'line_discount_sum' => $lineDiscountSum,
            'header_discount' => $headerDiscount,
            'amount_due' => $amountDue,
        ];
    }

    private static function lineDpp(PurchaseInvoiceLine $line): float
    {
        if ((float) $line->net_amount > 0) {
            return round((float) $line->net_amount, 2);
        }

        return round((float) $line->amount - (float) ($line->discount_amount ?? 0), 2);
    }

    private static function lineAmountAfterTax(PurchaseInvoiceLine $line): float
    {
        if ((float) ($line->amount_after_vat ?? 0) > 0) {
            return round((float) $line->amount_after_vat, 2);
        }

        return round((float) $line->amount, 2);
    }
}
