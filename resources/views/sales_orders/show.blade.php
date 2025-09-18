@extends('layouts.main')

@section('title_page')
    Sales Orders
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('sales-orders.index') }}">Sales Orders</a></li>
    <li class="breadcrumb-item active">Detail</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h3 class="card-title">Sales Order {{ $order->order_no ?? '#' . $order->id }}</h3>
                    <div>
                        <form method="post" action="{{ route('sales-orders.approve', $order->id) }}" class="d-inline"
                            data-confirm="Approve this Sales Order?">
                            @csrf
                            <button class="btn btn-sm btn-primary" aria-label="Approve Sales Order"
                                {{ $order->status !== 'draft' ? 'disabled' : '' }}>Approve</button>
                        </form>
                        <form method="post" action="{{ route('sales-orders.close', $order->id) }}" class="d-inline"
                            data-confirm="Close this Sales Order?">
                            @csrf
                            <button class="btn btn-sm btn-warning" aria-label="Close Sales Order"
                                {{ $order->status !== 'approved' ? 'disabled' : '' }}>Close</button>
                        </form>
                        <a href="{{ route('sales-orders.create-invoice', $order->id) }}" class="btn btn-sm btn-success"
                            aria-label="Create Invoice from Sales Order">Create Invoice</a>
                        @if ($order->order_type === 'item' && $order->approval_status === 'approved' && $order->status === 'confirmed')
                            <a href="{{ route('delivery-orders.create', ['sales_order_id' => $order->id]) }}"
                                class="btn btn-sm btn-info" aria-label="Create Delivery Order from Sales Order">
                                <i class="fas fa-truck"></i> Create Delivery Order
                            </a>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    @if (session('success'))
                        <script>
                            toastr.success(@json(session('success')));
                        </script>
                    @endif
                    <div class="row mb-3">
                        <div class="col-md-3"><b>Date</b>
                            <div>{{ $order->date }}</div>
                        </div>
                        <div class="col-md-3"><b>Customer</b>
                            <div>#{{ $order->customer_id }}</div>
                        </div>
                        <div class="col-md-3"><b>Status</b>
                            <div>{{ strtoupper($order->status) }}</div>
                        </div>
                        <div class="col-md-3"><b>Total</b>
                            <div>{{ number_format($order->total_amount, 2) }}</div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Account</th>
                                    <th>Description</th>
                                    <th class="text-right">Qty</th>
                                    <th class="text-right">Unit Price</th>
                                    <th class="text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($order->lines as $l)
                                    <tr>
                                        <td>#{{ $l->account_id }}</td>
                                        <td>{{ $l->description }}</td>
                                        <td class="text-right">{{ number_format($l->qty, 2) }}</td>
                                        <td class="text-right">{{ number_format($l->unit_price, 2) }}</td>
                                        <td class="text-right">{{ number_format($l->amount, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const confs = document.querySelectorAll('form[data-confirm]');
            confs.forEach(function(f) {
                f.addEventListener('submit', function(e) {
                    if (!confirm(f.getAttribute('data-confirm'))) {
                        e.preventDefault();
                    }
                });
            });
        });
    </script>
@endsection
