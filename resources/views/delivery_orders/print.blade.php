<!DOCTYPE html>
<html>

<head>
    <title>Delivery Order - {{ $deliveryOrder->do_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
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

        @media print {
            body {
                margin: 0;
            }

            .no-print {
                display: none;
            }
        }
    </style>
</head>

<body>
    <div class="no-print" style="text-align: center; margin-bottom: 20px;">
        <button onclick="window.print()"
            style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">Print</button>
        <button onclick="window.close()"
            style="padding: 10px 20px; background: #6c757d; color: white; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px;">Close</button>
    </div>

    <div class="header">
        <h1>DELIVERY ORDER</h1>
        <h2>{{ $deliveryOrder->do_number }}</h2>
    </div>

    <table>
        <tr>
            <td><strong>Sales Order:</strong></td>
            <td>{{ $deliveryOrder->salesOrder->order_no }}</td>
            <td><strong>Customer:</strong></td>
            <td>{{ $deliveryOrder->customer->name }}</td>
        </tr>
        <tr>
            <td><strong>Planned Delivery:</strong></td>
            <td>{{ $deliveryOrder->planned_delivery_date->format('d M Y') }}</td>
            <td><strong>Delivery Method:</strong></td>
            <td>{{ ucfirst(str_replace('_', ' ', $deliveryOrder->delivery_method)) }}</td>
        </tr>
        <tr>
            <td><strong>Delivery Address:</strong></td>
            <td colspan="3">{{ $deliveryOrder->delivery_address }}</td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th>Item Code</th>
                <th>Item Name</th>
                <th class="text-right">Ordered Qty</th>
                <th class="text-right">Unit Price</th>
                <th class="text-right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($deliveryOrder->lines as $line)
                <tr>
                    <td>{{ $line->item_code ?? 'N/A' }}</td>
                    <td>{{ $line->item_name ?? 'N/A' }}</td>
                    <td class="text-right">{{ number_format($line->ordered_qty, 2) }}</td>
                    <td class="text-right">{{ number_format($line->unit_price, 2) }}</td>
                    <td class="text-right">{{ number_format($line->amount, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="4" class="text-right">Total Amount:</th>
                <th class="text-right">{{ number_format($deliveryOrder->total_amount, 2) }}</th>
            </tr>
        </tfoot>
    </table>

    <div style="margin-top: 50px;">
        <table>
            <tr>
                <td style="width: 50%;">
                    <strong>Prepared by:</strong><br><br><br>
                    _________________________<br>
                    {{ $deliveryOrder->createdBy->name ?? 'N/A' }}<br>
                    Date: {{ $deliveryOrder->created_at->format('d M Y') }}
                </td>
                <td style="width: 50%;">
                    <strong>Received by:</strong><br><br><br>
                    _________________________<br>
                    Customer Signature<br>
                    Date: _______________
                </td>
            </tr>
        </table>
    </div>
</body>

</html>
