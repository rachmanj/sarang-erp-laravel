<!DOCTYPE html>
<html>

<head>
    <title>Purchase Order - {{ $order->order_no ?? '#' . $order->id }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; padding-bottom: 60px; }
        .company-header { margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #333; }
        .company-logo { float: left; margin-right: 20px; }
        .company-logo img { height: 60px; }
        .company-info { overflow: hidden; }
        .company-name { font-size: 18px; font-weight: bold; margin-bottom: 5px; }
        .company-details { font-size: 11px; color: #666; line-height: 1.4; }
        .header { text-align: center; margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .print-float-btn {
            position: fixed; bottom: 24px; right: 24px; padding: 12px 24px;
            background: #007bff; color: white; border: none; border-radius: 8px;
            cursor: pointer; font-size: 16px;
        }
        @media print { body { margin: 0; padding-bottom: 0; } .no-print { display: none !important; } }
    </style>
</head>

<body>
    <button class="no-print print-float-btn" onclick="window.print()">Print</button>

    <div class="company-header">
        @php
            $companyName = \App\Models\ErpParameter::get('company_name', 'Company Name');
            $companyAddress = \App\Models\ErpParameter::get('company_address', '');
            $companyPhone = \App\Models\ErpParameter::get('company_phone', '');
            $companyEmail = \App\Models\ErpParameter::get('company_email', '');
        @endphp
        @if (file_exists(public_path('logo_pt_csj.png')))
        <div class="company-logo">
            <img src="{{ asset('logo_pt_csj.png') }}" alt="Logo">
        </div>
        @endif
        <div class="company-info">
            <div class="company-name">{{ $companyName }}</div>
            <div class="company-details">
                @if ($companyAddress){{ $companyAddress }}<br>@endif
                @if ($companyPhone || $companyEmail){{ $companyPhone }}{{ $companyPhone && $companyEmail ? ' | ' : '' }}{{ $companyEmail }}@endif
            </div>
        </div>
    </div>

    <div class="header">
        <h1>PURCHASE ORDER</h1>
        <h2>{{ $order->order_no ?? '#' . $order->id }}</h2>
    </div>

    <table>
        <tr>
            <td><strong>Date:</strong></td>
            <td>{{ $order->date ? $order->date->format('d M Y') : '-' }}</td>
            <td><strong>Vendor:</strong></td>
            <td>{{ $order->businessPartner->name ?? 'N/A' }}</td>
        </tr>
        @if ($order->reference_no)
        <tr>
            <td><strong>Reference:</strong></td>
            <td colspan="3">{{ $order->reference_no }}</td>
        </tr>
        @endif
        @if ($order->expected_delivery_date)
        <tr>
            <td><strong>Expected Delivery:</strong></td>
            <td>{{ $order->expected_delivery_date->format('d M Y') }}</td>
            <td><strong>Total:</strong></td>
            <td>Rp {{ number_format($order->total_amount, 2) }}</td>
        </tr>
        @endif
    </table>

    <table>
        <thead>
            <tr>
                <th class="text-center" style="width:50px">No</th>
                <th>Item Code</th>
                <th>Description</th>
                <th class="text-right">Qty</th>
                <th class="text-right">Unit Price</th>
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

    <div style="margin-top: 40px;">
        <strong>Prepared by:</strong> {{ $order->createdBy->name ?? 'N/A' }} | Date: {{ $order->created_at->format('d M Y') }}
    </div>
</body>

</html>
