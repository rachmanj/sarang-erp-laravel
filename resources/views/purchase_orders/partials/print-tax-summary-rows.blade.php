@php
    $orderFooter ??= \App\Services\Accounting\PurchaseOrderFooterMath::orderFooterTotals($order);
    $decimals = $decimals ?? 2;
    $decSep = $decSep ?? '.';
    $thSep = $thSep ?? ',';
    $withRpPrefix = $withRpPrefix ?? true;
    $fmt = fn (float $value): string => ($withRpPrefix ? 'Rp ' : '').number_format($value, $decimals, $decSep, $thSep);
@endphp
<tr>
    <td class="label">Total Amount (DPP)</td>
    <td class="text-right">{{ $fmt($orderFooter['exclusive_subtotal']) }}</td>
</tr>
<tr>
    <td class="label">Total VAT</td>
    <td class="text-right">{{ $fmt($orderFooter['total_vat']) }}</td>
</tr>
<tr>
    <td class="label">Total WTax</td>
    <td class="text-right">
        @if ($orderFooter['total_wtax'] != 0)
            ({{ $fmt($orderFooter['total_wtax']) }})
        @else
            {{ $fmt($orderFooter['total_wtax']) }}
        @endif
    </td>
</tr>
<tr>
    <td class="label"><strong>Total Due</strong></td>
    <td class="text-right"><strong>{{ $fmt($orderFooter['amount_due']) }}</strong></td>
</tr>
