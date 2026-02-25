<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Purchase Invoice #{{ $invoice->id }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
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

        .document-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 6px;
        }

        th {
            background: #f5f5f5;
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
            $companyLogo = \App\Models\ErpParameter::get('company_logo_path', '');
        @endphp

        @if ($companyLogo && file_exists(public_path('storage/' . $companyLogo)))
            <div class="company-logo">
                <img src="{{ public_path('storage/' . $companyLogo) }}" alt="Logo" style="height: 60px;">
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
                    Tax Number: {{ $companyTaxNumber }}
                @endif
            </div>
        </div>
    </div>

    <div class="document-header">
        <div>
            <h3>Purchase Invoice #{{ $invoice->id }}</h3>
            <div>Date: {{ $invoice->date }}</div>
        </div>
        <div>
            <div>Vendor: {{ optional($invoice->businessPartner)->name ?? '—' }}</div>
        </div>
    </div>
    <table>
        <thead>
            <tr>
                <th>Account</th>
                <th>Description</th>
                <th>Qty</th>
                <th>Unit Price</th>
                <th>Amount</th>
                @if ($invoice->lines->sum('discount_amount') > 0)
                <th>Discount</th>
                <th>Net Amount</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->lines as $l)
                <tr>
                    <td>{{ optional(DB::table('accounts')->find($l->account_id))->code }}</td>
                    <td>{{ $l->description }}</td>
                    <td style="text-align:right">{{ number_format($l->qty, 2) }}</td>
                    <td style="text-align:right">{{ number_format($l->unit_price, 2) }}</td>
                    <td style="text-align:right">{{ number_format($l->amount, 2) }}</td>
                    @if ($invoice->lines->sum('discount_amount') > 0)
                    <td style="text-align:right">
                        @if (($l->discount_amount ?? 0) > 0)
                            - {{ number_format($l->discount_amount, 2) }}
                            @if (($l->discount_percentage ?? 0) > 0)
                                ({{ number_format($l->discount_percentage, 2) }}%)
                            @endif
                        @else
                            0.00
                        @endif
                    </td>
                    <td style="text-align:right">{{ number_format(($l->net_amount ?? $l->amount), 2) }}</td>
                    @endif
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            @if (($invoice->discount_amount ?? 0) > 0)
            <tr>
                <th colspan="{{ $invoice->lines->sum('discount_amount') > 0 ? 6 : 4 }}" style="text-align:right">Discount
                    @if (($invoice->discount_percentage ?? 0) > 0)
                        ({{ number_format($invoice->discount_percentage, 2) }}%)
                    @endif
                </th>
                <th style="text-align:right">- {{ number_format($invoice->discount_amount, 2) }}</th>
                @if ($invoice->lines->sum('discount_amount') > 0)
                <th colspan="2"></th>
                @endif
            </tr>
            @endif
            <tr>
                <th colspan="{{ $invoice->lines->sum('discount_amount') > 0 ? 6 : 4 }}" style="text-align:right">Total</th>
                <th style="text-align:right">{{ number_format($invoice->total_amount, 2) }}</th>
                @if ($invoice->lines->sum('discount_amount') > 0)
                <th colspan="2"></th>
                @endif
            </tr>
        </tfoot>
    </table>
</body>

</html>
