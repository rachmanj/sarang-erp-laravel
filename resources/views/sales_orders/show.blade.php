@extends('layouts.main')

@section('title_page')
    Sales Orders
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('sales-orders.index') }}">Sales Orders</a></li>
    <li class="breadcrumb-item active">{{ $order->order_no ?? '#' . $order->id }}</li>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-shopping-cart mr-1"></i>
                                Sales Order: {{ $order->order_no ?? '#' . $order->id }}
                            </h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-sm btn-info mr-1"
                                    onclick="showRelationshipMap('sales-orders', {{ $order->id }})">
                                    <i class="fas fa-sitemap"></i> Relationship Map
                                </button>
                                @if ($order->approval_status === 'pending')
                                    <button class="btn btn-sm btn-primary" aria-label="Approve Sales Order"
                                        onclick="confirmApproval({{ $order->id }}, 'approve')">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                @endif
                                @if ($order->approval_status === 'approved' && ($order->status === 'ordered' || $order->status === 'draft'))
                                    <button class="btn btn-sm btn-success" aria-label="Confirm Sales Order"
                                        onclick="confirmApproval({{ $order->id }}, 'confirm')">
                                        <i class="fas fa-check-circle"></i> Confirm
                                    </button>
                                @endif
                                @if ($order->status === 'delivered')
                                    <button class="btn btn-sm btn-warning" aria-label="Close Sales Order"
                                        onclick="confirmApproval({{ $order->id }}, 'close')">
                                        <i class="fas fa-times-circle"></i> Close
                                    </button>
                                @endif
                                <a href="{{ route('sales-orders.create-invoice', $order->id) }}" 
                                    class="btn btn-sm btn-success"
                                    aria-label="Create Invoice from Sales Order">
                                    <i class="fas fa-file-invoice-dollar"></i> Create Invoice
                                </a>
                                @if ($order->order_type === 'item' && $order->approval_status === 'approved' && in_array($order->status, ['confirmed', 'processing']))
                                    <a href="{{ route('delivery-orders.create', ['sales_order_id' => $order->id]) }}"
                                        class="btn btn-sm btn-info" aria-label="Create Delivery Order from Sales Order">
                                        <i class="fas fa-truck"></i> Create Delivery Order
                                    </a>
                                @endif
                                <a href="{{ route('sales-orders.index') }}" class="btn btn-sm btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Back
                                </a>
                            </div>
                        </div>

                        {{-- Document Navigation Components --}}
                        <div class="card-body border-bottom">
                            @include('components.document-navigation', [
                                'documentType' => 'sales-order',
                                'documentId' => $order->id,
                            ])
                        </div>

                        <div class="card-body">
                            @if (session('success'))
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    {{ session('success') }}
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                            @endif

                            <!-- Status Badges -->
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <span class="badge badge-{{ $order->status === 'closed' ? 'secondary' : ($order->status === 'approved' ? 'success' : 'info') }} badge-lg">
                                        Status: {{ ucfirst(str_replace('_', ' ', $order->status)) }}
                                    </span>
                                </div>
                                <div class="col-md-6">
                                    <span class="badge badge-{{ $order->approval_status === 'approved' ? 'success' : ($order->approval_status === 'rejected' ? 'danger' : 'warning') }} badge-lg">
                                        Approval: {{ ucfirst($order->approval_status) }}
                                    </span>
                                </div>
                            </div>

                            <!-- Order Details -->
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-sm table-borderless">
                                        <tr>
                                            <td width="40%"><strong>Order Number:</strong></td>
                                            <td>{{ $order->order_no ?? '#' . $order->id }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Reference No:</strong></td>
                                            <td>{{ $order->reference_no ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Date:</strong></td>
                                            <td>{{ $order->date->format('d M Y') }}</td>
                                        </tr>
                                        @if($order->expected_delivery_date)
                                        <tr>
                                            <td><strong>Expected Delivery:</strong></td>
                                            <td>{{ $order->expected_delivery_date->format('d M Y') }}</td>
                                        </tr>
                                        @endif
                                        @if($order->actual_delivery_date)
                                        <tr>
                                            <td><strong>Actual Delivery:</strong></td>
                                            <td>{{ $order->actual_delivery_date->format('d M Y') }}</td>
                                        </tr>
                                        @endif
                                        <tr>
                                            <td><strong>Customer:</strong></td>
                                            <td>
                                                @if($order->customer)
                                                    <strong>{{ $order->customer->name }}</strong>
                                                    @if($order->customer->code)
                                                        <br><small class="text-muted">Code: {{ $order->customer->code }}</small>
                                                    @endif
                                                @else
                                                    <span class="text-muted">N/A</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @if($order->companyEntity)
                                        <tr>
                                            <td><strong>Company Entity:</strong></td>
                                            <td>{{ $order->companyEntity->name }}</td>
                                        </tr>
                                        @endif
                                        @if($order->warehouse)
                                        <tr>
                                            <td><strong>Warehouse:</strong></td>
                                            <td>{{ $order->warehouse->name }}</td>
                                        </tr>
                                        @endif
                                        @if($order->currency)
                                        <tr>
                                            <td><strong>Currency:</strong></td>
                                            <td>
                                                {{ $order->currency->code }} 
                                                @if($order->exchange_rate != 1)
                                                    <small class="text-muted">(Rate: {{ number_format($order->exchange_rate, 6) }})</small>
                                                @endif
                                            </td>
                                        </tr>
                                        @endif
                                        <tr>
                                            <td><strong>Order Type:</strong></td>
                                            <td><span class="badge badge-info">{{ ucfirst($order->order_type) }}</span></td>
                                        </tr>
                                        @if($order->delivery_method)
                                        <tr>
                                            <td><strong>Delivery Method:</strong></td>
                                            <td>{{ ucfirst(str_replace('_', ' ', $order->delivery_method)) }}</td>
                                        </tr>
                                        @endif
                                        @if($order->delivery_address || $order->delivery_contact_person)
                                        <tr>
                                            <td><strong>Delivery Address:</strong></td>
                                            <td>
                                                @if($order->delivery_address)
                                                    <div class="text-pre-wrap">{{ nl2br(e($order->delivery_address)) }}</div>
                                                @endif
                                                @if($order->delivery_contact_person)
                                                    <div class="mt-1"><small class="text-muted">Contact: {{ $order->delivery_contact_person }}{{ $order->delivery_phone ? ' | ' . $order->delivery_phone : '' }}</small></div>
                                                @endif
                                            </td>
                                        </tr>
                                        @endif
                                        @if($order->payment_terms)
                                        <tr>
                                            <td><strong>Payment Terms:</strong></td>
                                            <td>{{ $order->payment_terms }}</td>
                                        </tr>
                                        @endif
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-sm table-borderless">
                                        @if($order->description)
                                        <tr>
                                            <td width="40%"><strong>Description:</strong></td>
                                            <td>{{ $order->description }}</td>
                                        </tr>
                                        @endif
                                        @if($order->approvedBy)
                                        <tr>
                                            <td><strong>Approved By:</strong></td>
                                            <td>{{ $order->approvedBy->name }}</td>
                                        </tr>
                                        @endif
                                        @if($order->approved_at)
                                        <tr>
                                            <td><strong>Approved At:</strong></td>
                                            <td>{{ $order->approved_at->format('d M Y H:i') }}</td>
                                        </tr>
                                        @endif
                                        @if($order->createdBy)
                                        <tr>
                                            <td><strong>Created By:</strong></td>
                                            <td>{{ $order->createdBy->name }}</td>
                                        </tr>
                                        @endif
                                        <tr>
                                            <td><strong>Created At:</strong></td>
                                            <td>{{ $order->created_at->format('d M Y H:i') }}</td>
                                        </tr>
                                        @if($order->deliveryOrders && $order->deliveryOrders->count() > 0)
                                        <tr>
                                            <td><strong>Delivery Orders:</strong></td>
                                            <td>
                                                @foreach($order->deliveryOrders as $do)
                                                    <a href="{{ route('delivery-orders.show', $do->id) }}" class="badge badge-info">
                                                        {{ $do->do_number }}
                                                    </a>
                                                @endforeach
                                            </td>
                                        </tr>
                                        @endif
                                    </table>
                                </div>
                            </div>

                            <!-- Line Items -->
                            <div class="card card-secondary mt-3">
                                <div class="card-header">
                                    <h3 class="card-title">Order Items</h3>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-striped mb-0">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Account</th>
                                                    <th>Item Code</th>
                                                    <th>Item Name</th>
                                                    <th>Description</th>
                                                    <th class="text-right">Qty</th>
                                                    <th class="text-right">Unit Price</th>
                                                    @if($order->exchange_rate != 1)
                                                    <th class="text-right">Unit Price (Foreign)</th>
                                                    @endif
                                                    <th class="text-right">VAT Rate</th>
                                                    <th class="text-right">WTAX Rate</th>
                                                    @if($order->lines->sum('discount_amount') > 0)
                                                    <th class="text-right">Discount</th>
                                                    @endif
                                                    <th class="text-right">Amount</th>
                                                    @if($order->exchange_rate != 1)
                                                    <th class="text-right">Amount (Foreign)</th>
                                                    @endif
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($order->lines as $index => $line)
                                                    <tr>
                                                        <td>{{ $index + 1 }}</td>
                                                        <td>
                                                            @if($line->account)
                                                                {{ $line->account->code }} - {{ $line->account->name }}
                                                            @else
                                                                <span class="text-muted">#{{ $line->account_id }}</span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if($line->inventoryItem && $line->inventoryItem->code)
                                                                <span class="badge badge-secondary">{{ $line->inventoryItem->code }}</span>
                                                            @elseif($line->item_code)
                                                                <span class="badge badge-secondary">{{ $line->item_code }}</span>
                                                            @else
                                                                <span class="text-muted">-</span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if($line->item_name)
                                                                <strong>{{ $line->item_name }}</strong>
                                                            @elseif($line->inventoryItem)
                                                                <strong>{{ $line->inventoryItem->name }}</strong>
                                                            @else
                                                                <span class="text-muted">-</span>
                                                            @endif
                                                        </td>
                                                        <td>{{ $line->description ?? '-' }}</td>
                                                        <td class="text-right">{{ number_format($line->qty, 2) }}</td>
                                                        <td class="text-right">{{ number_format($line->unit_price, 2) }}</td>
                                                        @if($order->exchange_rate != 1)
                                                        <td class="text-right">{{ number_format($line->unit_price_foreign ?? $line->unit_price, 2) }}</td>
                                                        @endif
                                                        <td class="text-right">
                                                            @if($line->vat_rate > 0)
                                                                {{ number_format($line->vat_rate, 2) }}%
                                                            @else
                                                                <span class="text-muted">-</span>
                                                            @endif
                                                        </td>
                                                        <td class="text-right">
                                                            @if($line->wtax_rate > 0)
                                                                {{ number_format($line->wtax_rate, 2) }}%
                                                            @else
                                                                <span class="text-muted">-</span>
                                                            @endif
                                                        </td>
                                                        @if($order->lines->sum('discount_amount') > 0)
                                                        <td class="text-right">
                                                            @if($line->discount_amount > 0)
                                                                {{ number_format($line->discount_amount, 2) }}
                                                                @if($line->discount_percentage > 0)
                                                                    <br><small class="text-muted">({{ number_format($line->discount_percentage, 2) }}%)</small>
                                                                @endif
                                                            @else
                                                                <span class="text-muted">-</span>
                                                            @endif
                                                        </td>
                                                        @endif
                                                        <td class="text-right">
                                                            <strong>{{ number_format($line->amount, 2) }}</strong>
                                                        </td>
                                                        @if($order->exchange_rate != 1)
                                                        <td class="text-right">
                                                            <strong>{{ number_format($line->amount_foreign ?? $line->amount, 2) }}</strong>
                                                        </td>
                                                        @endif
                                                        <td>
                                                            @if($line->status)
                                                                <span class="badge badge-{{ $line->status === 'delivered' ? 'success' : ($line->status === 'partial' ? 'warning' : 'info') }}">
                                                                    {{ ucfirst(str_replace('_', ' ', $line->status)) }}
                                                                </span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    @if($line->notes)
                                                    <tr>
                                                        <td colspan="{{ $order->exchange_rate != 1 ? ($order->lines->sum('discount_amount') > 0 ? 15 : 14) : ($order->lines->sum('discount_amount') > 0 ? 13 : 12) }}" class="text-muted small">
                                                            <i class="fas fa-sticky-note"></i> Notes: {{ $line->notes }}
                                                        </td>
                                                    </tr>
                                                    @endif
                                                @endforeach
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <th colspan="{{ $order->exchange_rate != 1 ? ($order->lines->sum('discount_amount') > 0 ? 11 : 10) : ($order->lines->sum('discount_amount') > 0 ? 9 : 8) }}" class="text-right">Subtotal:</th>
                                                    <th class="text-right">{{ number_format($order->lines->sum('amount'), 2) }}</th>
                                                    @if($order->exchange_rate != 1)
                                                    <th class="text-right">{{ number_format($order->lines->sum('amount_foreign') ?? $order->lines->sum('amount'), 2) }}</th>
                                                    @endif
                                                    <th></th>
                                                </tr>
                                                @if($order->discount_amount > 0)
                                                <tr>
                                                    <th colspan="{{ $order->exchange_rate != 1 ? ($order->lines->sum('discount_amount') > 0 ? 11 : 10) : ($order->lines->sum('discount_amount') > 0 ? 9 : 8) }}" class="text-right">
                                                        Discount
                                                        @if($order->discount_percentage > 0)
                                                            ({{ number_format($order->discount_percentage, 2) }}%)
                                                        @endif
                                                        :
                                                    </th>
                                                    <th class="text-right text-danger">-{{ number_format($order->discount_amount, 2) }}</th>
                                                    @if($order->exchange_rate != 1)
                                                    <th class="text-right"></th>
                                                    @endif
                                                    <th></th>
                                                </tr>
                                                @endif
                                                @if($order->freight_cost > 0 || $order->handling_cost > 0 || $order->insurance_cost > 0)
                                                <tr>
                                                    <th colspan="{{ $order->exchange_rate != 1 ? ($order->lines->sum('discount_amount') > 0 ? 11 : 10) : ($order->lines->sum('discount_amount') > 0 ? 9 : 8) }}" class="text-right">Additional Costs:</th>
                                                    <th class="text-right"></th>
                                                    @if($order->exchange_rate != 1)
                                                    <th class="text-right"></th>
                                                    @endif
                                                    <th></th>
                                                </tr>
                                                @if($order->freight_cost > 0)
                                                <tr>
                                                    <th colspan="{{ $order->exchange_rate != 1 ? ($order->lines->sum('discount_amount') > 0 ? 11 : 10) : ($order->lines->sum('discount_amount') > 0 ? 9 : 8) }}" class="text-right pl-5">Freight:</th>
                                                    <th class="text-right">{{ number_format($order->freight_cost, 2) }}</th>
                                                    @if($order->exchange_rate != 1)
                                                    <th class="text-right"></th>
                                                    @endif
                                                    <th></th>
                                                </tr>
                                                @endif
                                                @if($order->handling_cost > 0)
                                                <tr>
                                                    <th colspan="{{ $order->exchange_rate != 1 ? ($order->lines->sum('discount_amount') > 0 ? 11 : 10) : ($order->lines->sum('discount_amount') > 0 ? 9 : 8) }}" class="text-right pl-5">Handling:</th>
                                                    <th class="text-right">{{ number_format($order->handling_cost, 2) }}</th>
                                                    @if($order->exchange_rate != 1)
                                                    <th class="text-right"></th>
                                                    @endif
                                                    <th></th>
                                                </tr>
                                                @endif
                                                @if($order->insurance_cost > 0)
                                                <tr>
                                                    <th colspan="{{ $order->exchange_rate != 1 ? ($order->lines->sum('discount_amount') > 0 ? 11 : 10) : ($order->lines->sum('discount_amount') > 0 ? 9 : 8) }}" class="text-right pl-5">Insurance:</th>
                                                    <th class="text-right">{{ number_format($order->insurance_cost, 2) }}</th>
                                                    @if($order->exchange_rate != 1)
                                                    <th class="text-right"></th>
                                                    @endif
                                                    <th></th>
                                                </tr>
                                                @endif
                                                @endif
                                                <tr class="bg-light">
                                                    <th colspan="{{ $order->exchange_rate != 1 ? ($order->lines->sum('discount_amount') > 0 ? 11 : 10) : ($order->lines->sum('discount_amount') > 0 ? 9 : 8) }}" class="text-right">Total Amount:</th>
                                                    <th class="text-right">
                                                        <strong class="text-lg">{{ number_format($order->total_amount, 2) }}</strong>
                                                    </th>
                                                    @if($order->exchange_rate != 1)
                                                    <th class="text-right">
                                                        <strong class="text-lg">{{ number_format($order->total_amount_foreign, 2) }}</strong>
                                                    </th>
                                                    @endif
                                                    <th></th>
                                                </tr>
                                                @if($order->net_amount != $order->total_amount)
                                                <tr>
                                                    <th colspan="{{ $order->exchange_rate != 1 ? ($order->lines->sum('discount_amount') > 0 ? 11 : 10) : ($order->lines->sum('discount_amount') > 0 ? 9 : 8) }}" class="text-right">Net Amount:</th>
                                                    <th class="text-right">
                                                        <strong>{{ number_format($order->net_amount, 2) }}</strong>
                                                    </th>
                                                    @if($order->exchange_rate != 1)
                                                    <th class="text-right"></th>
                                                    @endif
                                                    <th></th>
                                                </tr>
                                                @endif
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- Notes and Terms -->
                            @if($order->notes || $order->terms_conditions)
                            <div class="row mt-3">
                                @if($order->notes)
                                <div class="col-md-6">
                                    <div class="card card-info">
                                        <div class="card-header">
                                            <h3 class="card-title"><i class="fas fa-sticky-note"></i> Notes</h3>
                                        </div>
                                        <div class="card-body">
                                            <p class="mb-0">{{ nl2br(e($order->notes)) }}</p>
                                        </div>
                                    </div>
                                </div>
                                @endif
                                @if($order->terms_conditions)
                                <div class="col-md-6">
                                    <div class="card card-warning">
                                        <div class="card-header">
                                            <h3 class="card-title"><i class="fas fa-file-contract"></i> Terms & Conditions</h3>
                                        </div>
                                        <div class="card-body">
                                            <p class="mb-0">{{ nl2br(e($order->terms_conditions)) }}</p>
                                        </div>
                                    </div>
                                </div>
                                @endif
                            </div>
                            @endif

                            <!-- Approval History -->
                            @if($order->approvals && $order->approvals->count() > 0)
                            <div class="card card-secondary mt-3">
                                <div class="card-header">
                                    <h3 class="card-title"><i class="fas fa-history"></i> Approval History</h3>
                                </div>
                                <div class="card-body p-0">
                                    <table class="table table-sm mb-0">
                                        <thead>
                                            <tr>
                                                <th>Approver</th>
                                                <th>Status</th>
                                                <th>Comments</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($order->approvals as $approval)
                                            <tr>
                                                <td>{{ $approval->user->name ?? 'N/A' }}</td>
                                                <td>
                                                    <span class="badge badge-{{ $approval->status === 'approved' ? 'success' : ($approval->status === 'rejected' ? 'danger' : 'warning') }}">
                                                        {{ ucfirst($approval->status) }}
                                                    </span>
                                                </td>
                                                <td>{{ $approval->comments ?? '-' }}</td>
                                                <td>{{ $approval->created_at->format('d M Y H:i') }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('scripts')
    <script>
        function confirmApproval(orderId, action) {
            let actionTitle, actionMessage, confirmText, icon, routeUrl, confirmColor;
            
            if (action === 'approve') {
                actionTitle = 'Approve Sales Order';
                actionMessage = 'Are you sure you want to approve this Sales Order?';
                confirmText = 'Yes, approve it!';
                icon = 'question';
                routeUrl = '{{ route('sales-orders.approve', ':id') }}'.replace(':id', orderId);
                confirmColor = '#3085d6';
            } else if (action === 'confirm') {
                actionTitle = 'Confirm Sales Order';
                actionMessage = 'Are you sure you want to confirm this Sales Order? This will make it ready for delivery.';
                confirmText = 'Yes, confirm it!';
                icon = 'question';
                routeUrl = '{{ route('sales-orders.confirm', ':id') }}'.replace(':id', orderId);
                confirmColor = '#28a745';
            } else if (action === 'close') {
                actionTitle = 'Close Sales Order';
                actionMessage = 'Are you sure you want to close this Sales Order?';
                confirmText = 'Yes, close it!';
                icon = 'warning';
                routeUrl = '{{ route('sales-orders.close', ':id') }}'.replace(':id', orderId);
                confirmColor = '#f39c12';
            }

            Swal.fire({
                title: actionTitle,
                text: actionMessage,
                icon: icon,
                showCancelButton: true,
                confirmButtonColor: confirmColor,
                cancelButtonColor: '#6c757d',
                confirmButtonText: confirmText,
                cancelButtonText: 'Cancel',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = routeUrl;

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
