<h3>Cash Ledger {{ $account ? '(' . $account->code . ' - ' . $account->name . ')' : '' }}</h3>
<table width="100%" cellspacing="0" cellpadding="4" border="1">
    <thead>
        <tr>
            <th align="left">Date</th>
            <th align="left">Description</th>
            <th align="right">Debit</th>
            <th align="right">Credit</th>
            <th align="right">Balance</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($data['rows'] ?? [] as $r)
            <tr>
                <td>{{ $r['date'] }}</td>
                <td>{{ $r['description'] ?? '' }}</td>
                <td align="right">{{ number_format($r['debit'], 2) }}</td>
                <td align="right">{{ number_format($r['credit'], 2) }}</td>
                <td align="right">{{ number_format($r['balance'], 2) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
