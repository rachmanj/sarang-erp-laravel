<!DOCTYPE html>
<html>

<head>
    <title>Account Statement - {{ $accountStatement->statement_no }}</title>
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

        .header h1 {
            margin: 0 0 5px 0;
            font-size: 20px;
        }

        .header h2 {
            margin: 0;
            font-size: 16px;
            font-weight: normal;
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

        .info-table td {
            border: none;
            padding: 4px 8px 4px 0;
        }

        .info-table td:first-child {
            font-weight: bold;
            width: 140px;
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
            <img src="{{ asset('logo_pt_csj_transparan.jpeg') }}" alt="Logo">
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
        <h1>ACCOUNT STATEMENT</h1>
        <h2>{{ $accountStatement->statement_no }}</h2>
    </div>

    <table class="info-table">
        <tr>
            <td>Statement Type:</td>
            <td>{{ ucfirst(str_replace('_', ' ', $accountStatement->statement_type)) }}</td>
            <td>Account/Partner:</td>
            <td>{{ $accountStatement->display_name }}</td>
        </tr>
        <tr>
            <td>Period:</td>
            <td>{{ $accountStatement->from_date->format('d/m/Y') }} - {{ $accountStatement->to_date->format('d/m/Y') }}</td>
            <td>Created By:</td>
            <td>{{ $accountStatement->creator->name ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td>Opening Balance:</td>
            <td>{{ $accountStatement->opening_balance >= 0 ? '+' : '' }}Rp {{ number_format($accountStatement->opening_balance, 2) }}</td>
            <td>Closing Balance:</td>
            <td>{{ $accountStatement->closing_balance >= 0 ? '+' : '' }}Rp {{ number_format($accountStatement->closing_balance, 2) }}</td>
        </tr>
    </table>

    @if ($accountStatement->lines->count() > 0)
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Reference</th>
                    <th>Description</th>
                    <th>Project</th>
                    <th>Department</th>
                    <th class="text-right">Debit</th>
                    <th class="text-right">Credit</th>
                    <th class="text-right">Balance</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($accountStatement->lines as $line)
                    <tr>
                        <td>{{ $line->transaction_date->format('d/m/Y') }}</td>
                        <td>{{ $line->reference_display }}</td>
                        <td>{{ $line->description }}</td>
                        <td>{{ $line->project->name ?? '-' }}</td>
                        <td>{{ $line->department->name ?? '-' }}</td>
                        <td class="text-right">
                            @if ($line->debit_amount > 0)
                                Rp {{ number_format($line->debit_amount, 2) }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="text-right">
                            @if ($line->credit_amount > 0)
                                Rp {{ number_format($line->credit_amount, 2) }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="text-right">
                            {{ $line->running_balance >= 0 ? '+' : '' }}Rp {{ number_format($line->running_balance, 2) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="font-weight: bold;">
                    <td colspan="5" class="text-right">Totals:</td>
                    <td class="text-right">Rp {{ number_format($accountStatement->total_debits, 2) }}</td>
                    <td class="text-right">Rp {{ number_format($accountStatement->total_credits, 2) }}</td>
                    <td class="text-right">
                        {{ $accountStatement->closing_balance >= 0 ? '+' : '' }}Rp {{ number_format($accountStatement->closing_balance, 2) }}
                    </td>
                </tr>
            </tfoot>
        </table>
    @else
        <p style="text-align: center; color: #666;">No transactions found for the selected period and filters.</p>
    @endif

    <div style="margin-top: 40px; font-size: 11px; color: #666;">
        Printed on {{ now()->format('d/m/Y H:i') }}
    </div>
</body>

</html>
