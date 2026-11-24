<!DOCTYPE html>
<html>
<head>
    <title>PO {{ $order->order_no ?? '#' . $order->id }}</title>
    <style>
        @page { size: 9.5in; margin: 0.25in; }
        body { font-family: 'Courier New', monospace; font-size: 11px; margin: 0; padding: 8px; max-width: 9.5in; }
        .company-header { text-align: center; margin-bottom: 8px; border-bottom: 1px solid #000; }
        .company-header .logo { max-height: 48px; margin-bottom: 4px; }
        .company-name { font-size: 12px; font-weight: bold; }
        .company-details { font-size: 9px; line-height: 1.3; }
        .header { text-align: center; margin: 8px 0; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; font-size: 10px; }
        th, td { border: 1px solid #000; padding: 3px 4px; }
        th { font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .print-btn { position: fixed; bottom: 16px; right: 16px; padding: 8px 16px; background: #333; color: white; border: none; cursor: pointer; font-size: 12px; }
        @media print { .no-print { display: none !important; } }
    </style>
</head>
<body>
    <button class="no-print print-btn" onclick="window.print()">Print</button>
    @include('partials.letterhead_pt_csj', ['entity' => $entity ?? null, 'letterheadVariant' => 'dotmatrix'])
    <div class="header">
        <h1>PURCHASE ORDER</h1>
        <div>{{ $order->order_no ?? '#' . $order->id }}</div>
    </div>
    <table>
        <tr>
            <td>Date:</td>
            <td>{{ $order->date ? $order->date->format('d M Y') : '-' }}</td>
            <td>Vendor:</td>
            <td>{{ $order->businessPartner->name ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td>Expected:</td>
            <td>{{ $order->expected_delivery_date ? $order->expected_delivery_date->format('d M Y') : '-' }}</td>
            <td>Total:</td>
            <td>Rp {{ number_format($orderFooter['amount_due'], 2, ',', '.') }}</td>
        </tr>
    </table>
    <table>
        <thead>
            <tr>
                <th class="text-center">No</th>
                <th>Item Code</th>
                <th>Part No.</th>
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
                <td>{{ $line->partNumber?->part_number ?? '-' }}</td>
                <td>{{ $line->inventoryItem->name ?? $line->item_name ?? $line->description ?? '-' }}</td>
                <td class="text-right">{{ number_format($line->qty, 2, ',', '.') }}</td>
                <td class="text-right">{{ number_format($line->unit_price, 2, ',', '.') }}</td>
                <td class="text-right">{{ number_format(\App\Services\Accounting\PurchaseOrderFooterMath::lineDpp($line), 2, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
        @include('purchase_orders.partials.print-items-tax-tfoot', [
            'labelColspan' => 6,
            'decSep' => ',',
            'thSep' => '.',
        ])
    </table>
    <div style="margin-top: 12px; font-size: 10px;">
        Prepared: {{ $order->createdBy->name ?? 'N/A' }} | {{ $order->created_at->format('d M Y') }}
    </div>
</body>
</html>
