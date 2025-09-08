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

        .header {
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
    <div class="header">
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
