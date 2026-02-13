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
                                <button type="button" class="btn btn-sm btn-info mr-1"
                                    onclick="showRelationshipMap('delivery-orders', {{ $deliveryOrder->id }})">
                                    <i class="fas fa-sitemap"></i> Relationship Map
                                </button>
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

                        {{-- Document Navigation Components --}}
                        <div class="card-body border-bottom">
                            @include('components.document-navigation', [
                                'documentType' => 'delivery-order',
                                'documentId' => $deliveryOrder->id,
                            ])
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
                                        @if($deliveryOrder->salesOrder->reference_no)
                                        <tr>
                                            <td><strong>Reference No:</strong></td>
                                            <td>{{ $deliveryOrder->salesOrder->reference_no }}</td>
                                        </tr>
                                        @endif
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
                            @php
                                $pickingStatuses = ['picking', 'packed', 'ready', 'in_transit'];
                                $deliveryStatuses = ['ready', 'in_transit', 'partial_delivered', 'delivered'];
                                $canEditPicking = $deliveryOrder->approval_status === 'approved'
                                    && in_array($deliveryOrder->status, $pickingStatuses);
                                $canEditDelivery = $deliveryOrder->approval_status === 'approved'
                                    && in_array($deliveryOrder->status, $deliveryStatuses);
                            @endphp
                            @if ($errors->any())
                                <div class="alert alert-danger">
                                    <ul class="mb-0">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
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
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($deliveryOrder->lines as $line)
                                                <tr data-line-id="{{ $line->id }}">
                                                    <td>{{ $line->item_code ?? 'N/A' }}</td>
                                                    <td>{{ $line->item_name ?? 'N/A' }}</td>
                                                    <td class="text-right">{{ number_format($line->ordered_qty, 2) }}</td>
                                                    <td class="text-right" data-line-id="{{ $line->id }}" data-field="picked">
                                                        @if ($canEditPicking)
                                                            <form method="post"
                                                                action="{{ route('delivery-orders.update-picking', $deliveryOrder) }}"
                                                                class="d-inline do-qty-form" data-field="picked" data-ordered="{{ $line->ordered_qty }}">
                                                                @csrf
                                                                <input type="hidden" name="line_id" value="{{ $line->id }}">
                                                                <input type="number" name="picked_qty"
                                                                    value="{{ old('line_id') == $line->id ? (old('picked_qty') ?? $line->picked_qty) : $line->picked_qty }}"
                                                                    min="0" max="{{ $line->ordered_qty }}" step="0.01"
                                                                    placeholder="0"
                                                                    class="form-control form-control-sm text-right d-inline-block mr-1"
                                                                    style="width: 80px;" required>
                                                                <button type="submit" class="btn btn-xs btn-outline-primary d-inline-block" title="Save">
                                                                    <i class="fas fa-save"></i>
                                                                </button>
                                                            </form>
                                                            <small class="text-muted d-block">Max: {{ number_format($line->ordered_qty, 2) }}</small>
                                                        @else
                                                            {{ number_format($line->picked_qty, 2) }}
                                                        @endif
                                                    </td>
                                                    <td class="text-right" data-line-id="{{ $line->id }}" data-field="delivered" data-picked="{{ $line->picked_qty }}">
                                                        @if ($canEditDelivery && $line->picked_qty > 0)
                                                            <form method="post"
                                                                action="{{ route('delivery-orders.update-delivery', $deliveryOrder) }}"
                                                                class="d-inline do-qty-form" data-field="delivered" data-picked="{{ $line->picked_qty }}">
                                                                @csrf
                                                                <input type="hidden" name="line_id" value="{{ $line->id }}">
                                                                <input type="number" name="delivered_qty"
                                                                    value="{{ old('line_id') == $line->id ? (old('delivered_qty') ?? $line->delivered_qty) : $line->delivered_qty }}"
                                                                    min="0" max="{{ $line->picked_qty }}" step="0.01"
                                                                    placeholder="0"
                                                                    class="form-control form-control-sm text-right d-inline-block mr-1"
                                                                    style="width: 80px;" required>
                                                                <button type="submit" class="btn btn-xs btn-outline-primary d-inline-block" title="Save">
                                                                    <i class="fas fa-save"></i>
                                                                </button>
                                                            </form>
                                                            <small class="text-muted d-block">Max: {{ number_format($line->picked_qty, 2) }}</small>
                                                        @else
                                                            {{ number_format($line->delivered_qty, 2) }}
                                                        @endif
                                                    </td>
                                                    <td class="line-status-cell">
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
                                                <th colspan="5" class="text-right">Total:</th>
                                                <th class="text-right">{{ number_format($deliveryOrder->total_amount, 2) }}</th>
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
                            @if (in_array($deliveryOrder->status, ['delivered', 'completed']) && $deliveryOrder->approval_status === 'approved' && ($deliveryOrder->closure_status ?? 'open') !== 'closed')
                                <div class="row mt-3">
                                    <div class="col-md-12">
                                        <a href="{{ route('delivery-orders.create-invoice', $deliveryOrder) }}"
                                            class="btn btn-success">
                                            <i class="fas fa-file-invoice-dollar"></i> Create Invoice from Delivery Order
                                        </a>
                                        <small class="text-muted ml-2">Creates Sales Invoice from delivered quantities</small>
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

    {{-- Include Relationship Map Modal --}}
    @include('components.relationship-map-modal')
