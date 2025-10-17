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
                        <button type="button" class="btn btn-sm btn-info mr-1"
                            onclick="showRelationshipMap('sales-orders', {{ $order->id }})">
                            <i class="fas fa-sitemap"></i> Relationship Map
                        </button>
                        <button class="btn btn-sm btn-primary" aria-label="Approve Sales Order"
                            {{ $order->approval_status !== 'pending' ? 'disabled' : '' }}
                            onclick="confirmApproval({{ $order->id }}, 'approve')">Approve</button>
                        <button class="btn btn-sm btn-warning" aria-label="Close Sales Order"
                            {{ $order->status !== 'approved' ? 'disabled' : '' }}
                            onclick="confirmApproval({{ $order->id }}, 'close')">Close</button>
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
                            <div>#{{ $order->business_partner_id }}</div>
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
        function confirmApproval(orderId, action) {
            const actionText = action === 'approve' ? 'approve' : 'close';
            const actionTitle = action === 'approve' ? 'Approve Sales Order' : 'Close Sales Order';
            const actionMessage = action === 'approve' ? 'Are you sure you want to approve this Sales Order?' :
                'Are you sure you want to close this Sales Order?';
            const confirmText = action === 'approve' ? 'Yes, approve it!' : 'Yes, close it!';
            const icon = action === 'approve' ? 'question' : 'warning';

            Swal.fire({
                title: actionTitle,
                text: actionMessage,
                icon: icon,
                showCancelButton: true,
                confirmButtonColor: action === 'approve' ? '#3085d6' : '#f39c12',
                cancelButtonColor: '#6c757d',
                confirmButtonText: confirmText,
                cancelButtonText: 'Cancel',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    // Create and submit form
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = action === 'approve' ? '{{ route('sales-orders.approve', ':id') }}'.replace(
                        ':id', orderId) : '{{ route('sales-orders.close', ':id') }}'.replace(':id', orderId);

                    const csrfToken = document.createElement('input');
                    csrfToken.type = 'hidden';
                    csrfToken.name = '_token';
                    csrfToken.value = '{{ csrf_token() }}';

                    form.appendChild(csrfToken);
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }
    </script>

    {{-- Include Relationship Map Modal --}}
    @include('components.relationship-map-modal')
@endsection
