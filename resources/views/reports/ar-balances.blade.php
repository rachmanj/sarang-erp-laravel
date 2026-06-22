@extends('layouts.main')

@section('title_page')
    AR Party Balances
@endsection

@section('breadcrumb_title')
    <li class='breadcrumb-item'><a href='/dashboard'>Dashboard</a></li>
    <li class='breadcrumb-item active'>AR Party Balances</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h3 class="card-title">AR Party Balances</h3>
                    <form method="get" class="form-inline">
                        <label class="mr-2 mb-0">As of
                            <input type="date" name="as_of" value="{{ $as_of ?? now()->toDateString() }}"
                                class="form-control form-control-sm ml-1">
                        </label>
                        <button class="btn btn-sm btn-secondary mr-2">Apply</button>
                        <a class="btn btn-sm btn-outline-secondary"
                            href="{{ route('reports.ar-balances', array_merge(request()->query(), ['export' => 'csv'])) }}">CSV</a>
                        <a class="btn btn-sm btn-outline-secondary"
                            href="{{ route('reports.ar-balances', array_merge(request()->query(), ['export' => 'pdf'])) }}">PDF</a>
                    </form>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th class="text-right">Invoices</th>
                                <th class="text-right">Receipts</th>
                                <th class="text-right">Balance</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows ?? [] as $r)
                                <tr>
                                    <td>{{ $r['customer_name'] ?? '#' . $r['customer_id'] }}</td>
                                    <td class="text-right">{{ number_format($r['invoices'], 2) }}</td>
                                    <td class="text-right">{{ number_format($r['receipts'], 2) }}</td>
                                    <td class="text-right">{{ number_format($r['balance'], 2) }}</td>
                                    <td><a href="{{ route('reports.ar-aging', ['customer_id' => $r['customer_id']]) }}"
                                            class="btn btn-xs btn-info">Reconcile</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                        @if (!empty($totals))
                            <tfoot>
                                <tr>
                                    <th>Total</th>
                                    <th class="text-right">{{ number_format($totals['invoices'] ?? 0, 2) }}</th>
                                    <th class="text-right">{{ number_format($totals['receipts'] ?? 0, 2) }}</th>
                                    <th class="text-right">{{ number_format($totals['balance'] ?? 0, 2) }}</th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
