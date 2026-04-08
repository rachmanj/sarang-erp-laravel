<h3>Trial Balance (As of {{ $as_of }})</h3>
<table width="100%" cellspacing="0" cellpadding="4" border="1">
    <thead>
        <tr>
            <th align="left">Code</th>
            <th align="left">Name</th>
            <th align="left">Type</th>
            <th align="left">Currencies</th>
            <th align="right">Debit</th>
            <th align="right">Credit</th>
            <th align="right">Balance</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($rows as $r)
            <tr>
                <td>{{ $r['code'] }}</td>
                <td>{{ $r['name'] }}</td>
                <td>{{ $r['type'] }}</td>
                <td>{{ $r['currencies'] ?? 'IDR' }}</td>
                <td align="right">{{ number_format($r['debit'], 2) }}</td>
                <td align="right">{{ number_format($r['credit'], 2) }}</td>
                <td align="right">{{ number_format($r['balance'], 2) }}</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <th align="left" colspan="4">Totals</th>
            <th align="right">{{ number_format($totals['debit'], 2) }}</th>
            <th align="right">{{ number_format($totals['credit'], 2) }}</th>
            <th></th>
        </tr>
    </tfoot>
</table>
