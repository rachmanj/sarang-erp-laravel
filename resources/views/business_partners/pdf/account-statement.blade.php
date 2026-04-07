@php
    $opening = (float) ($statement['opening_balance'] ?? 0);
    $closing = (float) ($statement['closing_balance'] ?? 0);
    $tDeb = (float) ($statement['total_debits'] ?? 0);
    $tCred = (float) ($statement['total_credits'] ?? 0);
    $rows = $statement['transactions'] ?? [];
@endphp
<h2 style="font-size: 14pt; margin-bottom: 8px;">Account statement</h2>
<p style="font-size: 10pt; margin: 4px 0;">
    <strong>{{ $partner->code }}</strong> &mdash; {{ $partner->name }}
    @if ($account)
        <br/>GL: {{ $account->code }} &mdash; {{ $account->name }}
    @endif
</p>
<p style="font-size: 9pt; margin: 4px 0; color: #333;">
    Period:
    @if ($periodStart)
        {{ \Illuminate\Support\Carbon::parse($periodStart)->format('d/m/Y') }}
    @else
        &mdash;
    @endif
    &mdash;
    @if ($periodEnd)
        {{ \Illuminate\Support\Carbon::parse($periodEnd)->format('d/m/Y') }}
    @else
        &mdash;
    @endif
</p>
<table width="100%" cellspacing="0" cellpadding="3" border="0" style="font-size: 9pt; margin-bottom: 12px;">
    <tr>
        <td width="25%"><strong>Opening balance</strong></td>
        <td align="right">{{ number_format($opening, 2, '.', ',') }}</td>
        <td width="25%"><strong>Closing balance</strong></td>
        <td align="right">{{ number_format($closing, 2, '.', ',') }}</td>
    </tr>
    <tr>
        <td><strong>Total debits</strong></td>
        <td align="right">{{ number_format($tDeb, 2, '.', ',') }}</td>
        <td><strong>Total credits</strong></td>
        <td align="right">{{ number_format($tCred, 2, '.', ',') }}</td>
    </tr>
</table>
<table width="100%" cellspacing="0" cellpadding="4" border="1" style="font-size: 8pt; border-collapse: collapse;">
    <thead>
        <tr style="background-color: #f0f0f0;">
            <th align="left">Posting</th>
            <th align="left">Doc date</th>
            <th align="left">Type</th>
            <th align="left">Doc no.</th>
            <th align="left">Jrnl</th>
            <th align="left">Description</th>
            <th align="right">Debit</th>
            <th align="right">Credit</th>
            <th align="right">Balance</th>
            <th align="left">Posted by</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($rows as $r)
            @php
                $row = is_object($r) ? json_decode(json_encode($r), true) : $r;
            @endphp
            <tr>
                <td>{{ ! empty($row['posting_date']) ? \Illuminate\Support\Carbon::parse($row['posting_date'])->format('d/m/Y') : '' }}</td>
                <td>{{ ! empty($row['document_date']) ? \Illuminate\Support\Carbon::parse($row['document_date'])->format('d/m/Y') : '' }}</td>
                <td>{{ $row['document_type'] ?? '' }}</td>
                <td>{{ $row['document_no'] ?? '' }}</td>
                <td>{{ $row['journal_no'] ?? '' }}</td>
                <td>{{ \Illuminate\Support\Str::limit($row['description'] ?? '', 40) }}</td>
                <td align="right">{{ number_format((float) ($row['debit'] ?? 0), 2, '.', ',') }}</td>
                <td align="right">{{ number_format((float) ($row['credit'] ?? 0), 2, '.', ',') }}</td>
                <td align="right">{{ number_format((float) ($row['cumulative_balance'] ?? 0), 2, '.', ',') }}</td>
                <td>{{ $row['created_by'] ?? '' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
