@extends('layouts.main')

@section('title', 'Sales Receipts')

@section('title_page')
    Sales Receipts
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">Sales Receipts</li>
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
                            <h3 class="card-title">Sales Receipts</h3>
                            @can('ar.receipts.create')
                                <a href="{{ route('sales-receipts.create') }}" class="btn btn-sm btn-primary">Create</a>
                            @endcan
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-bordered table-striped table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Receipt No</th>
                                        <th>Customer</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($receipts as $r)
                                        <tr>
                                            <td>{{ $r->date }}</td>
                                            <td>{{ $r->receipt_no }}</td>
                                            <td>{{ optional(DB::table('customers')->find($r->customer_id))->name }}</td>
                                            <td>{{ number_format($r->total_amount, 2) }}</td>
                                            <td>{{ strtoupper($r->status) }}</td>
                                            <td>
                                                <a href="{{ route('sales-receipts.show', $r->id) }}"
                                                    class="btn btn-xs btn-info">View</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer">{{ $receipts->links() }}</div>
                    </div>
                </div>
            </div>
        </div>
    @endsection
