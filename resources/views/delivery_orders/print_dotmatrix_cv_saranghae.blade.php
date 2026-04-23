<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DO {{ $deliveryOrder->do_number }}</title>
    <style>
        /*
         * Tuned for Epson LX-310 (9.5" tractor / ~8" printable @ 10 CPI ≈ 80 cols).
         * Use driver: continuous form, same width; disable fit-to-page if output scales wrong.
         */
        @page {
            size: 241mm 297mm;
            margin: 4mm 5mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Courier New', 'Liberation Mono', Courier, monospace;
            font-size: 9.5pt;
            line-height: 1.15;
            margin: 0;
            padding: 6px;
            max-width: 80ch;
            width: 100%;
            background: #fff;
            color: #000;
        }

        .company-header {
            text-align: center;
            margin-bottom: 6px;
            padding-bottom: 3px;
            border-bottom: 1px solid #000;
        }

        .company-logo {
            margin-bottom: 2px;
            background: #fff;
            padding: 2px;
            display: inline-block;
        }

        .company-logo img {
            height: 36px;
            max-width: 100%;
            image-rendering: crisp-edges;
        }

        .company-name {
            font-size: 10.5pt;
            font-weight: bold;
            margin-bottom: 1px;
        }

        .company-details {
            font-size: 8.5pt;
        }

        .header {
            text-align: center;
            margin: 6px 0;
        }

        .header h1 {
            font-size: 11pt;
            margin: 0 0 1px 0;
            font-weight: bold;
        }

        .header .do-number {
            font-size: 9.5pt;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
            font-size: 8.5pt;
        }

        th, td {
            border: 1px solid #000;
            padding: 1px 3px;
            text-align: left;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        th {
            font-weight: bold;
            background: #fff;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }

        .info-table td {
            border: none;
            padding: 0 3px 1px 0;
            vertical-align: top;
        }

        .info-table .label {
            width: 1%;
            white-space: nowrap;
            font-weight: bold;
        }

        .delivery-address-cell {
            white-space: pre-line;
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        .items-table {
            table-layout: fixed;
        }

        .items-table th:nth-child(1),
        .items-table td:nth-child(1) { width: 4%; }
        .items-table th:nth-child(2),
        .items-table td:nth-child(2) { width: 11%; }
        .items-table th:nth-child(3),
        .items-table td:nth-child(3) { width: 9%; }
        .items-table th:nth-child(4),
        .items-table td:nth-child(4) { width: 46%; }
        .items-table th:nth-child(5),
        .items-table td:nth-child(5) { width: 12%; }
        .items-table th:nth-child(6),
        .items-table td:nth-child(6) { width: 10%; }
        .items-table th:nth-child(7),
        .items-table td:nth-child(7) { width: 8%; }

        .items-table td:nth-child(4) {
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        .signature-row {
            margin-top: 14pt;
            font-size: 8.5pt;
        }

        .signature-row td {
            border: none;
            padding: 4px 2px;
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
            body {
                padding: 0;
                margin: 0;
                background: #fff;
                max-width: none;
            }
            .company-logo { background: #fff !important; }
            .company-logo img {
                height: 28px;
            }
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

    @php
        $customerPhone = $deliveryOrder->delivery_phone
            ?: ($deliveryOrder->customer?->primary_contact_phone ?? null);
    @endphp
    <table class="info-table">
        <tr>
            <td class="label">SO:</td>
            <td colspan="3">{{ $deliveryOrder->salesOrder?->order_no ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Customer:</td>
            <td colspan="3">{{ $deliveryOrder->customer?->name ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="label">Customer Phone:</td>
            <td colspan="3">{{ $customerPhone ?: '—' }}</td>
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
                <th>Code</th>
                <th>Part#</th>
                <th>Description</th>
                <th class="text-right">Qty</th>
                <th class="text-center">UOM</th>
                <th class="text-center">Ok</th>
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
