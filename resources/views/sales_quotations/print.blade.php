<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Sales Quotation {{ $salesQuotation->quotation_no }}</title>
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

        .quotation-info {
            margin-bottom: 20px;
        }

        .quotation-info table {
            width: 100%;
            border-collapse: collapse;
        }

        .quotation-info td {
            padding: 4px 8px;
            border: none;
        }

        .quotation-info td:first-child {
            font-weight: bold;
            width: 150px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 6px;
        }

        th {
            background: #f5f5f5;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ccc;
        }

        .terms-conditions {
            margin-top: 20px;
            font-size: 11px;
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
            <h2>SALES QUOTATION</h2>
            <div><strong>Quotation No:</strong> {{ $salesQuotation->quotation_no }}</div>
            <div><strong>Date:</strong> {{ $salesQuotation->date->format('d-M-Y') }}</div>
            @if($salesQuotation->valid_until_date)
                <div><strong>Valid Until:</strong> {{ $salesQuotation->valid_until_date->format('d-M-Y') }}</div>
            @endif
            @if($salesQuotation->reference_no)
                <div><strong>Reference:</strong> {{ $salesQuotation->reference_no }}</div>
            @endif
        </div>
        <div>
            <div><strong>Customer:</strong></div>
            <div>{{ $salesQuotation->businessPartner->name ?? 'N/A' }}</div>
            @if($salesQuotation->businessPartner)
                @php
                    $bpAddress = $salesQuotation->businessPartner->getDetailBySection('address', 'street')?->field_value ?? '';
                    $bpCity = $salesQuotation->businessPartner->getDetailBySection('address', 'city')?->field_value ?? '';
                    $bpPhone = $salesQuotation->businessPartner->getDetailBySection('contact', 'phone')?->field_value ?? '';
                @endphp
                @if($bpAddress)
                    <div>{{ $bpAddress }}</div>
                @endif
                @if($bpCity)
                    <div>{{ $bpCity }}</div>
                @endif
                @if($bpPhone)
                    <div>Phone: {{ $bpPhone }}</div>
                @endif
            @endif
        </div>
    </div>

    @if($salesQuotation->description)
    <div style="margin-bottom: 15px;">
        <strong>Description:</strong> {{ $salesQuotation->description }}
    </div>
    @endif

    <table>
        <thead>
            <tr>
                <th>Item/Account</th>
                <th>Description</th>
                <th class="text-center">Qty</th>
                <th class="text-right">Unit Price</th>
                <th class="text-right">VAT</th>
                <th class="text-right">WTax</th>
                <th class="text-right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($salesQuotation->lines as $l)
                <tr>
                    <td>
                        @if ($l->inventory_item_id && $l->inventoryItem)
                            <strong>{{ $l->inventoryItem->code }}</strong><br>
                            <small>{{ $l->inventoryItem->name }}</small>
                        @elseif($l->item_code)
                            <strong>{{ $l->item_code }}</strong><br>
                            <small>{{ $l->item_name }}</small>
                        @elseif($l->account_id && $l->account)
                            <strong>{{ $l->account->code }}</strong><br>
                            <small>{{ $l->account->name }}</small>
                        @else
                            #{{ $l->account_id }}
                        @endif
                    </td>
                    <td>{{ $l->description }}</td>
                    <td class="text-center">{{ number_format($l->qty, 2) }}</td>
                    <td class="text-right">Rp {{ number_format($l->unit_price, 0, ',', '.') }}</td>
                    <td class="text-right">{{ $l->vat_rate ?? 0 }}%</td>
                    <td class="text-right">{{ $l->wtax_rate ?? 0 }}%</td>
                    <td class="text-right">Rp {{ number_format($l->amount, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="6" class="text-right">Subtotal:</th>
                <th class="text-right">Rp {{ number_format($salesQuotation->total_amount, 0, ',', '.') }}</th>
            </tr>
            @if($salesQuotation->discount_amount > 0)
            <tr>
                <th colspan="6" class="text-right">Discount 
                    @if($salesQuotation->discount_percentage > 0)
                        ({{ number_format($salesQuotation->discount_percentage, 2) }}%)
                    @endif
                    :
                </th>
                <th class="text-right">- Rp {{ number_format($salesQuotation->discount_amount, 0, ',', '.') }}</th>
            </tr>
            @endif
            <tr>
                <th colspan="6" class="text-right">Total:</th>
                <th class="text-right">Rp {{ number_format($salesQuotation->net_amount, 0, ',', '.') }}</th>
            </tr>
        </tfoot>
    </table>

    @if($salesQuotation->terms_conditions)
    <div class="terms-conditions">
        <h4>Terms & Conditions:</h4>
        <p>{!! nl2br(e($salesQuotation->terms_conditions)) !!}</p>
    </div>
    @endif

    @if($salesQuotation->notes)
    <div class="terms-conditions">
        <h4>Notes:</h4>
        <p>{!! nl2br(e($salesQuotation->notes)) !!}</p>
    </div>
    @endif

    <div class="footer">
        <div style="margin-top: 40px;">
            <div style="float: left; width: 50%;">
                <div style="border-top: 1px solid #333; padding-top: 5px; width: 200px;">
                    <div style="text-align: center;">Prepared By</div>
                </div>
            </div>
            <div style="float: right; width: 50%;">
                <div style="border-top: 1px solid #333; padding-top: 5px; width: 200px; margin-left: auto;">
                    <div style="text-align: center;">Approved By</div>
                </div>
            </div>
            <div style="clear: both;"></div>
        </div>
    </div>
</body>

</html>
