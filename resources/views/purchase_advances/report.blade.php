@extends('layouts.main')

@section('title_page')
    Laporan Uang Muka Pembelian
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('purchase-advances.index') }}">Uang Muka Pembelian</a></li>
    <li class="breadcrumb-item active">Laporan</li>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">Laporan Uang Muka Pembelian per Supplier</h3>
                    <a href="{{ route('purchase-advances.index') }}" class="btn btn-sm btn-secondary">Daftar Uang Muka</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>Supplier</th>
                                    <th class="text-right">Total DP Posted</th>
                                    <th class="text-right">Terpakai</th>
                                    <th class="text-right">Sisa</th>
                                    <th class="text-right">0–30 hari</th>
                                    <th class="text-right">31–60 hari</th>
                                    <th class="text-right">61–90 hari</th>
                                    <th class="text-right">&gt; 90 hari</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($supplierSummary as $row)
                                    <tr>
                                        <td>{{ $row['vendor_name'] }}</td>
                                        <td class="text-right">{{ number_format($row['total_posted'], 2) }}</td>
                                        <td class="text-right">{{ number_format($row['total_used'], 2) }}</td>
                                        <td class="text-right"><strong>{{ number_format($row['remaining'], 2) }}</strong></td>
                                        <td class="text-right">{{ number_format($row['aging']['0_30'], 2) }}</td>
                                        <td class="text-right">{{ number_format($row['aging']['31_60'], 2) }}</td>
                                        <td class="text-right">{{ number_format($row['aging']['61_90'], 2) }}</td>
                                        <td class="text-right">{{ number_format($row['aging']['over_90'], 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">Tidak ada uang muka posted.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <h5 class="mt-4 mb-2">Rincian Pemakaian per Faktur</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead>
                                <tr>
                                    <th>Supplier</th>
                                    <th>No. Uang Muka</th>
                                    <th>No. Faktur</th>
                                    <th class="text-right">Jumlah</th>
                                    <th>Tanggal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($applications as $app)
                                    <tr>
                                        <td>{{ $app->vendor_name }}</td>
                                        <td>{{ $app->advance_no }}</td>
                                        <td>{{ $app->invoice_no }}</td>
                                        <td class="text-right">{{ number_format((float) $app->amount, 2) }}</td>
                                        <td>{{ \Carbon\Carbon::parse($app->application_date)->format('d M Y') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">Belum ada pemakaian uang muka.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection