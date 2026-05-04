<?php

namespace App\Services\Accounting;

use App\Models\Accounting\SalesInvoice;
use App\Models\Accounting\SalesInvoiceLine;
use App\Models\DeliveryOrderLine;
use App\Models\SalesOrderLine;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class SalesInvoicePostingMath
{
    /**
     * Line gross total (DPP + VAT − WTax on base) for persistence, matching store/update and
     * {@see SalesOrderLine::computeAmountFromPricing()}.
     */
    public static function computedGrossAmountForLine(SalesInvoiceLine $line): float
    {
        $qty = (float) $line->qty;
        $unitPrice = (float) $line->unit_price;
        if ($line->delivery_order_line_id) {
            $dol = DeliveryOrderLine::with('salesOrderLine')->find($line->delivery_order_line_id);
            $sol = $dol?->salesOrderLine;
            if ($sol) {
                return SalesOrderLine::computeAmountFromPricing(
                    $qty,
                    $unitPrice,
                    $sol->vat_rate,
                    $sol->wtax_rate
                );
            }
        }

        $vatRate = self::vatRatePercentForLine($line);

        return SalesOrderLine::computeAmountFromPricing(
            $qty,
            $unitPrice,
            $vatRate,
            (float) ($line->wtax_rate ?? 0)
        );
    }

    /**
     * DPP / output VAT / WTax / gross receivable from qty × unit_price (tax-exclusive base), matching
     * {@see SalesOrderLine::computeAmountFromPricing()}. Persisted {@see SalesInvoiceLine::$amount} may be stale;
     * footer and posting use this split so VAT is added on top of DPP, not backed out of line gross.
     *
     * @return array{dpp: float, output_vat: float, wtax: float, gross: float}
     */
    public static function splitLineFromTaxExclusivePricing(SalesInvoiceLine $line): array
    {
        $dpp = round((float) $line->qty * (float) $line->unit_price, 2);
        $vatRate = self::vatRatePercentForLine($line);
        $wtaxRate = (float) ($line->wtax_rate ?? 0);
        $outputVat = $vatRate > 0.0 ? round($dpp * ($vatRate / 100), 2) : 0.0;
        $wtaxAmt = $wtaxRate > 0.0 ? round($dpp * ($wtaxRate / 100), 2) : 0.0;
        $gross = round($dpp + $outputVat - $wtaxAmt, 2);

        return [
            'dpp' => $dpp,
            'output_vat' => $outputVat,
            'wtax' => $wtaxAmt,
            'gross' => $gross,
        ];
    }

    /**
     * @param  Collection<int, \App\Models\Accounting\SalesInvoiceLine>|iterable<\App\Models\Accounting\SalesInvoiceLine>  $lines
     * @return array{gross_total: float, ppn_total: float, ppn_by_revenue_account: array<int, float>}
     */
    public static function summarizeLinesForPosting(Collection|iterable $lines): array
    {
        $grossTotal = 0.0;
        /** @var array<int, float> */
        $ppnByRevenueAccount = [];

        foreach ($lines as $l) {
            $parts = self::splitLineFromTaxExclusivePricing($l);
            $grossTotal += $parts['gross'];
            if ($parts['output_vat'] > 0.0) {
                $revenueAccountId = (int) $l->account_id;
                $ppnByRevenueAccount[$revenueAccountId] = round(($ppnByRevenueAccount[$revenueAccountId] ?? 0) + $parts['output_vat'], 2);
            }
        }

        $ppnTotalRounded = round(array_sum($ppnByRevenueAccount), 2);

        return [
            'gross_total' => round($grossTotal, 2),
            'ppn_total' => $ppnTotalRounded,
            'ppn_by_revenue_account' => $ppnByRevenueAccount,
        ];
    }

    /**
     * Footer figures for invoice screens/prints (DPP from tax-exclusive unit_price; gross = DPP + PPN − WTax per line).
     *
     * @return array{exclusive_subtotal: float, gross_total: float, total_vat: float, total_wtax: float, amount_due: float}
     */
    public static function invoiceFooterTotals(SalesInvoice $invoice): array
    {
        $grossTotal = 0.0;
        $exclusiveSubtotal = 0.0;
        $totalVat = 0.0;
        $totalWtax = 0.0;

        foreach ($invoice->lines as $l) {
            $parts = self::splitLineFromTaxExclusivePricing($l);
            $exclusiveSubtotal += $parts['dpp'];
            $totalVat += $parts['output_vat'];
            $totalWtax += $parts['wtax'];
            $grossTotal += $parts['gross'];
        }

        $grossTotal = round($grossTotal, 2);

        return [
            'exclusive_subtotal' => round($exclusiveSubtotal, 2),
            'gross_total' => $grossTotal,
            'total_vat' => round($totalVat, 2),
            'total_wtax' => round($totalWtax, 2),
            'amount_due' => $grossTotal,
        ];
    }

    private static function vatRatePercentForLine(SalesInvoiceLine $line): float
    {
        $fromRelation = (float) ($line->taxCode?->rate ?? 0);
        if ($fromRelation !== 0.0 || ! $line->tax_code_id) {
            return $fromRelation;
        }

        return (float) (DB::table('tax_codes')->where('id', $line->tax_code_id)->value('rate') ?? 0);
    }
}
