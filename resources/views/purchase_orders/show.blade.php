@extends('layouts.main')

@section('title_page')
    Purchase Order {{ $order->order_no ?? '#' . $order->id }}
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('purchase-orders.index') }}">Purchase Orders</a></li>
    <li class="breadcrumb-item active">{{ $order->order_no ?? '#' . $order->id }}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="card-title">
                            <i class="fas fa-shopping-cart mr-1"></i>
                            Purchase Order {{ $order->order_no ?? '#' . $order->id }}
                        </h3>
                    </div>
                    <div>
                        <a href="{{ route('purchase-orders.index') }}" class="btn btn-sm btn-secondary mr-1">
                            <i class="fas fa-arrow-left"></i> Back to Purchase Orders
                        </a>
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
                            <div>{{ $order->businessPartner->name ?? '#' . $order->business_partner_id }}</div>
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
                        $receivedQty = (float) DB::table('goods_receipt_po_lines as grl')
                            ->join('goods_receipt_po as grn', 'grn.id', '=', 'grl.grpo_id')
                            ->where('grn.purchase_order_id', $order->id)
                            ->sum('grl.qty');
                    @endphp
                    <p><b>Ordered vs Received:</b> {{ number_format($orderedQty, 2) }} ordered |
                        {{ number_format($receivedQty, 2) }} received</p>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Item/Account</th>
                                    <th>Description</th>
                                    <th class="text-right">Qty</th>
                                    <th class="text-right">Unit Price</th>
                                    <th class="text-right">VAT</th>
                                    <th class="text-right">WTax</th>
                                    <th class="text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($order->lines as $l)
                                    <tr>
                                        <td>
                                            @if ($l->inventory_item_id && $l->inventoryItem)
                                                <strong>{{ $l->inventoryItem->code }}</strong><br>
                                                <small class="text-muted">{{ $l->inventoryItem->name }}</small>
                                            @elseif($l->item_code)
                                                <strong>{{ $l->item_code }}</strong><br>
                                                <small class="text-muted">{{ $l->item_name }}</small>
                                            @else
                                                <span class="text-muted">#{{ $l->account_id }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $l->description }}</td>
                                        <td class="text-right">{{ number_format($l->qty, 2) }}</td>
                                        <td class="text-right">{{ number_format($l->unit_price, 2) }}</td>
                                        <td class="text-right">{{ $l->vat_rate ?? 0 }}%</td>
                                        <td class="text-right">{{ $l->wtax_rate ?? 0 }}%</td>
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
