<!DOCTYPE html>
<html>

<head>
    <title>PO {{ $order->order_no ?? '#' . $order->id }}</title>
    <style>
        @page { size: 9.5in; margin: 0.25in; }
        * { box-sizing: border-box; }
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 11px;
            line-height: 1.2;
            margin: 0;
            padding: 8px;
            max-width: 9.5in;
            width: 100%;
        }
        .company-header { text-align: center; margin-bottom: 8px; padding-bottom: 4px; border-bottom: 1px solid #000; }
        .company-name { font-size: 12px; font-weight: bold; }
        .company-details { font-size: 10px; }
        .header { text-align: center; margin: 8px 0; }
        .header h1 { font-size: 14px; margin: 0; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; font-size: 10px; }
        th, td { border: 1px solid #000; padding: 3px 4px; text-align: left; }
        th { font-weight: bold; background: #fff; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .info-table td { border: none; padding: 1px 4px; }
        .info-table .label { font-weight: bold; white-space: nowrap; }
        .items-table th:nth-child(1) { width: 5%; }
        .items-table th:nth-child(2) { width: 15%; }
        .items-table th:nth-child(3) { width: 45%; }
        .items-table th:nth-child(4) { width: 12%; }
        .items-table th:nth-child(5) { width: 12%; }
        .items-table th:nth-child(6) { width: 11%; }
        .print-btn { position: fixed; bottom: 16px; right: 16px; padding: 8px 16px; background: #333; color: white; border: none; cursor: pointer; font-size: 12px; }
        @media print { body { padding: 0; } .no-print { display: none !important; } }
    </style>
</head>

<body>
    <button class="no-print print-btn" onclick="window.print()">Print</button>

    @php
        $companyName = \App\Models\ErpParameter::get('company_name', 'Company Name');
        $companyAddress = \App\Models\ErpParameter::get('company_address', '');
        $companyPhone = \App\Models\ErpParameter::get('company_phone', '');
        $companyEmail = \App\Models\ErpParameter::get('company_email', '');
    @endphp

    <div class="company-header">
        <div class="company-name">{{ $companyName }}</div>
        <div class="company-details">{{ $companyAddress }}@if ($companyPhone || $companyEmail) | {{ $companyPhone }}{{ $companyPhone && $companyEmail ? ' | ' : '' }}{{ $companyEmail }}@endif</div>
    </div>

    <div class="header">
        <h1>PURCHASE ORDER</h1>
        <div>{{ $order->order_no ?? '#' . $order->id }}</div>
    </div>

    <table class="info-table">
        <tr>
            <td class="label">Date:</td>
            <td>{{ $order->date ? $order->date->format('d M Y') : '-' }}</td>
            <td class="label">Vendor:</td>
            <td>{{ $order->businessPartner->name ?? 'N/A' }}</td>
        </tr>
        @if ($order->reference_no)
        <tr>
            <td class="label">Ref:</td>
            <td colspan="3">{{ $order->reference_no }}</td>
        </tr>
        @endif
        <tr>
            <td class="label">Expected:</td>
            <td>{{ $order->expected_delivery_date ? $order->expected_delivery_date->format('d M Y') : '-' }}</td>
            <td class="label">Total:</td>
            <td>Rp {{ number_format($order->total_amount, 2) }}</td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th class="text-center">No</th>
                <th>Code</th>
                <th>Description</th>
                <th class="text-right">Qty</th>
                <th class="text-right">Price</th>
                <th class="text-right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->lines as $index => $line)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $line->inventoryItem->code ?? $line->item_code ?? '-' }}</td>
                <td>{{ $line->inventoryItem->name ?? $line->item_name ?? $line->description ?? '-' }}</td>
                <td class="text-right">{{ number_format($line->qty, 2) }}</td>
                <td class="text-right">{{ number_format($line->unit_price, 2) }}</td>
                <td class="text-right">{{ number_format($line->amount, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="5" class="text-right">Total</th>
                <th class="text-right">Rp {{ number_format($order->total_amount, 2) }}</th>
            </tr>
        </tfoot>
    </table>

    <div style="margin-top: 12px; font-size: 10px;">
        Prepared: {{ $order->createdBy->name ?? 'N/A' }} | {{ $order->created_at->format('d M Y') }}
    </div>
</body>

</html>
