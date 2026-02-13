<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Sales Invoice {{ $invoice->invoice_no ?? '#' . $invoice->id }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
        }

        .company-header {
            margin-bottom: 24px;
            padding-bottom: 15px;
            border-bottom: 2px solid #333;
        }

        .company-logo {
            float: left;
            margin-right: 20px;
        }

        .company-logo img {
            height: 60px;
            max-width: 180px;
            object-fit: contain;
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
            font-size: 10px;
            color: #444;
            line-height: 1.5;
        }

        .document-section {
            margin-bottom: 20px;
        }

        .document-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            gap: 24px;
        }

        .invoice-info {
            min-width: 200px;
        }

        .invoice-info .label {
            font-weight: bold;
            color: #555;
            margin-bottom: 2px;
        }

        .bill-to-box {
            border: 1px solid #ccc;
            padding: 12px;
            background: #fafafa;
            min-width: 280px;
        }

        .bill-to-box .title {
            font-weight: bold;
            margin-bottom: 8px;
            font-size: 12px;
        }

        .bill-to-box .content {
            font-size: 11px;
            line-height: 1.5;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 6px 8px;
        }

        th {
            background: #f5f5f5;
            font-weight: 600;
        }

        .text-right {
            text-align: right;
        }

        .totals-section {
            margin-top: 16px;
            width: 300px;
            margin-left: auto;
        }

        .totals-row {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
            border-bottom: 1px solid #eee;
        }

        .totals-row.total-due {
            font-weight: bold;
            font-size: 13px;
            margin-top: 8px;
            padding-top: 8px;
            border-top: 2px solid #333;
        }

        .signature-block {
            margin-top: 48px;
            display: flex;
            justify-content: flex-end;
        }

        .signature-box {
            text-align: center;
            width: 220px;
        }

        .signature-line {
            border-bottom: 1px solid #333;
            height: 40px;
            margin-bottom: 6px;
        }

        .signature-label {
            font-size: 10px;
            color: #555;
        }

        .footer-note {
            margin-top: 24px;
            padding-top: 12px;
            border-top: 1px solid #ddd;
            font-size: 10px;
            color: #666;
            line-height: 1.5;
        }

        .float-print-btn {
            position: fixed;
            bottom: 24px;
            right: 24px;
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: #007bff;
            color: #fff;
            border: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.25);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            z-index: 1000;
            transition: background 0.2s, transform 0.2s;
        }

        .float-print-btn:hover {
            background: #0056b3;
            transform: scale(1.05);
        }

        @media print {
            .float-print-btn { display: none !important; }
        }
    </style>
</head>

