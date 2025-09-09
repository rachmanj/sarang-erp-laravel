@extends('layouts.app')
@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">AP Party Balances</h3>
                    <div>
                        <a class="btn btn-sm btn-outline-secondary"
                            href="{{ route('reports.ap-balances', ['export' => 'csv']) }}">CSV</a>
                        <a class="btn btn-sm btn-outline-secondary"
                            href="{{ route('reports.ap-balances', ['export' => 'pdf']) }}">PDF</a>
                    </div>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Vendor</th>
                                <th class="text-right">Invoices</th>
                                <th class="text-right">Payments</th>
                                <th class="text-right">Balance</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows ?? [] as $r)
                                <tr>
                                    <td>{{ $r['vendor_name'] ?? '#' . $r['vendor_id'] }}</td>
                                    <td class="text-right">{{ number_format($r['invoices'], 2) }}</td>
                                    <td class="text-right">{{ number_format($r['payments'], 2) }}</td>
                                    <td class="text-right">{{ number_format($r['balance'], 2) }}</td>
                                    <td><a href="{{ route('reports.ap-aging', ['vendor_id' => $r['vendor_id']]) }}"
                                            class="btn btn-xs btn-info">Reconcile</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                        @if (!empty($totals))
                            <tfoot>
                                <tr>
                                    <th>Total</th>
                                    <th class="text-right">{{ number_format($totals['invoices'] ?? 0, 2) }}</th>
                                    <th class="text-right">{{ number_format($totals['payments'] ?? 0, 2) }}</th>
                                    <th class="text-right">{{ number_format($totals['balance'] ?? 0, 2) }}</th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </section>
@endsection
