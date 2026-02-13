<!DOCTYPE html>
<html>

<head>
    <title>Delivery Order - {{ $deliveryOrder->do_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            padding-bottom: 60px;
        }

        .company-header {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #333;
        }

        .company-logo {
            float: left;
            margin-right: 20px;
        }

        .company-logo img {
            height: 60px;
        }

        .company-info {
            overflow: hidden;
        }

        .company-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .company-details {
            font-size: 11px;
            color: #666;
            line-height: 1.4;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .delivery-address-cell {
            white-space: pre-line;
            vertical-align: top;
            min-height: 80px;
        }

        .print-float-btn {
            position: fixed;
            bottom: 24px;
            right: 24px;
            padding: 12px 24px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            font-size: 16px;
        }

        .print-float-btn:hover {
            background: #0056b3;
        }

        @media print {
            body {
                margin: 0;
                padding-bottom: 0;
            }

            .no-print {
                display: none !important;
            }
        }
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
            $companyTaxNumber = \App\Models\ErpParameter::get('company_tax_number', '');
        @endphp

        <div class="company-logo">
            <img src="{{ asset('logo_pt_csj.png') }}" alt="Logo">
        </div>

        <div class="company-info">
            <div class="company-name">{{ $companyName }}</div>
            <div class="company-details">
                @if ($companyAddress)
                    {{ $companyAddress }}<br>
                @endif
                @if ($companyPhone || $companyEmail)
                    @if ($companyPhone)
                        Phone: {{ $companyPhone }}
                    @endif
                    @if ($companyPhone && $companyEmail)
                        |
                    @endif
                    @if ($companyEmail)
                        Email: {{ $companyEmail }}
                    @endif
                    <br>
                @endif
                @if ($companyTaxNumber)
                    Tax Number: {{ $companyTaxNumber }}
                @endif
            </div>
        </div>
    </div>

    <div class="header">
        <h1>DELIVERY ORDER</h1>
        <h2>{{ $deliveryOrder->do_number }}</h2>
    </div>

    <table>
        <tr>
            <td><strong>Sales Order:</strong></td>
            <td>{{ $deliveryOrder->salesOrder?->order_no ?? '-' }}</td>
            <td><strong>Customer:</strong></td>
            <td>{{ $deliveryOrder->customer?->name ?? 'N/A' }}</td>
        </tr>
        @if ($deliveryOrder->salesOrder?->reference_no)
        <tr>
            <td><strong>Customer Ref No:</strong></td>
            <td colspan="3">{{ $deliveryOrder->salesOrder->reference_no }}</td>
        </tr>
        @endif
        <tr>
            <td><strong>Planned Delivery:</strong></td>
            <td>{{ $deliveryOrder->planned_delivery_date->format('d M Y') }}</td>
            <td><strong>Delivery Method:</strong></td>
            <td>{{ ucfirst(str_replace('_', ' ', $deliveryOrder->delivery_method)) }}</td>
        </tr>
        <tr>
            <td><strong>Delivery Address:</strong></td>
            <td colspan="3" class="delivery-address-cell">{{ $deliveryOrder->delivery_address ?? '' }}</td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th class="text-center" style="width: 50px;">No</th>
                <th>Item Code</th>
                <th>Item Name</th>
                <th class="text-right">Delivered Qty</th>
                <th class="text-center" style="width: 50px;"></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($deliveryOrder->lines as $index => $line)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $line->item_code ?? 'N/A' }}</td>
                    <td>{{ $line->item_name ?? 'N/A' }}</td>
                    <td class="text-right">{{ number_format($line->delivered_qty > 0 ? $line->delivered_qty : $line->ordered_qty, 2) }}</td>
                    <td class="text-center"><input type="checkbox" disabled></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 50px;">
        <table>
            <tr>
                <td style="width: 50%;">
                    <strong>Prepared by:</strong><br><br><br>
                    _________________________<br>
                    {{ $deliveryOrder->createdBy->name ?? 'N/A' }}<br>
                    Date: {{ $deliveryOrder->created_at->format('d M Y') }}
                </td>
                <td style="width: 50%;">
                    <strong>Received by:</strong><br><br><br>
                    _________________________<br>
                    Customer Signature<br>
                    Date: _______________
                </td>
            </tr>
        </table>
    </div>
</body>

</html>
