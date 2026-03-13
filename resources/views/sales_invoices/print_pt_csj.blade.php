<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Sales Invoice {{ $invoice->invoice_no ?? '#' . $invoice->id }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; padding-bottom: 60px; font-size: 11px; }
        .company-header { margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #333; }
        .company-logo { float: left; margin-right: 20px; }
        .company-logo img { height: 60px; max-width: 180px; object-fit: contain; }
        .company-info { overflow: hidden; }
        .company-name { font-size: 18px; font-weight: bold; margin-bottom: 5px; }
        .company-details { font-size: 10px; color: #444; line-height: 1.5; }
        .document-section { margin-bottom: 20px; }
        .document-header { display: flex; justify-content: space-between; margin-bottom: 20px; gap: 24px; }
        .invoice-info { min-width: 200px; }
        .invoice-info .label { font-weight: bold; color: #555; margin-bottom: 2px; }
        .bill-to-box { border: 1px solid #ccc; padding: 12px; background: #fafafa; min-width: 280px; }
        .bill-to-box .title { font-weight: bold; margin-bottom: 8px; font-size: 12px; }
        .bill-to-box .content { font-size: 11px; line-height: 1.5; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; }
        th { background: #f5f5f5; font-weight: 600; }
        .text-right { text-align: right; }
        .totals-section { margin-top: 16px; width: 300px; margin-left: auto; }
        .totals-row { display: flex; justify-content: space-between; padding: 4px 0; border-bottom: 1px solid #eee; }
        .totals-row.total-due { font-weight: bold; font-size: 13px; margin-top: 8px; padding-top: 8px; border-top: 2px solid #333; }
        .signature-block { margin-top: 48px; display: flex; justify-content: flex-end; }
        .signature-box { text-align: center; width: 220px; }
        .signature-line { border-bottom: 1px solid #333; height: 40px; margin-bottom: 6px; }
        .signature-label { font-size: 10px; color: #555; }
        .footer-note { margin-top: 24px; padding-top: 12px; border-top: 1px solid #ddd; font-size: 10px; color: #666; line-height: 1.5; }
        .print-float-btn { position: fixed; bottom: 24px; right: 24px; padding: 12px 24px; background: #007bff; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 16px; }
        @media print { body { margin: 0; padding-bottom: 0; } .no-print { display: none !important; } }
    </style>
</head>

<body>
    <button class="no-print print-float-btn" onclick="window.print()">Print</button>

    <div class="company-header">
        @php
            $entity = $entity ?? \App\Models\CompanyEntity::where('name', 'PT Cahaya Sarange Jaya')->first();
            $companyName = $entity?->name ?? 'PT Cahaya Sarange Jaya';
            $companyAddress = $entity?->address ?? \App\Models\ErpParameter::get('company_address', '');
            $companyPhone = $entity?->phone ?? \App\Models\ErpParameter::get('company_phone', '');
            $companyEmail = $entity?->email ?? \App\Models\ErpParameter::get('company_email', '');
            $companyTaxNumber = $entity?->tax_number ?? \App\Models\ErpParameter::get('company_tax_number', '');
        @endphp
        @if (file_exists(public_path('logo_pt_csj_transparan.jpeg')))
            <div class="company-logo">
                <img src="{{ asset('logo_pt_csj_transparan.jpeg') }}" alt="{{ $companyName }}">
            </div>
        @endif
        <div class="company-info">
            <div class="company-name">{{ $companyName }}</div>
            <div class="company-details">
                @if ($companyAddress){{ $companyAddress }}<br>@endif
                @if ($companyPhone || $companyEmail){{ $companyPhone }}{{ $companyPhone && $companyEmail ? ' | ' : '' }}{{ $companyEmail }}@endif
                @if ($companyTaxNumber)<br>Tax ID: {{ $companyTaxNumber }}@endif
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
                @if ($invoice->terms_days)<div><span class="label">Terms:</span> {{ $invoice->terms_days }} days</div>@endif
                @if ($invoice->reference_no)<div><span class="label">Reference No:</span> {{ $invoice->reference_no }}</div>@endif
                @if ($invoice->businessPartnerProject)<div><span class="label">Customer's Project:</span> {{ $invoice->businessPartnerProject->display_name }}</div>@endif
                @if ($invoice->description)<div><span class="label">Description:</span> {{ $invoice->description }}</div>@endif
                @if ($invoice->deliveryOrders && $invoice->deliveryOrders->isNotEmpty())
                    <div><span class="label">Delivery Order(s):</span> {{ $invoice->deliveryOrders->map(fn($d) => $d->do_number ?? '#' . $d->id)->implode(', ') }}</div>
                @endif
            </div>
            <div class="bill-to-box">
                <div class="title">Bill To</div>
                <div class="content">
                    <strong>{{ optional($invoice->businessPartner)->name ?? '—' }}</strong><br>
                    @if ($invoice->businessPartner && $invoice->businessPartner->code)<span style="color:#666;">Code: {{ $invoice->businessPartner->code }}</span><br>@endif
                    @if ($invoice->businessPartner && $invoice->businessPartner->tax_id)<span style="color:#666;">Tax ID: {{ $invoice->businessPartner->tax_id }}</span><br>@endif
                    @if ($invoice->businessPartner && $invoice->businessPartner->primaryAddress)
                        @php $addr = $invoice->businessPartner->primaryAddress; @endphp
                        @if ($addr->address_line_1){{ $addr->address_line_1 }}<br>@endif
                        @if ($addr->address_line_2){{ $addr->address_line_2 }}<br>@endif
                        @if ($addr->city || $addr->state_province || $addr->postal_code){{ trim(implode(', ', array_filter([$addr->city, $addr->state_province, $addr->postal_code]))) }}<br>@endif
                        @if ($addr->country){{ $addr->country }}@endif
                    @elseif ($invoice->businessPartner){{ optional($invoice->businessPartner->primaryAddress)->full_address ?? '—' }}@endif
                </div>
            </div>
        </div>
    </div>

    @php
        $originalTotal = $invoice->lines->sum('amount');
        $totalVat = 0; $totalWtax = 0;
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
                <th class="text-center" style="width: 40px;">No</th>
                <th>Item Code</th>
                <th>Part No.</th>
                <th>Description</th>
                <th class="text-right" style="width: 80px;">Qty</th>
                <th class="text-right" style="width: 100px;">Unit Price</th>
                <th class="text-right" style="width: 110px;">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->lines as $num => $l)
            <tr>
                <td class="text-center">{{ $num + 1 }}</td>
                <td>{{ $l->item_code ?? optional($l->inventoryItem)->code ?? '—' }}</td>
                <td>{{ $l->partNumber?->part_number ?? $l->deliveryOrderLine?->partNumber?->part_number ?? '—' }}</td>
                <td>{{ $l->item_name ?? $l->description ?? '—' }}</td>
                <td class="text-right">{{ number_format($l->qty, 2) }}</td>
                <td class="text-right">{{ number_format($l->unit_price, 2) }}</td>
                <td class="text-right">{{ number_format($l->amount, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="6" class="text-right"><strong>Subtotal</strong></td>
                <td class="text-right">{{ number_format($originalTotal, 2) }}</td>
            </tr>
            @if ($totalVat != 0)
            <tr>
                <td colspan="6" class="text-right">Total VAT</td>
                <td class="text-right">{{ number_format($totalVat, 2) }}</td>
            </tr>
            @endif
            @if ($totalWtax != 0)
            <tr>
                <td colspan="6" class="text-right">Total WTax</td>
                <td class="text-right">({{ number_format($totalWtax, 2) }})</td>
            </tr>
            @endif
            <tr>
                <td colspan="6" class="text-right"><strong>Amount Due</strong></td>
                <td class="text-right"><strong>{{ number_format($amountDue, 2) }}</strong></td>
            </tr>
        </tfoot>
    </table>

    <div class="signature-block">
        <div class="signature-box">
            <div class="signature-line"></div>
            <div class="signature-label">Authorized Signatory</div>
        </div>
    </div>

    <div class="footer-note">
        <strong>Payment Terms:</strong> Please pay within {{ $invoice->terms_days ?? 0 }} days from invoice date. Make payment before Due Date to avoid late payment charges.<br><br>
        Thank you for your business.
    </div>
</body>

</html>
