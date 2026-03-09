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
            background: #fff;
        }

        .company-header {
            text-align: center;
            margin-bottom: 8px;
            padding-bottom: 4px;
            border-bottom: 1px solid #000;
        }

        .company-logo {
            margin-bottom: 4px;
            background: #fff;
            padding: 4px;
            display: inline-block;
        }

        .company-logo img {
            height: 40px;
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
        .items-table th:nth-child(2) { width: 12%; }
        .items-table th:nth-child(3) { width: 38%; }
        .items-table th:nth-child(4) { width: 8%; }
        .items-table th:nth-child(5) { width: 12%; }
        .items-table th:nth-child(6) { width: 8%; }

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
            body { padding: 0; margin: 0; background: #fff; }
            .company-logo { background: #fff !important; }
            .no-print { display: none !important; }
        }
    </style>
</head>

<body>
    <button class="no-print print-float-btn" onclick="window.print()">Print</button>

    @php
        $entity = $entity ?? \App\Models\CompanyEntity::where('name', 'CV Cahaya Saranghae')->first();
        $companyName = $entity?->name ?? 'CV Cahaya Saranghae';
        $companyAddress = $entity?->address ?? \App\Models\ErpParameter::get('cv_cahaya_saranghae_address', '');
        $companyPhone = $entity?->phone ?? \App\Models\ErpParameter::get('cv_cahaya_saranghae_phone', '');
        $companyEmail = $entity?->email ?? \App\Models\ErpParameter::get('cv_cahaya_saranghae_email', '');
        $companyTaxNumber = $entity?->tax_number ?? \App\Models\ErpParameter::get('cv_cahaya_saranghae_tax_number', '');
        $logoPath = file_exists(public_path('logo_cv_saranghae_saja_light.png'))
            ? 'logo_cv_saranghae_saja_light.png'
            : 'logo_cv_saranghae_saja.png';
    @endphp

    <div class="company-header">
        <div class="company-logo">
            <img src="{{ asset($logoPath) }}" alt="CV Cahaya Saranghae">
        </div>
        <div class="company-name">{{ $companyName }}</div>
        <div class="company-details">
            {{ $companyAddress }}
            @if ($companyPhone || $companyEmail)
                | {{ $companyPhone }}{{ $companyPhone && $companyEmail ? ' | ' : '' }}{{ $companyEmail }}
            @endif
            @if ($companyTaxNumber)
                | NPWP: {{ $companyTaxNumber }}
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
        @if ($deliveryOrder->businessPartnerProject)
        <tr>
            <td class="label">Project:</td>
            <td colspan="3">{{ $deliveryOrder->businessPartnerProject->display_name }}</td>
        </tr>
        @endif
        @if ($deliveryOrder->salesOrder?->reference_no)
        <tr>
            <td class="label">Customer Ref No:</td>
            <td colspan="3">{{ $deliveryOrder->salesOrder->reference_no }}</td>
        </tr>
        @endif
        <tr>
            <td class="label">Delivery Date:</td>
            <td colspan="3"></td>
        </tr>
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
        @if ($deliveryOrder->notes)
        <tr>
            <td class="label">Description:</td>
            <td colspan="3" class="delivery-address-cell">{{ $deliveryOrder->notes }}</td>
        </tr>
        @endif
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th class="text-center">No</th>
                <th>Item Code</th>
                <th>Part No.</th>
                <th>Item Name</th>
                <th class="text-right">Qty</th>
                <th class="text-center">UOM</th>
                <th class="text-center">[ ]</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($deliveryOrder->lines as $index => $line)
            @php
                $uom = $line->salesOrderLine?->unit_of_measure
                    ?? $line->salesOrderLine?->orderUnit?->code
                    ?? $line->inventoryItem?->baseUnit?->unit?->code
                    ?? '-';
            @endphp
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $line->inventoryItem?->code ?? $line->item_code ?? 'N/A' }}</td>
                <td>{{ $line->partNumber?->part_number ?? '-' }}</td>
                <td>{{ $line->item_name ?? 'N/A' }}</td>
                <td class="text-right">{{ number_format($line->delivered_qty > 0 ? $line->delivered_qty : $line->ordered_qty, 2) }}</td>
                <td class="text-center">{{ $uom }}</td>
                <td class="text-center">[ ]</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table class="info-table signature-row">
        <tr>
            <td style="width: 33%;">
                Prepared: _________________________<br>
                {{ $deliveryOrder->createdBy->name ?? 'N/A' }} | {{ $deliveryOrder->created_at->format('d M Y') }}
            </td>
            <td style="width: 34%;">
                Sender: _________________________<br>
                Date: _______________
            </td>
            <td style="width: 33%;">
                Received: _________________________<br>
                Customer | Date: _______________
            </td>
        </tr>
    </table>
</body>

</html>
