@extends('layouts.main')

@section('title_page')
    PPN Reconciliation
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">PPN Reconciliation</li>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">{{ $report_title ?? 'PPN Masukan / Keluaran Reconciliation' }}</h3>
                    <a href="{{ route('reports.ppn-reconciliation', ['from' => $from, 'to' => $to, 'export' => 'json']) }}"
                        class="btn btn-sm btn-outline-primary">Export SPT 1111 JSON</a>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-3">Period: {{ $from }} to {{ $to }}</p>
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <th>PPN Keluaran</th>
                                <td class="text-right">{{ number_format($ppn_keluaran ?? 0, 2, '.', ',') }}</td>
                            </tr>
                            <tr>
                                <th>PPN Masukan</th>
                                <td class="text-right">{{ number_format($ppn_masukan ?? 0, 2, '.', ',') }}</td>
                            </tr>
                            <tr>
                                <th>Net PPN Payable (Kurang/Lebih Bayar)</th>
                                <td class="text-right font-weight-bold">{{ number_format($net_payable ?? 0, 2, '.', ',') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
@endsection
