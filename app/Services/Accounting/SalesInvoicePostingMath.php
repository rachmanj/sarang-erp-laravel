<?php

namespace App\Services\Accounting;

use App\Models\Accounting\SalesInvoice;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class SalesInvoicePostingMath
{
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
            $amount = (float) $l->amount;
            $grossTotal += $amount;
            if (empty($l->tax_code_id)) {
                continue;
            }
            $ratePercent = (float) (DB::table('tax_codes')->where('id', $l->tax_code_id)->value('rate') ?? 0);
            $wtaxPercent = (float) ($l->wtax_rate ?? 0);
            $linePpn = self::computeOutputVatFromTaxInclusiveLineAmount($amount, $ratePercent, $wtaxPercent);
            $revenueAccountId = (int) $l->account_id;
            $ppnByRevenueAccount[$revenueAccountId] = round(($ppnByRevenueAccount[$revenueAccountId] ?? 0) + $linePpn, 2);
        }

        $ppnTotalRounded = round(array_sum($ppnByRevenueAccount), 2);

        return [
            'gross_total' => $grossTotal,
            'ppn_total' => $ppnTotalRounded,
            'ppn_by_revenue_account' => $ppnByRevenueAccount,
        ];
    }

    /**
     * Split a tax-inclusive line gross into DPP, output VAT, and withholding tax on DPP.
     *
     * @return array{dpp: float, output_vat: float, wtax: float}
     */
    public static function splitTaxInclusiveLineAmount(float $grossAmount, float $vatRatePercent, float $wtaxRatePercent): array
    {
        if ($grossAmount <= 0) {
            return ['dpp' => 0.0, 'output_vat' => 0.0, 'wtax' => 0.0];
        }

        $vat = $vatRatePercent / 100;
        $wtax = $wtaxRatePercent / 100;
        $denominator = 1 + $vat - $wtax;
        if ($denominator <= 0) {
            return ['dpp' => round($grossAmount, 2), 'output_vat' => 0.0, 'wtax' => 0.0];
        }

        $dpp = round($grossAmount / $denominator, 2);
        $outputVat = $vatRatePercent > 0 ? round($dpp * $vat, 2) : 0.0;
        $wtaxAmt = $wtaxRatePercent > 0 ? round($dpp * $wtax, 2) : 0.0;

        return ['dpp' => $dpp, 'output_vat' => $outputVat, 'wtax' => $wtaxAmt];
    }

    /**
     * Line amount is tax-inclusive gross: base + (base × VAT%) − (base × WTax%), aligned with {@see \App\Models\SalesOrderLine::computeAmountFromPricing}.
     */
    public static function computeOutputVatFromTaxInclusiveLineAmount(float $grossAmount, float $vatRatePercent, float $wtaxRatePercent): float
    {
        if ($grossAmount <= 0 || $vatRatePercent <= 0) {
            return 0.0;
        }

        return self::splitTaxInclusiveLineAmount($grossAmount, $vatRatePercent, $wtaxRatePercent)['output_vat'];
    }

    /**
     * Footer figures for invoice screens/prints (line amounts are tax-inclusive gross).
     *
     * @return array{exclusive_subtotal: float, gross_total: float, total_vat: float, total_wtax: float, amount_due: float}
     */
    public static function invoiceFooterTotals(SalesInvoice $invoice): array
    {
        $grossTotal = (float) $invoice->lines->sum(fn ($l) => (float) $l->amount);
        $exclusiveSubtotal = 0.0;
        $totalVat = 0.0;
        $totalWtax = 0.0;

        foreach ($invoice->lines as $l) {
            $exclusiveSubtotal += round((float) $l->qty * (float) $l->unit_price, 2);
            $vatRate = (float) ($l->taxCode?->rate ?? 0);
            if ($vatRate === 0.0 && $l->tax_code_id) {
                $vatRate = (float) (DB::table('tax_codes')->where('id', $l->tax_code_id)->value('rate') ?? 0);
            }
            $wtaxRate = (float) ($l->wtax_rate ?? 0);
            $parts = self::splitTaxInclusiveLineAmount((float) $l->amount, $vatRate, $wtaxRate);
            $totalVat += $parts['output_vat'];
            $totalWtax += $parts['wtax'];
        }

        return [
            'exclusive_subtotal' => round($exclusiveSubtotal, 2),
            'gross_total' => $grossTotal,
            'total_vat' => $totalVat,
            'total_wtax' => $totalWtax,
            'amount_due' => $grossTotal,
        ];
    }
}