<body>
    <div class="company-header">
        @php
            $companyName = \App\Models\ErpParameter::get('company_name', 'Company Name');
            $companyAddress = \App\Models\ErpParameter::get('company_address', '');
            $companyPhone = \App\Models\ErpParameter::get('company_phone', '');
            $companyEmail = \App\Models\ErpParameter::get('company_email', '');
            $companyTaxNumber = \App\Models\ErpParameter::get('company_tax_number', '');
        @endphp

        @php
            $logoDataUri = null;
            $logoPath = public_path('logo_pt_csj.png');
            if (file_exists($logoPath)) {
                $logoDataUri = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
            }
        @endphp
        @if ($logoDataUri)
            <div class="company-logo">
                <img src="{{ $logoDataUri }}" alt="{{ $companyName }}">
            </div>
        @endif

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
                    Tax ID: {{ $companyTaxNumber }}
                @endif
            </div>
        </div>
    </div>

    <div class="document-section">
        <div class="document-header">
            <div class="invoice-info">
                <h2 style="margin: 0 0 12px 0;">SALES INVOICE</h2>
                <div><span class="label">Invoice No:</span> {{ $invoice->invoice_no ?? '#' . $invoice->id }}</div>
                <div><span class="label">Date:</span> {{ $invoice->date ? $invoice->date->format('d M Y') : '—' }}</div>
                <div><span class="label">Due Date:</span> {{ $invoice->due_date ? $invoice->due_date->format('d M Y') : '—' }}</div>
                @if ($invoice->terms_days)
                    <div><span class="label">Terms:</span> {{ $invoice->terms_days }} days</div>
                @endif
                @if ($invoice->reference_no)
                    <div><span class="label">Reference No:</span> {{ $invoice->reference_no }}</div>
                @endif
                @if ($invoice->description)
                    <div><span class="label">Description:</span> {{ $invoice->description }}</div>
                @endif
                @if ($invoice->deliveryOrders && $invoice->deliveryOrders->isNotEmpty())
                    <div><span class="label">Delivery Order(s):</span> {{ $invoice->deliveryOrders->map(fn($d) => $d->do_number ?? '#' . $d->id)->implode(', ') }}</div>
                @endif
            </div>
            <div class="bill-to-box">
                <div class="title">Bill To</div>
                <div class="content">
                    <strong>{{ optional($invoice->businessPartner)->name ?? '—' }}</strong><br>
                    @if ($invoice->businessPartner && $invoice->businessPartner->code)
                        <span style="color:#666;">Code: {{ $invoice->businessPartner->code }}</span><br>
                    @endif
                    @if ($invoice->businessPartner && $invoice->businessPartner->tax_id)
                        <span style="color:#666;">Tax ID: {{ $invoice->businessPartner->tax_id }}</span><br>
                    @endif
                    @if ($invoice->businessPartner && $invoice->businessPartner->primaryAddress)
                        @php $addr = $invoice->businessPartner->primaryAddress; @endphp
                        @if ($addr->address_line_1)
                            {{ $addr->address_line_1 }}<br>
                        @endif
                        @if ($addr->address_line_2)
                            {{ $addr->address_line_2 }}<br>
                        @endif
                        @if ($addr->city || $addr->state_province || $addr->postal_code)
                            {{ trim(implode(', ', array_filter([$addr->city, $addr->state_province, $addr->postal_code]))) }}<br>
                        @endif
                        @if ($addr->country)
                            {{ $addr->country }}
                        @endif
                    @elseif ($invoice->businessPartner)
                        {{ optional($invoice->businessPartner->primaryAddress)->full_address ?? '—' }}
                    @endif
                </div>
            </div>
        </div>
    </div>

    @php
        $originalTotal = $invoice->lines->sum('amount');
        $totalVat = 0;
        $totalWtax = 0;
        foreach ($invoice->lines as $l) {
            $lineBase = (float) $l->qty * (float) $l->unit_price;
            $vatRate = $l->taxCode ? (float) $l->taxCode->rate : 0;
            $wtaxRate = (float) ($l->wtax_rate ?? 0);
            $totalVat += $lineBase * ($vatRate / 100);
            $totalWtax += $lineBase * ($wtaxRate / 100);
        }
        $amountDue = $originalTotal + $totalVat - $totalWtax;
    @endphp

    <table>
        <thead>
            <tr>
                <th style="width: 40px;">No</th>
                <th>Item Code</th>
                <th>Description</th>
                <th class="text-right" style="width: 80px;">Qty</th>
                <th class="text-right" style="width: 100px;">Unit Price</th>
                <th class="text-right" style="width: 110px;">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->lines as $num => $l)
                <tr>
                    <td>{{ $num + 1 }}</td>
                    <td>{{ $l->item_code ?? optional($l->inventoryItem)->code ?? '—' }}</td>
                    <td>{{ $l->item_name ?? $l->description ?? '—' }}</td>
                    <td class="text-right">{{ number_format($l->qty, 2) }}</td>
                    <td class="text-right">{{ number_format($l->unit_price, 2) }}</td>
                    <td class="text-right">{{ number_format($l->amount, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals-section">
        <div class="totals-row">
            <span>Subtotal:</span>
            <span>{{ number_format($originalTotal, 2) }}</span>
        </div>
        @if ($totalVat != 0)
            <div class="totals-row">
                <span>Total VAT:</span>
                <span>{{ number_format($totalVat, 2) }}</span>
            </div>
        @endif
        @if ($totalWtax != 0)
            <div class="totals-row">
                <span>Total WTax:</span>
                <span>({{ number_format($totalWtax, 2) }})</span>
            </div>
        @endif
        <div class="totals-row total-due">
            <span>Amount Due:</span>
            <span>{{ number_format($amountDue, 2) }}</span>
        </div>
    </div>

    <div class="signature-block">
        <div class="signature-box">
            <div class="signature-line"></div>
            <div class="signature-label">Authorized Signatory</div>
        </div>
    </div>

    <div class="footer-note">
        <strong>Payment Terms:</strong> Please pay within {{ $invoice->terms_days ?? 0 }} days from invoice date. Make payment before Due Date to avoid late payment charges.
        <br><br>
        Thank you for your business.
    </div>

    <button type="button" class="float-print-btn" onclick="window.print()" title="Print">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
    </button>
</body>

</html>
