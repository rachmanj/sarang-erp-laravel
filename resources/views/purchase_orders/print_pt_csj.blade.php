<!DOCTYPE html>
<html>

<head>
    <title>Purchase Order - {{ $order->order_no ?? '#' . $order->id }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            padding-bottom: 60px;
            font-size: 11px;
        }

        .company-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 2px solid #333;
        }

        .company-left {
            display: flex;
            gap: 16px;
            align-items: center;
        }

        .company-logo img {
            height: 56px;
        }

        .company-name {
            font-size: 16px;
            font-weight: bold;
            color: #0066cc;
        }

        .company-legal {
            font-size: 14px;
            font-weight: bold;
            margin-top: 2px;
        }

        .company-tagline {
            font-size: 10px;
            color: #666;
            margin-top: 2px;
        }

        .company-offices {
            font-size: 10px;
            line-height: 1.5;
            text-align: right;
        }

        .company-offices strong {
            display: block;
            margin-top: 4px;
        }

        .title-block {
            background: #d0d0d0;
            padding: 10px 24px;
            text-align: center;
            margin-bottom: 8px;
        }

        .title-block h1 {
            margin: 0;
            font-size: 18px;
            color: #333;
        }

        .po-no {
            text-align: center;
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 16px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            font-size: 10px;
        }

        th,
        td {
            border: 1px solid #333;
            padding: 6px 8px;
        }

        th {
            background: #e8e8e8;
            font-weight: bold;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .info-table td {
            border: 1px solid #333;
        }

        .order-ship-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 16px;
        }

        .order-box,
        .ship-box {
            border: 1px solid #333;
        }

        .order-box h3,
        .ship-box h3 {
            margin: 0;
            background: #d0d0d0;
            padding: 6px 10px;
            font-size: 11px;
        }

        .order-box .content,
        .ship-box .content {
            padding: 10px;
            font-size: 10px;
            line-height: 1.6;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-top: 24px;
        }

        .footer-left .notes {
            margin-bottom: 12px;
            font-size: 10px;
        }

        .footer-left .say {
            margin: 12px 0;
            font-style: italic;
        }

        .footer-left .vendor-confirm {
            margin-top: 24px;
        }

        .footer-right .totals {
            width: 100%;
            max-width: 280px;
            margin-left: auto;
        }

        .footer-right .totals td {
            border: none;
            padding: 4px 8px;
        }

        .footer-right .totals .label {
            text-align: right;
        }

        .signature-row {
            display: flex;
            gap: 48px;
            margin-top: 32px;
            justify-content: flex-end;
        }

        .signature-box {
            text-align: center;
            width: 140px;
        }

        .signature-line {
            border-bottom: 1px solid #333;
            height: 36px;
            margin-bottom: 4px;
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
            font-size: 16px;
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

    @php
        $entity = $entity ?? \App\Models\CompanyEntity::where('name', 'PT Cahaya Sarange Jaya')->first();
        $companyName = $entity?->name ?? 'PT Cahaya Sarange Jaya';
        $officeBalikpapan = \App\Models\ErpParameter::get(
            'pt_csj_office_balikpapan',
            'Mal Fantasi Balikpapan Baru, Ruko Puri Blok A35, Balikpapan Baru, Kal-Tim 76114',
        );
        $officeJakarta = \App\Models\ErpParameter::get(
            'pt_csj_office_jakarta',
            'Jl. Raya Cilandak KKO, Komplek Vico Kav.12, Jakarta Selatan, 12560',
        );
        $tagline = \App\Models\ErpParameter::get('pt_csj_tagline', 'General Supplier');
        $logoPath = $entity?->logo_path ?? 'logo_pt_csj.png';
        $vendorAddress =
            optional($order->businessPartner)->primaryAddress?->full_address ??
            (optional($order->businessPartner)->addresses->first()?->full_address ?? '-');
        $subtotalBeforeHeader = $order->lines->sum('amount');
        $totalLineDiscount = $order->lines->sum('discount_amount');
        $headerDiscount = $order->discount_amount ?? 0;
        $vatAmount = $order->lines->sum(fn($l) => ($l->net_amount ?? 0) * (($l->vat_rate ?? 0) / 100));
        $dpp = $order->lines->sum('net_amount') - $headerDiscount;
    @endphp

    <div class="company-header">
        <div class="company-left">
            @if (file_exists(public_path($logoPath)))
                <div class="company-logo">
                    <img src="{{ asset($logoPath) }}" alt="Logo">
                </div>
            @endif
            <div>
                {{-- <div class="company-name">Cahaya Sarange Jaya</div> --}}
                <div class="company-legal">{{ $companyName }}</div>
                <div class="company-tagline">{{ $tagline }}</div>
            </div>
        </div>
        <div class="company-offices">
            <strong>Office Balikpapan:</strong>
            {{ $officeBalikpapan }}<br>
            <strong>Office Jakarta:</strong>
            {{ $officeJakarta }}
        </div>
    </div>

    <div class="title-block">
        <h1>PURCHASE ORDER</h1>
    </div>
    <div class="po-no">No : {{ $order->order_no ?? '#' . $order->id }}</div>

    <table class="info-table">
        <tr>
            <td style="width:25%"><strong>Date :</strong></td>
            <td style="width:25%">{{ $order->date ? $order->date->format('d F Y') : '-' }}</td>
            <td style="width:25%"><strong>Curr :</strong></td>
            <td style="width:25%">{{ $order->currency?->code ?? 'IDR' }}</td>
        </tr>
        <tr>
            <td><strong>Refer :</strong></td>
            <td>{{ $order->reference_no ?? '' }}</td>
            <td><strong>Payment Terms :</strong></td>
            <td>{{ $order->payment_terms ?? 'Cash' }}</td>
        </tr>
        <tr>
            <td><strong>Delivery Date :</strong></td>
            <td colspan="3">
                {{ $order->expected_delivery_date ? $order->expected_delivery_date->format('d F Y') : '' }}</td>
        </tr>
    </table>

    <div class="order-ship-grid">
        <div class="order-box">
            <h3>ORDER TO :</h3>
            <div class="content">
                <strong>Vendor :</strong> {{ $order->businessPartner->name ?? 'N/A' }}<br>
                <strong>Address :</strong> {{ $vendorAddress }}<br>
                <strong>Contact :</strong><br>
                <strong>Attn :</strong>
            </div>
        </div>
        <div class="ship-box">
            <h3>SHIP TO :</h3>
            <div class="content">
                <strong>{{ $companyName }}</strong><br>
                @if ($order->warehouse)
                    {{ $order->warehouse->name }}<br>
                    {{ $order->warehouse->address ?? '' }}<br>
                    <strong>Contact :</strong> {{ $order->warehouse->phone ?? '' }}<br>
                    <strong>Attn :</strong> {{ $order->warehouse->contact_person ?? '' }}
                @else
                    {{ $officeBalikpapan }}<br>
                    <strong>Contact :</strong> {{ $entity?->phone ?? '' }}<br>
                    <strong>Attn :</strong> {{ $order->createdBy->name ?? '' }}
                @endif
            </div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th class="text-center" style="width:5%">NO</th>
                <th style="width:10%">ITEM CODE</th>
                <th style="width:10%">PART NO.</th>
                <th style="width:33%">ITEM DESCRIPTION</th>
                <th class="text-center" style="width:8%">UOM</th>
                <th class="text-right" style="width:10%">QTY</th>
                <th class="text-right" style="width:12%">UNIT PRICE</th>
                <th class="text-right" style="width:15%">TOTAL PRICE</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->lines as $index => $line)
                @php
                    $uom = $line->orderUnit?->code ?? ($line->inventoryItem?->unit_of_measure ?? '-');
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $line->inventoryItem?->code ?? ($line->item_code ?? '-') }}</td>
                    <td>{{ $line->partNumber?->part_number ?? '-' }}</td>
                    <td>{{ $line->inventoryItem?->name ?? ($line->item_name ?? ($line->description ?? '-')) }}</td>
                    <td class="text-center">{{ $uom }}</td>
                    <td class="text-right">{{ number_format($line->qty, 2, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($line->unit_price, 2, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($line->amount, 2, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer-grid">
        <div class="footer-left">
            <div class="notes">
                <strong>IMPORTANT NOTES:</strong><br>
                • {{ $order->notes ? Str::limit($order->notes, 80) : '' }}<br>
                • {{ $order->terms_conditions ? Str::limit($order->terms_conditions, 80) : '' }}
            </div>
            @if ($terbilang ?? null)
                <div class="say">
                    <strong>Say :</strong> {{ ucfirst($terbilang) }}
                </div>
            @endif
            <div class="vendor-confirm">
                <strong>Vendor Confirmation</strong><br>
                _________________________________________
            </div>
        </div>
        <div class="footer-right">
            <table class="totals">
                <tr>
                    <td class="label">Total</td>
                    <td class="text-right">Rp {{ number_format($subtotalBeforeHeader, 2, ',', '.') }}</td>
                </tr>
                @if ($totalLineDiscount > 0 || $headerDiscount > 0)
                    <tr>
                        <td class="label">Line Discounts</td>
                        <td class="text-right">Rp {{ number_format($totalLineDiscount, 2, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="label">Header Discount</td>
                        <td class="text-right">Rp {{ number_format($headerDiscount, 2, ',', '.') }}</td>
                    </tr>
                @endif
                <tr>
                    <td class="label">DPP (11/12)</td>
                    <td class="text-right">Rp {{ number_format($dpp, 2, ',', '.') }}</td>
                </tr>
                <tr>
                    <td class="label">Tax 12%</td>
                    <td class="text-right">Rp {{ number_format($vatAmount, 2, ',', '.') }}</td>
                </tr>
                <tr>
                    <td class="label">Total After Tax</td>
                    <td class="text-right">Rp {{ number_format($order->total_amount, 2, ',', '.') }}</td>
                </tr>
                <tr>
                    <td class="label"><strong>Grand Total</strong></td>
                    <td class="text-right"><strong>Rp {{ number_format($order->total_amount, 2, ',', '.') }}</strong>
                    </td>
                </tr>
            </table>
            <div class="signature-row">
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div>Prepared by</div>
                    <div>{{ $order->createdBy->name ?? 'N/A' }}</div>
                </div>
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div>Approved by</div>
                    <div>{{ $order->approvedBy?->name ?? '' }}</div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