@endsection

@push('scripts')
    <script>
        function showRejectModal() {
            $('#rejectModal').modal('show');
        }

        document.querySelectorAll('.do-qty-form').forEach(function(form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                var btn = form.querySelector('button[type="submit"]');
                var originalHtml = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

                fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                })
                .then(function(res) { return res.json().then(function(data) { return { ok: res.ok, data: data }; }); })
                .then(function(result) {
                    if (result.ok && result.data.success) {
                        if (typeof toastr !== 'undefined') {
                            toastr.success(result.data.message);
                        } else {
                            alert(result.data.message);
                        }
                        var row = form.closest('tr');
                        var lineId = row.dataset.lineId;
                        if (form.dataset.field === 'picked') {
                            var input = form.querySelector('input[name="picked_qty"]');
                            input.value = result.data.line.picked_qty;
                            var deliveredCell = row.querySelector('td[data-field="delivered"]');
                            if (deliveredCell && deliveredCell.querySelector('form')) {
                                var deliveredForm = deliveredCell.querySelector('form');
                                var deliveredInput = deliveredForm.querySelector('input[name="delivered_qty"]');
                                deliveredForm.dataset.picked = result.data.line.picked_qty;
                                deliveredInput.max = result.data.line.picked_qty;
                                deliveredCell.dataset.picked = result.data.line.picked_qty;
                                var maxHint = deliveredCell.querySelector('small.text-muted');
                                if (maxHint) maxHint.textContent = 'Max: ' + parseFloat(result.data.line.picked_qty).toLocaleString('en', { minimumFractionDigits: 2 });
                            } else if (result.data.line.picked_qty > 0) {
                                location.reload();
                            }
                        } else {
                            var input = form.querySelector('input[name="delivered_qty"]');
                            input.value = result.data.line.delivered_qty;
                        }
                        var statusCell = row.querySelector('.line-status-cell span.badge');
                        if (statusCell && result.data.line.status) {
                            statusCell.className = 'badge badge-' + (result.data.line.status === 'delivered' ? 'success' : 'warning');
                            statusCell.textContent = result.data.line.status.replace(/_/g, ' ').replace(/\b\w/g, function(c) { return c.toUpperCase(); });
                        }
                    } else {
                        var msg = result.data && result.data.message ? result.data.message : 'An error occurred';
                        if (typeof toastr !== 'undefined') {
                            toastr.error(msg);
                        } else {
                            alert(msg);
                        }
                    }
                })
                .catch(function(err) {
                    if (typeof toastr !== 'undefined') {
                        toastr.error('Request failed');
                    } else {
                        alert('Request failed');
                    }
                })
                .finally(function() {
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                });
            });
        });
    </script>
@endpush
