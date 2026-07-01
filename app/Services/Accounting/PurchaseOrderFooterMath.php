<?php

namespace App\Services\Accounting;

use App\Models\PurchaseOrder;

final class PurchaseOrderFooterMath
{
    /**
     * Footer aligned with PO create/edit totals: DPP after header scaling; VAT and WTax on that DPP.
     *
     * @return array{
     *     exclusive_subtotal: float,
     *     total_vat: float,
     *     total_wtax: float,
     *     header_discount: float,
     *     amount_due: float
     * }
     */
    public static function orderFooterTotals(PurchaseOrder $order): array
    {
        $order->loadMissing(['lines' => fn ($q) => $q->orderBy('id')]);
        $lines = $order->lines->sortBy('id')->values();
        $headerDiscount = round((float) ($order->discount_amount ?? 0), 2);
        $scaledRows = HeaderDiscountAllocation::purchaseOrderLineScaled($lines, $headerDiscount);

        $exclusiveSubtotal = 0.0;
        $totalVat = 0.0;
        $totalWtax = 0.0;
        $amountDue = 0.0;

        foreach ($lines as $i => $line) {
            $dpp = (float) ($scaledRows[$i]['dpp'] ?? HeaderDiscountAllocation::poLineNetBeforeHeader($line));
            $vr = (float) ($line->vat_rate ?? 0);
            $wr = (float) ($line->wtax_rate ?? 0);
            $exclusiveSubtotal += $dpp;
            $totalVat += round($dpp * ($vr / 100), 2);
            $totalWtax += round($dpp * ($wr / 100), 2);
            $amountDue += (float) ($scaledRows[$i]['payable'] ?? 0);
        }

        $computedPayable = round($amountDue, 2);
        $storedPayable = round((float) $order->total_amount, 2);
        $amountDueFinal = abs($computedPayable - $storedPayable) <= 0.05 ? $computedPayable : $storedPayable;

        return [
            'exclusive_subtotal' => round($exclusiveSubtotal, 2),
            'total_vat' => round($totalVat, 2),
            'total_wtax' => round($totalWtax, 2),
            'header_discount' => $headerDiscount,
            'amount_due' => $amountDueFinal,
        ];
    }

    public static function lineDpp(mixed $line): float
    {
        return HeaderDiscountAllocation::poLineNetBeforeHeader($line);
    }
}
