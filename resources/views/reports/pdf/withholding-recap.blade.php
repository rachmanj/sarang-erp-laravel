<h3>Withholding Recap</h3>
<table width="100%" cellspacing="0" cellpadding="4" border="1">
    <thead>
        <tr>
            <th align="left">Vendor</th>
            <th align="right">Withholding total</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($rows as $r)
            <tr>
                <td>{{ $r['vendor_name'] ?? '#' . $r['vendor_id'] }}</td>
                <td align="right">{{ number_format($r['withholding_total'], 2) }}</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <th align="left">Totals</th>
            <th align="right">{{ number_format($totals['withholding_total'] ?? 0, 2) }}</th>
        </tr>
    </tfoot>
</table>
