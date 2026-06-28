<?php

namespace App\Services\Accounting;

use App\Models\Accounting\PurchaseInvoice;
use App\Models\Accounting\PurchaseInvoiceLine;
use App\Models\Accounting\SalesInvoice;
use App\Models\Accounting\SalesInvoiceLine;
use Illuminate\Support\Collection;

final class HeaderDiscountAllocation
{
    public static function payableScale(float $sumLinePayables, float $headerDiscountRupiah): float
    {
        $sumLinePayables = round(max(0.0, $sumLinePayables), 2);
        $headerDiscountRupiah = round(max(0.0, $headerDiscountRupiah), 2);
        if ($sumLinePayables <= 0) {
            return 1.0;
        }
        if ($headerDiscountRupiah >= $sumLinePayables) {
            return 0.0;
        }

        return ($sumLinePayables - $headerDiscountRupiah) / $sumLinePayables;
    }

    /**
     * @param  array<int, float>  $linePayables  Pre-header payable per line (same order as rows)
     * @return array<int, float> Rupiah share of header discount per line; sums to $headerTotal (within rounding)
     */
    public static function splitHeaderAcrossPayables(array $linePayables, float $headerTotal): array
    {
        $headerTotal = round(max(0.0, $headerTotal), 2);
        $count = count($linePayables);
        if ($count === 0) {
            return [];
        }
        $T = round(array_sum($linePayables), 2);
        if ($T <= 0 || $headerTotal <= 0) {
            return array_fill(0, $count, 0.0);
        }

        $shares = [];
        $running = 0.0;
        foreach ($linePayables as $i => $Ti) {
            $Ti = round((float) $Ti, 2);
            if ($i === $count - 1) {
                $shares[$i] = round($headerTotal - $running, 2);
            } else {
                $share = $headerTotal * ($Ti / $T);
                $shares[$i] = round($share, 2);
                $running += $shares[$i];
            }
        }

        return $shares;
    }

    public static function poLineNetBeforeHeader(mixed $line): float
    {
        if ((float) $line->net_amount > 0) {
            return round((float) $line->net_amount, 2);
        }

        return round(
            (float) $line->qty * (float) $line->unit_price - (float) ($line->discount_amount ?? 0),
            2
        );
    }

    public static function piLineNetBeforeHeader(PurchaseInvoiceLine $line): float
    {
        if ((float) $line->net_amount > 0) {
            return round((float) $line->net_amount, 2);
        }

        return round((float) $line->amount - (float) ($line->discount_amount ?? 0), 2);
    }

    /**
     * Header discount is spread in payable space (net + VAT − WTax on net). Scale factor
     * {@see payableScale} matches “% diskon header pada subtotal termasuk PPN” vs diskon baris serupa.
     *
     * @return list<array{dpp: float, header_share: float, payable: float}>
     */
    public static function purchaseOrderLineScaled(Collection $linesOrdered, float $headerDiscountRupiah): array
    {
        $payables = [];
        foreach ($linesOrdered as $i => $line) {
            $net = self::poLineNetBeforeHeader($line);
            $vr = (float) ($line->vat_rate ?? 0);
            $wr = (float) ($line->wtax_rate ?? 0);
            $payables[$i] = round($net + ($net * $vr / 100) - ($net * $wr / 100), 2);
        }
        $T = round(array_sum($payables), 2);
        $H = round(min($headerDiscountRupiah, $T), 2);
        $s = self::payableScale($T, $H);
        $headerShares = self::splitHeaderAcrossPayables($payables, $H);

        $result = [];
        foreach ($linesOrdered as $i => $line) {
            $net = self::poLineNetBeforeHeader($line);
            $vr = (float) ($line->vat_rate ?? 0);
            $wr = (float) ($line->wtax_rate ?? 0);
            $dpp = round($net * $s, 2);
            $vat = round($dpp * ($vr / 100), 2);
            $wtax = round($dpp * ($wr / 100), 2);
            $result[] = [
                'dpp' => $dpp,
                'header_share' => $headerShares[$i],
                'payable' => round($dpp + $vat - $wtax, 2),
            ];
        }

        return $result;
    }

    /**
     * @return list<array{line_id: int, dpp: float, header_share: float}>
     */
    public static function purchaseInvoiceLineScaled(PurchaseInvoice $invoice): array
    {
        $lines = $invoice->lines->sortBy('id')->values();
        if ($lines->isEmpty()) {
            return [];
        }

        $lineDiscountSum = round((float) $lines->sum('discount_amount'), 2);
        $invoiceDiscount = round((float) ($invoice->discount_amount ?? 0), 2);
        $headerOnly = round(max(0.0, $invoiceDiscount - $lineDiscountSum), 2);

        $payables = [];
        foreach ($lines as $i => $line) {
            $payables[$i] = round((float) ($line->amount_after_vat ?? 0), 2);
        }
        $T = round(array_sum($payables), 2);
        $H = round(min($headerOnly, $T), 2);
        $s = self::payableScale($T, $H);
        $headerShares = self::splitHeaderAcrossPayables($payables, $H);

        $out = [];
        foreach ($lines as $i => $line) {
            /** @var PurchaseInvoiceLine $line */
            $net = self::piLineNetBeforeHeader($line);
            $dpp = round($net * $s, 2);
            $out[] = [
                'line_id' => (int) $line->id,
                'dpp' => $dpp,
                'header_share' => $headerShares[$i],
            ];
        }

        return $out;
    }

    /**
     * @return list<array{line_id: int, dpp: float, output_vat: float, wtax: float, payable: float, header_share: float}>
     */
    public static function salesInvoiceLineScaled(SalesInvoice $invoice): array
    {
        $lines = $invoice->lines->sortBy('id')->values();
        if ($lines->isEmpty()) {
            return [];
        }

        $lineDiscountSum = round((float) $lines->sum('discount_amount'), 2);
        $invoiceDiscount = round((float) ($invoice->discount_amount ?? 0), 2);
        $headerOnly = round(max(0.0, $invoiceDiscount - $lineDiscountSum), 2);

        $payables = [];
        foreach ($lines as $i => $line) {
            /** @var SalesInvoiceLine $line */
            $parts = SalesInvoicePostingMath::splitLineFromTaxExclusivePricing($line);
            $payables[$i] = $parts['gross'];
        }

        $T = round(array_sum($payables), 2);
        $H = round(min($headerOnly, $T), 2);
        $s = self::payableScale($T, $H);
        $headerShares = self::splitHeaderAcrossPayables($payables, $H);

        $out = [];
        foreach ($lines as $i => $line) {
            /** @var SalesInvoiceLine $line */
            $parts = SalesInvoicePostingMath::splitLineFromTaxExclusivePricing($line);
            $dpp = round($parts['dpp'] * $s, 2);
            $vatRate = $parts['dpp'] > 0 ? ($parts['output_vat'] / $parts['dpp']) * 100 : 0.0;
            $wtaxRate = $parts['dpp'] > 0 ? ($parts['wtax'] / $parts['dpp']) * 100 : 0.0;
            $outputVat = $vatRate > 0 ? round($dpp * ($vatRate / 100), 2) : 0.0;
            $wtax = $wtaxRate > 0 ? round($dpp * ($wtaxRate / 100), 2) : 0.0;

            $out[] = [
                'line_id' => (int) $line->id,
                'dpp' => $dpp,
                'output_vat' => $outputVat,
                'wtax' => $wtax,
                'payable' => round($dpp + $outputVat - $wtax, 2),
                'header_share' => $headerShares[$i],
            ];
        }

        return $out;
    }
}
