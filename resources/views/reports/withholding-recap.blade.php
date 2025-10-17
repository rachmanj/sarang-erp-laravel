@extends('layouts.main')

@section('title_page')
    Withholding Recap
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">Withholding Recap</li>
@endsection
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Withholding Recap</h3>
                    <div>
                        <form class="form-inline" method="get">
                            <input type="date" name="from" class="form-control form-control-sm mr-1"
                                value="{{ request('from') }}" />
                            <input type="date" name="to" class="form-control form-control-sm mr-1"
                                value="{{ request('to') }}" />
                            <input type="number" name="business_partner_id" class="form-control form-control-sm mr-1"
                                value="{{ request('business_partner_id') }}" placeholder="Vendor ID" />
                            <button class="btn btn-sm btn-primary">Apply</button>
                            <a class="btn btn-sm btn-outline-secondary ml-2"
                                href="{{ route('reports.withholding-recap', array_filter(['from' => request('from'), 'to' => request('to'), 'vendor_id' => request('vendor_id'), 'export' => 'csv'])) }}">CSV</a>
                            <a class="btn btn-sm btn-outline-secondary"
                                href="{{ route('reports.withholding-recap', array_filter(['from' => request('from'), 'to' => request('to'), 'vendor_id' => request('vendor_id'), 'export' => 'pdf'])) }}">PDF</a>
                        </form>
                    </div>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Vendor</th>
                                <th class="text-right">Withholding Total</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows ?? [] as $r)
                                <tr>
                                    <td>{{ $r['vendor_name'] ?? '#' . $r['vendor_id'] }}</td>
                                    <td class="text-right">{{ number_format($r['withholding_total'], 2) }}</td>
                                    <td>
                                        <a class="btn btn-xs btn-info"
                                            href="{{ route('purchase-invoices.index') }}?q=&vendor_id={{ $r['vendor_id'] }}">View
                                            PIs</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        @if (!empty($totals))
                            <tfoot>
                                <tr>
                                    <th>Total</th>
                                    <th class="text-right">{{ number_format($totals['withholding_total'] ?? 0, 2) }}</th>
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
