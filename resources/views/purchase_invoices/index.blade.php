@extends('layouts.main')

@section('title', 'Purchase Invoices')

@section('title_page')
    Purchase Invoices
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">Purchase Invoices</li>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    @if (session('success'))
                        <script>
                            toastr.success(@json(session('success')));
                        </script>
                    @endif
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h3 class="card-title">Purchase Invoices</h3>
                            @can('ap.invoices.create')
                                <a href="{{ route('purchase-invoices.create') }}" class="btn btn-sm btn-primary">Create</a>
                            @endcan
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-bordered table-striped table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Invoice No</th>
                                        <th>Vendor</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($invoices as $inv)
                                        <tr>
                                            <td>{{ $inv->date }}</td>
                                            <td>{{ $inv->invoice_no }}</td>
                                            <td>{{ optional(DB::table('vendors')->find($inv->vendor_id))->name }}</td>
                                            <td>{{ number_format($inv->total_amount, 2) }}</td>
                                            <td>{{ strtoupper($inv->status) }}</td>
                                            <td>
                                                <a href="{{ route('purchase-invoices.show', $inv->id) }}"
                                                    class="btn btn-xs btn-info">View</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer">{{ $invoices->links() }}</div>
                    </div>
                </div>
            </div>
        </div>
    @endsection
