@php
    $orderFooter ??= \App\Services\Accounting\PurchaseOrderFooterMath::orderFooterTotals($order);
    $labelColspan = $labelColspan ?? 6;
    $decimals = $decimals ?? 2;
    $decSep = $decSep ?? '.';
    $thSep = $thSep ?? ',';
    $withRpPrefix = $withRpPrefix ?? true;
    $fmt = fn (float $value): string => ($withRpPrefix ? 'Rp ' : '').number_format($value, $decimals, $decSep, $thSep);
@endphp
<tfoot>
    <tr>
        <th colspan="{{ $labelColspan }}" class="text-right">Total Amount (DPP)</th>
        <th class="text-right">{{ $fmt($orderFooter['exclusive_subtotal']) }}</th>
    </tr>
    <tr>
        <th colspan="{{ $labelColspan }}" class="text-right">Total VAT</th>
        <th class="text-right">{{ $fmt($orderFooter['total_vat']) }}</th>
    </tr>
    <tr>
        <th colspan="{{ $labelColspan }}" class="text-right">Total WTax</th>
        <th class="text-right">
            @if ($orderFooter['total_wtax'] != 0)
                ({{ $fmt($orderFooter['total_wtax']) }})
            @else
                {{ $fmt($orderFooter['total_wtax']) }}
            @endif
        </th>
    </tr>
    <tr>
        <th colspan="{{ $labelColspan }}" class="text-right"><strong>Total Due</strong></th>
        <th class="text-right"><strong>{{ $fmt($orderFooter['amount_due']) }}</strong></th>
    </tr>
</tfoot>
