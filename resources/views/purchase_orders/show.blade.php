@extends('layouts.main')
@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h3 class="card-title">Purchase Order {{ $order->order_no ?? '#' . $order->id }}</h3>
                    <div>
                        <form method="post" action="{{ route('purchase-orders.approve', $order->id) }}" class="d-inline"
                            data-confirm="Approve this Purchase Order?">
                            @csrf<button class="btn btn-sm btn-primary" aria-label="Approve Purchase Order"
                                {{ $order->status !== 'draft' ? 'disabled' : '' }}>Approve</button></form>
                        <form method="post" action="{{ route('purchase-orders.close', $order->id) }}" class="d-inline"
                            data-confirm="Close this Purchase Order?">
                            @csrf<button class="btn btn-sm btn-warning" aria-label="Close Purchase Order"
                                {{ $order->status !== 'approved' ? 'disabled' : '' }}>Close</button></form>
                        <a href="{{ route('purchase-orders.create-invoice', $order->id) }}" class="btn btn-sm btn-success"
                            aria-label="Create Invoice from Purchase Order">Create Invoice</a>
                        @can('assets.create')
                            <a href="{{ route('purchase-orders.create-assets', $order->id) }}" class="btn btn-sm btn-info"
                                aria-label="Create Assets from Purchase Order">Create Assets</a>
                        @endcan
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-3"><b>Date</b>
                            <div>{{ $order->date }}</div>
                        </div>
                        <div class="col-md-3"><b>Vendor</b>
                            <div>#{{ $order->vendor_id }}</div>
                        </div>
                        <div class="col-md-3"><b>Status</b>
                            <div>{{ strtoupper($order->status) }}</div>
                        </div>
                        <div class="col-md-3"><b>Total</b>
                            <div>{{ number_format($order->total_amount, 2) }}</div>
                        </div>
                    </div>
                    @if (session('success'))
                        <script>
                            toastr.success(@json(session('success')));
                        </script>
                    @endif
                    @php
                        $orderedQty = (float) DB::table('purchase_order_lines')
                            ->where('order_id', $order->id)
                            ->sum('qty');
                        $receivedQty = (float) DB::table('goods_receipt_lines as grl')
                            ->join('goods_receipts as grn', 'grn.id', '=', 'grl.grn_id')
                            ->where('grn.purchase_order_id', $order->id)
                            ->sum('grl.qty');
                    @endphp
                    <p><b>Ordered vs Received:</b> {{ number_format($orderedQty, 2) }} ordered |
                        {{ number_format($receivedQty, 2) }} received</p>
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
    </section>
@endsection
