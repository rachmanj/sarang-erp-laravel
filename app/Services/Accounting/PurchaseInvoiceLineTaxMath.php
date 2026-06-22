<?php

namespace App\Services\Accounting;

use App\Models\Accounting\PurchaseInvoiceLine;

final class PurchaseInvoiceLineTaxMath
{
    public static function ppnAmount(float $dpp, ?object $taxCode): float
    {
        if (! $taxCode) {
            return 0.0;
        }

        $rate = (float) $taxCode->rate;
        if (str_contains(strtolower((string) $taxCode->name), 'ppn') || strtolower((string) $taxCode->type) === 'ppn_input') {
            return round($dpp * ($rate / 100), 2);
        }

        return 0.0;
    }

    public static function withholdingAmount(float $dpp, ?object $taxCode, float $wtaxRate): float
    {
        if ($taxCode && strtolower((string) $taxCode->type) === 'withholding') {
            return round($dpp * ((float) $taxCode->rate / 100), 2);
        }

        if ($wtaxRate > 0) {
            return round($dpp * ($wtaxRate / 100), 2);
        }

        return 0.0;
    }

    public static function withholdingTaxType(?object $taxCode, float $wtaxRate): string
    {
        if ($taxCode && strtolower((string) $taxCode->type) === 'withholding') {
            return self::mapWithholdingCode((string) $taxCode->code);
        }

        return $wtaxRate > 0 ? 'pph_23' : 'pph_23';
    }

    public static function withholdingRate(?object $taxCode, float $wtaxRate): float
    {
        if ($taxCode && strtolower((string) $taxCode->type) === 'withholding') {
            return (float) $taxCode->rate;
        }

        return $wtaxRate;
    }

    public static function lineWithholdingFromPurchaseLine(PurchaseInvoiceLine $line, float $dpp): float
    {
        $tax = $line->tax_code_id
            ? \Illuminate\Support\Facades\DB::table('tax_codes')->where('id', $line->tax_code_id)->first()
            : null;

        return self::withholdingAmount($dpp, $tax, (float) ($line->wtax_rate ?? 0));
    }

    private static function mapWithholdingCode(string $code): string
    {
        $normalized = strtolower($code);

        return match (true) {
            str_contains($normalized, 'pph21') || str_contains($normalized, '21') => 'pph_21',
            str_contains($normalized, 'pph22') || str_contains($normalized, '22') => 'pph_22',
            default => 'pph_23',
        };
    }
}
