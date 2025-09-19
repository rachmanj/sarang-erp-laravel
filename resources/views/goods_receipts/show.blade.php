@extends('layouts.main')
@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h3 class="card-title">Goods Receipt {{ $grn->grn_no ?? '#' . $grn->id }}</h3>
                    <div>
                        <form method="post" action="{{ route('goods-receipts.receive', $grn->id) }}" class="d-inline"
                            data-confirm="Mark this GRN as received?">
                            @csrf<button class="btn btn-sm btn-primary" aria-label="Mark GRN Received"
                                {{ $grn->status !== 'draft' ? 'disabled' : '' }}>Mark
                                Received</button></form>
                        <a href="{{ route('goods-receipts.create-invoice', $grn->id) }}" class="btn btn-sm btn-success"
                            aria-label="Create Purchase Invoice from GRN">Create Purchase Invoice</a>
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
                            <div>{{ $grn->date }}</div>
                        </div>
                        <div class="col-md-3"><b>Vendor</b>
                            <div>#{{ $grn->business_partner_id }}</div>
                        </div>
                        <div class="col-md-3"><b>Status</b>
                            <div>{{ strtoupper($grn->status) }}</div>
                        </div>
                        <div class="col-md-3"><b>Total</b>
                            <div>{{ number_format($grn->total_amount, 2) }}</div>
                        </div>
                    </div>
                    @php
                        $orderedQty = null;
                        if (!empty($grn->purchase_order_id)) {
                            $orderedQty = (float) DB::table('purchase_order_lines')
                                ->where('order_id', $grn->purchase_order_id)
                                ->sum('qty');
                        }
                        $receivedQty = (float) DB::table('goods_receipt_lines')->where('grn_id', $grn->id)->sum('qty');
                    @endphp
                    @if (!is_null($orderedQty))
                        <p><b>Ordered vs Received:</b> {{ number_format($orderedQty, 2) }} ordered |
                            {{ number_format($receivedQty, 2) }} received</p>
                    @else
                        <p><b>Received Qty:</b> {{ number_format($receivedQty, 2) }}</p>
                    @endif
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
                                @foreach ($grn->lines as $l)
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
