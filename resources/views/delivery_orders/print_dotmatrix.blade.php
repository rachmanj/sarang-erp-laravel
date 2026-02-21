<!DOCTYPE html>
<html>

<head>
    <title>DO {{ $deliveryOrder->do_number }}</title>
    <style>
        @page {
            size: 9.5in;
            margin: 0.25in;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 11px;
            line-height: 1.2;
            margin: 0;
            padding: 8px;
            max-width: 9.5in;
            width: 100%;
        }

        .company-header {
            text-align: center;
            margin-bottom: 8px;
            padding-bottom: 4px;
            border-bottom: 1px solid #000;
        }

        .company-name {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .company-details {
            font-size: 10px;
        }

        .header {
            text-align: center;
            margin: 8px 0;
        }

        .header h1 {
            font-size: 14px;
            margin: 0 0 2px 0;
            font-weight: bold;
        }

        .header .do-number {
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            font-size: 10px;
        }

        th, td {
            border: 1px solid #000;
            padding: 3px 4px;
            text-align: left;
        }

        th {
            font-weight: bold;
            background: #fff;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }

        .info-table td {
            border: none;
            padding: 1px 4px;
            vertical-align: top;
        }

        .info-table .label {
            width: 1%;
            white-space: nowrap;
            font-weight: bold;
        }

        .delivery-address-cell {
            white-space: pre-line;
            max-width: 0;
        }

        .items-table th:nth-child(1) { width: 5%; }
        .items-table th:nth-child(2) { width: 15%; }
        .items-table th:nth-child(3) { width: 45%; }
        .items-table th:nth-child(4) { width: 15%; }
        .items-table th:nth-child(5) { width: 8%; }

        .signature-row {
            margin-top: 12px;
            font-size: 10px;
        }

        .signature-row td {
            border: none;
            padding: 4px;
            vertical-align: top;
        }

        .print-float-btn {
            position: fixed;
            bottom: 16px;
            right: 16px;
            padding: 8px 16px;
            background: #333;
            color: white;
            border: none;
            cursor: pointer;
            font-family: Arial, sans-serif;
            font-size: 12px;
        }

        @media print {
            body { padding: 0; margin: 0; }
            .no-print { display: none !important; }
        }
    </style>
</head>

<body>
    <button class="no-print print-float-btn" onclick="window.print()">Print</button>

    @php
        $companyName = \App\Models\ErpParameter::get('company_name', 'Company Name');
        $companyAddress = \App\Models\ErpParameter::get('company_address', '');
        $companyPhone = \App\Models\ErpParameter::get('company_phone', '');
        $companyEmail = \App\Models\ErpParameter::get('company_email', '');
    @endphp

    <div class="company-header">
        <div class="company-name">{{ $companyName }}</div>
        <div class="company-details">
            {{ $companyAddress }}
            @if ($companyPhone || $companyEmail)
                | {{ $companyPhone }}{{ $companyPhone && $companyEmail ? ' | ' : '' }}{{ $companyEmail }}
            @endif
        </div>
    </div>

    <div class="header">
        <h1>DELIVERY ORDER</h1>
        <div class="do-number">{{ $deliveryOrder->do_number }}</div>
    </div>

    <table class="info-table">
        <tr>
            <td class="label">SO:</td>
            <td>{{ $deliveryOrder->salesOrder?->order_no ?? '-' }}</td>
            <td class="label">Customer:</td>
            <td>{{ $deliveryOrder->customer?->name ?? 'N/A' }}</td>
        </tr>
        @if ($deliveryOrder->salesOrder?->reference_no)
        <tr>
            <td class="label">Ref:</td>
            <td colspan="3">{{ $deliveryOrder->salesOrder->reference_no }}</td>
        </tr>
        @endif
        <tr>
            <td class="label">Date:</td>
            <td>{{ $deliveryOrder->planned_delivery_date->format('d M Y') }}</td>
            <td class="label">Method:</td>
            <td>{{ ucfirst(str_replace('_', ' ', $deliveryOrder->delivery_method)) }}</td>
        </tr>
        <tr>
            <td class="label">Address:</td>
            <td colspan="3" class="delivery-address-cell">{{ $deliveryOrder->delivery_address ?? '' }}</td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th class="text-center">No</th>
                <th>Item Code</th>
                <th>Item Name</th>
                <th class="text-right">Qty</th>
                <th class="text-center">[ ]</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($deliveryOrder->lines as $index => $line)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $line->item_code ?? 'N/A' }}</td>
                <td>{{ $line->item_name ?? 'N/A' }}</td>
                <td class="text-right">{{ number_format($line->delivered_qty > 0 ? $line->delivered_qty : $line->ordered_qty, 2) }}</td>
                <td class="text-center">[ ]</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table class="info-table signature-row">
        <tr>
            <td style="width: 50%;">
                Prepared: _________________________<br>
                {{ $deliveryOrder->createdBy->name ?? 'N/A' }} | {{ $deliveryOrder->created_at->format('d M Y') }}
            </td>
            <td style="width: 50%;">
                Received: _________________________<br>
                Customer | Date: _______________
            </td>
        </tr>
    </table>
</body>

</html>
