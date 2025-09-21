@extends('layouts.main')

@section('title', 'Delivery Order Details')

@section('title_page')
    Delivery Order Details
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('delivery-orders.index') }}">Delivery Orders</a></li>
    <li class="breadcrumb-item active">{{ $deliveryOrder->do_number }}</li>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-truck mr-1"></i>
                                Delivery Order: {{ $deliveryOrder->do_number }}
                            </h3>
                            <div class="card-tools">
                                <a href="{{ route('delivery-orders.print', $deliveryOrder) }}" class="btn btn-sm btn-info"
                                    target="_blank">
                                    <i class="fas fa-print"></i> Print
                                </a>
                                @if ($deliveryOrder->status === 'draft')
                                    <a href="{{ route('delivery-orders.edit', $deliveryOrder) }}"
                                        class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                @endif
                                <a href="{{ route('delivery-orders.index') }}" class="btn btn-sm btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Back
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Status -->
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <span
                                        class="badge badge-{{ $deliveryOrder->status === 'delivered' ? 'success' : 'info' }}">
                                        Status: {{ ucfirst(str_replace('_', ' ', $deliveryOrder->status)) }}
                                    </span>
                                </div>
                                <div class="col-md-6">
                                    <span
                                        class="badge badge-{{ $deliveryOrder->approval_status === 'approved' ? 'success' : 'warning' }}">
                                        Approval: {{ ucfirst($deliveryOrder->approval_status) }}
                                    </span>
                                </div>
                            </div>

                            <!-- Details -->
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-sm">
                                        <tr>
                                            <td><strong>DO Number:</strong></td>
                                            <td>{{ $deliveryOrder->do_number }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Sales Order:</strong></td>
                                            <td>{{ $deliveryOrder->salesOrder->order_no }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Customer:</strong></td>
                                            <td>{{ $deliveryOrder->customer->name }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Planned Delivery:</strong></td>
                                            <td>{{ $deliveryOrder->planned_delivery_date->format('d M Y') }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Delivery Method:</strong></td>
                                            <td>{{ ucfirst(str_replace('_', ' ', $deliveryOrder->delivery_method)) }}</td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <address>
                                        {{ $deliveryOrder->delivery_address }}<br>
                                        @if ($deliveryOrder->delivery_contact_person)
                                            Contact: {{ $deliveryOrder->delivery_contact_person }}<br>
                                        @endif
                                        @if ($deliveryOrder->delivery_phone)
                                            Phone: {{ $deliveryOrder->delivery_phone }}<br>
                                        @endif
                                    </address>
                                </div>
                            </div>

                            <!-- Items -->
                            <div class="card card-secondary mt-3">
                                <div class="card-header">
                                    <h3 class="card-title">Delivery Items</h3>
                                </div>
                                <div class="card-body p-0">
                                    <table class="table table-sm table-striped mb-0">
                                        <thead>
                                            <tr>
                                                <th>Item Code</th>
                                                <th>Item Name</th>
                                                <th class="text-right">Ordered Qty</th>
                                                <th class="text-right">Picked Qty</th>
                                                <th class="text-right">Delivered Qty</th>
                                                <th class="text-right">Unit Price</th>
                                                <th class="text-right">Amount</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($deliveryOrder->lines as $line)
                                                <tr>
                                                    <td>{{ $line->item_code ?? 'N/A' }}</td>
                                                    <td>{{ $line->item_name ?? 'N/A' }}</td>
                                                    <td class="text-right">{{ number_format($line->ordered_qty, 2) }}</td>
                                                    <td class="text-right">{{ number_format($line->picked_qty, 2) }}</td>
                                                    <td class="text-right">{{ number_format($line->delivered_qty, 2) }}
                                                    </td>
                                                    <td class="text-right">{{ number_format($line->unit_price, 2) }}</td>
                                                    <td class="text-right">{{ number_format($line->amount, 2) }}</td>
                                                    <td>
                                                        <span
                                                            class="badge badge-{{ $line->status === 'delivered' ? 'success' : 'warning' }}">
                                                            {{ ucfirst(str_replace('_', ' ', $line->status)) }}
                                                        </span>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th colspan="6" class="text-right">Total:</th>
                                                <th class="text-right">{{ number_format($deliveryOrder->total_amount, 2) }}
                                                </th>
                                                <th></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>

                            <!-- Approval Actions -->
                            @if ($deliveryOrder->approval_status === 'pending')
                                <div class="row mt-3">
                                    <div class="col-md-12">
                                        <form method="post"
                                            action="{{ route('delivery-orders.approve', $deliveryOrder) }}"
                                            class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-success">
                                                <i class="fas fa-check"></i> Approve
                                            </button>
                                        </form>
                                        <button type="button" class="btn btn-danger" onclick="showRejectModal()">
                                            <i class="fas fa-times"></i> Reject
                                        </button>
                                    </div>
                                </div>
                            @endif

                            <!-- Complete Delivery Action -->
                            @if ($deliveryOrder->status === 'in_transit' && $deliveryOrder->approval_status === 'approved')
                                <div class="row mt-3">
                                    <div class="col-md-12">
                                        <form method="post"
                                            action="{{ route('delivery-orders.complete-delivery', $deliveryOrder) }}"
                                            class="d-inline">
                                            @csrf
                                            <div class="form-group">
                                                <label for="actual_delivery_date">Actual Delivery Date:</label>
                                                <input type="date" name="actual_delivery_date" id="actual_delivery_date"
                                                    value="{{ $deliveryOrder->actual_delivery_date ? $deliveryOrder->actual_delivery_date->format('Y-m-d') : now()->format('Y-m-d') }}"
                                                    class="form-control d-inline-block" style="width: auto;">
                                            </div>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-check-circle"></i> Complete Delivery & Recognize Revenue
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endif
                            
                            <!-- Create Invoice Action -->
                            @if ($deliveryOrder->status === 'delivered' && $deliveryOrder->approval_status === 'approved')
                                <div class="row mt-3">
                                    <div class="col-md-12">
                                        <a href="{{ route('delivery-orders.create-invoice', $deliveryOrder) }}" 
                                           class="btn btn-success">
                                            <i class="fas fa-file-invoice-dollar"></i> Create Invoice from Delivery Order
                                        </a>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Reject Modal -->
    <div class="modal fade" id="rejectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Reject Delivery Order</h4>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <form method="post" action="{{ route('delivery-orders.reject', $deliveryOrder) }}">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Rejection Reason: <span class="text-danger">*</span></label>
                            <textarea name="comments" class="form-control" rows="3" required
                                placeholder="Please provide reason for rejection"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Reject</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function showRejectModal() {
            $('#rejectModal').modal('show');
        }
    </script>
@endpush
