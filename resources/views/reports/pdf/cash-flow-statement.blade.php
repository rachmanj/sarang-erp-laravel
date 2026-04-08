<h3>Statement of Cash Flows — Indirect ({{ $data['from'] }} to {{ $data['to'] }})</h3>
<p style="font-size: 11px;">Opening position date: {{ $data['begin_balance_date'] }} · Posted only:
    {{ $data['only_posted_journals'] ? 'Yes' : 'No' }}</p>

@foreach (['operating', 'investing', 'financing'] as $block)
    <h4 style="margin-bottom: 4px;">{{ $data[$block]['label'] }}</h4>
    <table width="100%" cellspacing="0" cellpadding="4" border="1" style="margin-bottom: 10px;">
        <tbody>
            @foreach ($data[$block]['lines'] as $line)
                <tr>
                    <td>{{ $line['label'] }}</td>
                    <td align="right" width="120">{{ number_format($line['amount'], 2) }}</td>
                </tr>
            @endforeach
            <tr>
                <th align="left">Subtotal</th>
                <th align="right">{{ number_format($data[$block]['subtotal'], 2) }}</th>
            </tr>
        </tbody>
    </table>
@endforeach

<h4>Summary &amp; reconciliation</h4>
<table width="100%" cellspacing="0" cellpadding="4" border="1">
    <tr>
        <td>Net change in cash (computed)</td>
        <td align="right">{{ number_format($data['summary']['net_change_computed'], 2) }}</td>
    </tr>
    <tr>
        <td>Net change in cash &amp; bank accounts (balance sheet, configured prefixes)</td>
        <td align="right">{{ number_format($data['summary']['net_change_cash_accounts'], 2) }}</td>
    </tr>
    <tr>
        <th>Reconciliation difference</th>
        <th align="right">{{ number_format($data['summary']['reconciliation_difference'], 2) }}</th>
    </tr>
</table>
