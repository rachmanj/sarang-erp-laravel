<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Prasasta ERP | Cash Expense</title>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/fontawesome-free/css/all.min.css') }}">

    <!-- Theme style -->
    <link rel="stylesheet" href="{{ asset('adminlte/dist/css/adminlte.min.css') }}">

    <style>
        @media print {
            .no-print {
                display: none !important;
            }
        }

        .invoice {
            background: #fff;
            border: 1px solid rgba(0, 0, 0, .125);
            border-radius: 0.25rem;
            position: relative;
            width: 100%;
        }

        .invoice-header {
            border-bottom: 1px solid rgba(0, 0, 0, .125);
            padding-bottom: 1rem;
            margin-bottom: 1rem;
        }

        .company-info {
            text-align: center;
            margin-bottom: 1rem;
        }

        .company-name {
            font-size: 1.5rem;
            font-weight: bold;
            color: #333;
        }

        .company-address {
            color: #666;
            font-size: 0.9rem;
        }

        .print-button {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            font-size: 24px;
            cursor: pointer;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
        }

        .print-button:hover {
            background: #0056b3;
            transform: scale(1.1);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.4);
        }

        .print-button:active {
            transform: scale(0.95);
        }

        @media print {
            .print-button {
                display: none !important;
            }
        }
    </style>
</head>

<body>
    <div class="wrapper mx-4">
        <!-- Main content -->
        <section class="invoice">
            <!-- title row -->
            <div class="row invoice-header">
                <div class="col-12 company-info">
                    <div class="company-name">Prasasta ERP</div>
                    <div class="company-address">Enterprise Resource Planning System</div>
                </div>
            </div>

            <!-- info row -->
            <div class="row invoice-info">
                <div class="col-sm-4 invoice-col">
                    <strong>Cash Expense Details</strong>
                    <address>
                        <strong>Expense ID: #{{ $cashExpense->id }}</strong><br>
                        Date: <b>{{ \Carbon\Carbon::parse($cashExpense->date)->format('d M Y') }}</b><br>
                        Status: <b>{{ ucfirst($cashExpense->status) }}</b><br>
                        @if ($cashExpense->creator)
                            Created by: <b>{{ $cashExpense->creator->name }}</b><br>
                        @endif
                    </address>
                </div>
                <div class="col-sm-4 text-center">
                    <h3>Cash Expense Voucher</h3>
                </div>
                <div class="col-sm-4 invoice-col text-right">
                    Document No: <b>CE-{{ str_pad($cashExpense->id, 6, '0', STR_PAD_LEFT) }}</b><br>
                    Print Date: <b>{{ now()->format('d M Y H:i') }}</b><br>
                    Amount: <b>Rp {{ number_format($cashExpense->amount, 2, ',', '.') }}</b><br>
                </div>
            </div>
            <!-- /.row -->

            <!-- Table row -->
            <div class="row">
                <div class="col-12 table-responsive">
                    <table class="table table-bordered" style="border: 1px solid black;">
                        <thead>
                            <tr>
                                <th style="border: 1px solid black;">No</th>
                                <th style="border: 1px solid black;">Description</th>
                                <th style="border: 1px solid black;">Account</th>
                                <th class="text-right" style="border: 1px solid black;">Amount (IDR)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="border: 1px solid black;">1</td>
                                <td style="border: 1px solid black;">
                                    {{ $cashExpense->description ?: 'Cash Expense' }}
                                    @if ($cashExpense->project)
                                        <br><small>Project: {{ $cashExpense->project->code }} -
                                            {{ $cashExpense->project->name }}</small>
                                    @endif
                                    @if ($cashExpense->fund)
                                        <br><small>Fund: {{ $cashExpense->fund->code }} -
                                            {{ $cashExpense->fund->name }}</small>
                                    @endif
                                    @if ($cashExpense->department)
                                        <br><small>Department: {{ $cashExpense->department->code }} -
                                            {{ $cashExpense->department->name }}</small>
                                    @endif
                                </td>
                                <td style="border: 1px solid black;">
                                    <strong>Expense Account:</strong><br>
                                    {{ $cashExpense->expenseAccount->code }} -
                                    {{ $cashExpense->expenseAccount->name }}<br><br>
                                    <strong>Cash Account:</strong><br>
                                    {{ $cashAccount ? $cashAccount->code . ' - ' . $cashAccount->name : 'N/A' }}
                                </td>
                                <td class="text-right" style="border: 1px solid black;">
                                    {{ number_format($cashExpense->amount, 2, ',', '.') }}
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3" class="text-right" style="border: 1px solid black;">TOTAL</th>
                                <th class="text-right" style="border: 1px solid black;">
                                    {{ number_format($cashExpense->amount, 2, ',', '.') }}
                                </th>
                            </tr>
                            <tr>
                                <th class="text-right" style="border: 1px solid black;">Say</th>
                                <td colspan="3" style="border: 1px solid black;">{{ ucfirst($terbilang) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <!-- /.row -->

            <!-- Journal Entry Details -->
            <div class="row mt-3">
                <div class="col-12">
                    <h5>Journal Entry:</h5>
                    <table class="table table-bordered table-sm">
                        <thead>
                            <tr>
                                <th>Account</th>
                                <th class="text-right">Debit</th>
                                <th class="text-right">Credit</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>{{ $cashExpense->expenseAccount->code }} -
                                    {{ $cashExpense->expenseAccount->name }}</td>
                                <td class="text-right">{{ number_format($cashExpense->amount, 2, ',', '.') }}</td>
                                <td class="text-right">-</td>
                            </tr>
                            <tr>
                                <td>{{ $cashAccount ? $cashAccount->code . ' - ' . $cashAccount->name : 'N/A' }}</td>
                                <td class="text-right">-</td>
                                <td class="text-right">{{ number_format($cashExpense->amount, 2, ',', '.') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Signature row -->
            <div class="row invoice-info mt-4">
                <div class="col-sm-3 invoice-col">
                    <b>Prepared by</b><br>
                    <br>
                    <br>
                    <br>
                    <br>
                    <br>
                    {{ $cashExpense->creator->name ?? 'Admin' }}<br>
                </div>

                <div class="col-sm-3 invoice-col">
                    <b>Approved by</b><br>
                    <br>
                    <br>
                    <br>
                    <br>
                    <br>
                    (....................................)<br>
                </div>

                <div class="col-sm-3 invoice-col">
                    <b>Received by</b><br>
                    <br>
                    <br>
                    <br>
                    <br>
                    <br>
                    (....................................)<br>
                </div>

                <div class="col-sm-3 invoice-col">
                    <b>Cashier</b><br>
                    <br>
                    <br>
                    <br>
                    <br>
                    <br>
                    (....................................)<br>
                </div>
            </div>
            <!-- /.row -->
        </section>
        <!-- /.content -->
    </div>
    <!-- ./wrapper -->

    <!-- Floating Print Button -->
    <button class="print-button no-print" onclick="window.print()" title="Print Document">
        <i class="fas fa-print"></i>
    </button>

    <!-- Page specific script -->
    <script>
        // Commented out automatic print - user must click the print button
        // window.addEventListener("load", function() {
        //     window.print();
        // });
    </script>
</body>

</html>
