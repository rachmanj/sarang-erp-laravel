<h3>GL Detail</h3>
<p style="font-size: 11px;">
    @if (!empty($filters['from']))
        From {{ $filters['from'] }}
    @endif
    @if (!empty($filters['to']))
        to {{ $filters['to'] }}
    @endif
    @if (!empty($filters['account_id']))
        · Account ID {{ $filters['account_id'] }}
    @endif
</p>
<table width="100%" cellspacing="0" cellpadding="3" border="1" style="font-size: 9px;">
    <thead>
        <tr>
            <th align="left">Date</th>
            <th align="left">Journal</th>
            <th align="left">Account</th>
            <th align="right">Debit</th>
            <th align="right">Credit</th>
            <th align="left">Memo</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($rows as $r)
            <tr>
                <td>{{ $r['date'] }}</td>
                <td>{{ \Illuminate\Support\Str::limit($r['journal_desc'] ?? '', 40) }}</td>
                <td>{{ $r['account_code'] }} {{ \Illuminate\Support\Str::limit($r['account_name'], 28) }}</td>
                <td align="right">{{ number_format($r['debit'], 2) }}</td>
                <td align="right">{{ number_format($r['credit'], 2) }}</td>
                <td>{{ \Illuminate\Support\Str::limit($r['memo'] ?? '', 35) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
