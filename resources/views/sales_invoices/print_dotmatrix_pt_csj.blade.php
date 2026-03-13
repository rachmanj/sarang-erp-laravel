<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>SI {{ $invoice->invoice_no ?? '#' . $invoice->id }}</title>
    <style>
        @page { size: 9.5in; margin: 0.25in; }
        * { box-sizing: border-box; }
        body { font-family: 'Courier New', Courier, monospace; font-size: 11px; line-height: 1.2; margin: 0; padding: 8px; max-width: 9.5in; width: 100%; }
        .company-header { text-align: center; margin-bottom: 8px; padding-bottom: 4px; border-bottom: 1px solid #000; }
        .company-header .logo { max-height: 48px; margin-bottom: 4px; }
        .company-name { font-size: 12px; font-weight: bold; margin-bottom: 2px; }
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
        .totals { margin-top: 8px; width: 100%; }
        .totals-row { display: flex; justify-content: space-between; padding: 2px 0; }
        .totals-row.total-due { font-weight: bold; border-top: 1px solid #000; margin-top: 4px; padding-top: 4px; }
        .signature-row { margin-top: 24px; font-size: 10px; }
        .signature-row td { border: none; padding: 8px 4px; vertical-align: top; }
        .print-btn { position: fixed; bottom: 16px; right: 16px; padding: 8px 16px; background: #333; color: white; border: none; cursor: pointer; font-size: 12px; }
        @media print { body { padding: 0; } .no-print { display: none !important; } }
    </style>
</head>

<body>
    <button class="no-print print-btn" onclick="window.print()">Print</button>

    @php
        $entity = $entity ?? \App\Models\CompanyEntity::where('name', 'PT Cahaya Sarange Jaya')->first();
        $companyName = $entity?->name ?? 'PT Cahaya Sarange Jaya';
        $companyAddress = $entity?->address ?? \App\Models\ErpParameter::get('company_address', '');
        $companyPhone = $entity?->phone ?? \App\Models\ErpParameter::get('company_phone', '');
        $companyEmail = $entity?->email ?? \App\Models\ErpParameter::get('company_email', '');
    @endphp

    <div class="company-header">
        @if (file_exists(public_path('logo_pt_csj_transparan.jpeg')))
        <img src="{{ asset('logo_pt_csj_transparan.jpeg') }}" alt="Logo" class="logo">
        @endif
        <div class="company-name">{{ $companyName }}</div>
        <div class="company-details">{{ $companyAddress }}@if ($companyPhone || $companyEmail) | {{ $companyPhone }}{{ $companyPhone && $companyEmail ? ' | ' : '' }}{{ $companyEmail }}@endif</div>
    </div>

    <div class="header">
        <h1>SALES INVOICE</h1>
        <div>{{ $invoice->invoice_no ?? '#' . $invoice->id }}</div>
    </div>

    <table class="info-table">
        <tr>
            <td class="label">Date:</td>
            <td>{{ $invoice->date ? $invoice->date->format('d M Y') : '—' }}</td>
            <td class="label">Due:</td>
            <td>{{ $invoice->due_date ? $invoice->due_date->format('d M Y') : '—' }}</td>
        </tr>
        <tr>
            <td class="label">Bill To:</td>
            <td colspan="3">{{ optional($invoice->businessPartner)->name ?? '—' }}</td>
        </tr>
        @if ($invoice->businessPartnerProject)
        <tr>
            <td class="label">Project:</td>
            <td colspan="3">{{ $invoice->businessPartnerProject->display_name }}</td>
        </tr>
        @endif
        @if ($invoice->reference_no)
        <tr>
            <td class="label">Ref:</td>
            <td colspan="3">{{ $invoice->reference_no }}</td>
        </tr>
        @endif
    </table>

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
                <th class="text-center" style="width:5%">No</th>
                <th style="width:12%">Code</th>
                <th style="width:10%">Part No.</th>
                <th style="width:38%">Description</th>
                <th class="text-right" style="width:10%">Qty</th>
                <th class="text-right" style="width:12%">Price</th>
                <th class="text-right" style="width:13%">Amount</th>
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

    <table class="info-table signature-row">
        <tr>
            <td style="width: 33%;">Prepared: _________________________<br>{{ $invoice->created_at->format('d M Y') }}</td>
            <td style="width: 34%;">Authorized: _________________________<br>Date: _______________</td>
            <td style="width: 33%;">Received: _________________________<br>Customer | Date: _______________</td>
        </tr>
    </table>
</body>

</html>
