<h3>AP Aging (As of {{ $as_of }})</h3>
<table width="100%" cellspacing="0" cellpadding="4" border="1">
    <thead>
        <tr>
            <th align="left">Vendor</th>
            <th align="right">Current</th>
            <th align="right">31-60</th>
            <th align="right">61-90</th>
            <th align="right">91+</th>
            <th align="right">Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($rows as $r)
            <tr>
                <td>{{ $r['vendor_name'] ?? '#' . $r['vendor_id'] }}</td>
                <td align="right">{{ number_format($r['current'], 2) }}</td>
                <td align="right">{{ number_format($r['d31_60'], 2) }}</td>
                <td align="right">{{ number_format($r['d61_90'], 2) }}</td>
                <td align="right">{{ number_format($r['d91_plus'], 2) }}</td>
                <td align="right">{{ number_format($r['total'], 2) }}</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <th align="left">Totals</th>
            <th align="right">{{ number_format($totals['current'] ?? 0, 2) }}</th>
            <th align="right">{{ number_format($totals['d31_60'] ?? 0, 2) }}</th>
            <th align="right">{{ number_format($totals['d61_90'] ?? 0, 2) }}</th>
            <th align="right">{{ number_format($totals['d91_plus'] ?? 0, 2) }}</th>
            <th align="right">{{ number_format($totals['total'] ?? 0, 2) }}</th>
        </tr>
    </tfoot>
</table>
