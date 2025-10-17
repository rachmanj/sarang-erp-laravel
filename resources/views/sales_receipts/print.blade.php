<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Sales Receipt #{{ $receipt->id }}</title>
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
            <h3>Sales Receipt #{{ $receipt->id }}</h3>
            <div>Date: {{ $receipt->date }}</div>
        </div>
        <div>
            <div>Customer: {{ optional(DB::table('customers')->find($receipt->customer_id))->name }}</div>
        </div>
    </div>
    <table>
        <thead>
            <tr>
                <th>Account</th>
                <th>Description</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($receipt->lines as $l)
                <tr>
                    <td>{{ optional(DB::table('accounts')->find($l->account_id))->code }}</td>
                    <td>{{ $l->description }}</td>
                    <td style="text-align:right">{{ number_format($l->amount, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="2" style="text-align:right">Total</th>
                <th style="text-align:right">{{ number_format($receipt->total_amount, 2) }}</th>
            </tr>
        </tfoot>
    </table>
</body>

</html>
