<div style="font-family: DejaVu Sans, Helvetica, Arial, sans-serif; color: #1a1a1a;">
    <div style="border-bottom: 2px solid #1e3a5f; padding-bottom: 8px; margin-bottom: 14px;">
        <div style="font-size: 14px; font-weight: bold; letter-spacing: 0.5px;">{{ $entity_name ?? config('app.name') }}</div>
        <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #555; margin-top: 2px;">Statement
            of Profit or Loss</div>
        <div style="font-size: 10px; color: #666; margin-top: 6px;">{{ $from }} to {{ $to }} · Posted:
            {{ $only_posted_journals ? 'Yes' : 'No' }} · Zero lines hidden: {{ $hide_zero_lines ? 'Yes' : 'No' }}</div>
    </div>

    @foreach ($sections as $section)
        <h4 style="margin: 14px 0 6px 0; font-size: 11px; color: #333;">{{ $section['label'] }}</h4>
        <table width="100%" cellspacing="0" cellpadding="5" border="1" style="border-collapse: collapse; margin-bottom: 12px; font-size: 9px;">
            <thead>
                <tr style="background: #eef2f7;">
                    <th align="left" width="14%">Code</th>
                    <th align="left">Description</th>
                    <th align="right" width="18%">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($section['rows'] as $r)
                    @php
                        $depth = (int) ($r['depth'] ?? 0);
                        $pad = $depth * 10;
                        $isParent = ! empty($r['is_parent']);
                    @endphp
                    <tr>
                        <td>{{ $r['code'] }}</td>
                        <td style="padding-left: {{ 4 + $pad }}px; font-weight: {{ $isParent ? '600' : '400' }};">
                            @if ($isParent)
                                <span style="color: #888;">↳</span>
                            @endif
                            {{ $r['name'] }}
                        </td>
                        <td align="right">{{ number_format($r['amount'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background: #e8eef5; font-weight: bold;">
                    <th align="left" colspan="2">Section total</th>
                    <th align="right">{{ number_format($section['total'], 2) }}</th>
                </tr>
            </tfoot>
        </table>
    @endforeach

    <table width="100%" cellspacing="0" cellpadding="5" border="1" style="border-collapse: collapse; font-size: 9px;">
        <tr>
            <th align="left" width="70%">Gross profit</th>
            <td align="right">{{ number_format($subtotals['gross_profit'], 2) }}</td>
        </tr>
        <tr>
            <th align="left">Operating income</th>
            <td align="right">{{ number_format($subtotals['operating_income'], 2) }}</td>
        </tr>
        <tr style="background: #e8f0fe; font-weight: bold;">
            <th align="left">Net income</th>
            <td align="right">{{ number_format($subtotals['net_income'], 2) }}</td>
        </tr>
    </table>
</div>
