<h3>AP Party Balances (As of {{ $as_of ?? now()->toDateString() }})</h3>
<table width="100%" cellspacing="0" cellpadding="4" border="1">
    <thead>
        <tr>
            <th align="left">Vendor</th>
            <th align="right">Invoices</th>
            <th align="right">Payments</th>
            <th align="right">Balance</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($rows as $r)
            <tr>
                <td>{{ $r['vendor_name'] ?? '#' . $r['vendor_id'] }}</td>
                <td align="right">{{ number_format($r['invoices'], 2) }}</td>
                <td align="right">{{ number_format($r['payments'], 2) }}</td>
                <td align="right">{{ number_format($r['balance'], 2) }}</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <th align="left">Totals</th>
            <th align="right">{{ number_format($totals['invoices'] ?? 0, 2) }}</th>
            <th align="right">{{ number_format($totals['payments'] ?? 0, 2) }}</th>
            <th align="right">{{ number_format($totals['balance'] ?? 0, 2) }}</th>
        </tr>
    </tfoot>
</table>
