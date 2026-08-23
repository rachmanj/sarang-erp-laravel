<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Prasasta ERP | Pengeluaran Kas</title>

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
        <section class="invoice">
            <div class="row invoice-header">
                <div class="col-12 company-info">
                    @php
                        $companyName = \App\Models\ErpParameter::get('company_name', 'Company Name');
                        $companyAddress = \App\Models\ErpParameter::get('company_address', '');
                        $companyPhone = \App\Models\ErpParameter::get('company_phone', '');
                        $companyEmail = \App\Models\ErpParameter::get('company_email', '');
                        $companyTaxNumber = \App\Models\ErpParameter::get('company_tax_number', '');
                        $companyLogo = \App\Models\ErpParameter::get('company_logo_path', '');
                    @endphp

                    @if ($companyLogo && file_exists(public_path('storage/' . $companyLogo)))
                        <div style="margin-bottom: 10px;">
                            <img src="{{ public_path('storage/' . $companyLogo) }}" alt="Logo"
                                style="height: 60px;">
                        </div>
                    @endif

                    <div class="company-name">{{ $companyName }}</div>
                    <div class="company-address">
                        @if ($companyAddress)
                            {{ $companyAddress }}<br>
                        @endif
                        @if ($companyPhone || $companyEmail)
                            @if ($companyPhone)
                                Telepon: {{ $companyPhone }}
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
                            NPWP: {{ $companyTaxNumber }}
                        @endif
                    </div>
                </div>
            </div>

            <div class="row invoice-info">
                <div class="col-sm-4 invoice-col">
                    <strong>Detail Pengeluaran Kas</strong>
                    <address>
                        <strong>No. Pengeluaran: {{ $cashExpense->expense_no ?? 'CE-' . str_pad($cashExpense->id, 6, '0', STR_PAD_LEFT) }}</strong><br>
                        Tanggal: <b>{{ \Carbon\Carbon::parse($cashExpense->date)->format('d M Y') }}</b><br>
                        Akun Kas/Bank: <b>{{ $cashAccount ? $cashAccount->code . ' - ' . $cashAccount->name : 'N/A' }}</b><br>
                        Status: <b>{{ ucfirst($cashExpense->status) }}</b><br>
                        @if ($cashExpense->creator)
                            Dibuat oleh: <b>{{ $cashExpense->creator->name }}</b><br>
                        @endif
                    </address>
                </div>
                <div class="col-sm-4 text-center">
                    <h3>Bukti Pengeluaran Kas</h3>
                    @if ($cashExpense->description)
                        <p class="mb-0"><em>{{ $cashExpense->description }}</em></p>
                    @endif
                </div>
                <div class="col-sm-4 invoice-col text-right">
                    ID Dokumen: <b>#{{ $cashExpense->id }}</b><br>
                    Tanggal Cetak: <b>{{ now()->format('d M Y H:i') }}</b><br>
                    Total: <b>Rp {{ number_format($cashExpense->total_amount, 2, ',', '.') }}</b><br>
                </div>
            </div>

            <div class="row">
                <div class="col-12 table-responsive">
                    <table class="table table-bordered" style="border: 1px solid black;">
                        <thead>
                            <tr>
                                <th style="border: 1px solid black; width: 5%">No</th>
                                <th style="border: 1px solid black; width: 30%">Akun Biaya</th>
                                <th style="border: 1px solid black;">Keterangan</th>
                                <th class="text-right" style="border: 1px solid black; width: 18%">Nominal (IDR)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($cashExpense->lines as $index => $line)
                                <tr>
                                    <td style="border: 1px solid black;">{{ $index + 1 }}</td>
                                    <td style="border: 1px solid black;">
                                        {{ $line->account->code }} - {{ $line->account->name }}
                                        @if ($line->project)
                                            <br><small>Project: {{ $line->project->code }} - {{ $line->project->name }}</small>
                                        @endif
                                        @if ($line->department)
                                            <br><small>Dept: {{ $line->department->code }} - {{ $line->department->name }}</small>
                                        @endif
                                    </td>
                                    <td style="border: 1px solid black;">
                                        {{ $line->description ?: '-' }}
                                    </td>
                                    <td class="text-right" style="border: 1px solid black;">
                                        {{ number_format($line->amount, 2, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3" class="text-right" style="border: 1px solid black;">TOTAL</th>
                                <th class="text-right" style="border: 1px solid black;">
                                    {{ number_format($cashExpense->total_amount, 2, ',', '.') }}
                                </th>
                            </tr>
                            <tr>
                                <th class="text-right" style="border: 1px solid black;">Terbilang</th>
                                <td colspan="3" style="border: 1px solid black;">{{ ucfirst($terbilang) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-12">
                    <h5>Jurnal:</h5>
                    <table class="table table-bordered table-sm">
                        <thead>
                            <tr>
                                <th>Akun</th>
                                <th class="text-right">Debit</th>
                                <th class="text-right">Kredit</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($cashExpense->lines as $line)
                                <tr>
                                    <td>{{ $line->account->code }} - {{ $line->account->name }}</td>
                                    <td class="text-right">{{ number_format($line->amount, 2, ',', '.') }}</td>
                                    <td class="text-right">-</td>
                                </tr>
                            @endforeach
                            <tr>
                                <td>{{ $cashAccount ? $cashAccount->code . ' - ' . $cashAccount->name : 'N/A' }}</td>
                                <td class="text-right">-</td>
                                <td class="text-right">{{ number_format($cashExpense->total_amount, 2, ',', '.') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="row invoice-info mt-4">
                <div class="col-sm-3 invoice-col">
                    <b>Dibuat oleh</b><br>
                    <br>
                    <br>
                    <br>
                    <br>
                    <br>
                    {{ $cashExpense->creator->name ?? 'Admin' }}<br>
                </div>

                <div class="col-sm-3 invoice-col">
                    <b>Disetujui oleh</b><br>
                    <br>
                    <br>
                    <br>
                    <br>
                    <br>
                    (....................................)<br>
                </div>

                <div class="col-sm-3 invoice-col">
                    <b>Diterima oleh</b><br>
                    <br>
                    <br>
                    <br>
                    <br>
                    <br>
                    (....................................)<br>
                </div>

                <div class="col-sm-3 invoice-col">
                    <b>Kasir</b><br>
                    <br>
                    <br>
                    <br>
                    <br>
                    <br>
                    (....................................)<br>
                </div>
            </div>
        </section>
    </div>

    <button class="print-button no-print" onclick="window.print()" title="Cetak Dokumen">
        <i class="fas fa-print"></i>
    </button>
</body>

</html>
