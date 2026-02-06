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
                        <button type="button" class="btn btn-sm btn-info mr-1"
                            onclick="showRelationshipMap('purchase-orders', {{ $order->id }})">
                            <i class="fas fa-sitemap"></i> Relationship Map
                        </button>
                        <a href="{{ route('purchase-orders.index') }}" class="btn btn-sm btn-secondary mr-1">
                            <i class="fas fa-arrow-left"></i> Back to Purchase Orders
                        </a>
                        @if ($order->status === 'draft')
                            @php
                                $canApprove = false;
                                if (auth()->user()->hasRole('superadmin')) {
                                    $canApprove = true;
                                } else {
                                    $pendingApproval = $order->approvals()
                                        ->where('user_id', auth()->id())
                                        ->where('status', 'pending')
                                        ->exists();
                                    $canApprove = $pendingApproval;
                                }
                            @endphp
                            @if ($canApprove)
                                <form method="post" action="{{ route('purchase-orders.approve', $order->id) }}" class="d-inline"
                                    data-confirm="Approve this Purchase Order?">
                                    @csrf
                                    <button class="btn btn-sm btn-primary" aria-label="Approve Purchase Order">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                </form>
                            @endif
                            <form method="post" action="{{ route('purchase-orders.destroy', $order->id) }}" class="d-inline"
                                data-confirm="Are you sure you want to delete this Purchase Order? This action cannot be undone.">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-danger" aria-label="Delete Purchase Order" type="submit">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </form>
                        @endif
                        @if ($order->status === 'ordered' || $order->status === 'approved')
                            <form method="post" action="{{ route('purchase-orders.close', $order->id) }}" class="d-inline"
                                data-confirm="Close this Purchase Order?">
                                @csrf
                                <button class="btn btn-sm btn-warning" aria-label="Close Purchase Order">
                                    <i class="fas fa-lock"></i> Close
                                </button>
                            </form>
                        @endif
                        @if ($order->status === 'ordered' && $order->order_type === 'item')
                            <a href="{{ route('purchase-orders.show-copy-to-grpo', $order->id) }}" class="btn btn-sm btn-success">
                                <i class="fas fa-copy"></i> Copy to GRPO
                            </a>
                        @endif
                        @if ($order->status === 'ordered' && $order->order_type === 'service')
                            <a href="{{ route('purchase-orders.show-copy-to-purchase-invoice', $order->id) }}" class="btn btn-sm btn-success">
                                <i class="fas fa-file-invoice"></i> Copy to Invoice
                            </a>
                        @endif
                        @can('assets.create')
                            <a href="{{ route('purchase-orders.create-assets', $order->id) }}" class="btn btn-sm btn-info"
                                aria-label="Create Assets from Purchase Order">Create Assets</a>
                        @endcan
                    </div>
                </div>
                <div class="card-body">
                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show">
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                            {{ session('error') }}
                        </div>
                    @endif
                    <div class="row mb-3">
                        <div class="col-md-3"><b>Date</b>
                            <div>{{ $order->date ? $order->date->format('d-M-Y') : '' }}</div>
                        </div>
                        <div class="col-md-3"><b>Vendor</b>
                            <div>{{ $order->businessPartner->name ?? '#' . $order->business_partner_id }}</div>
                        </div>
                        <div class="col-md-3"><b>Status</b>
                            <div>
                                <span class="badge badge-{{ $order->status === 'draft' ? 'secondary' : ($order->status === 'ordered' ? 'success' : 'info') }}">
                                    {{ strtoupper($order->status) }}
                                </span>
                                @if ($order->approval_status)
                                    <br><small class="text-muted">Approval: {{ ucfirst($order->approval_status) }}</small>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-3"><b>Total</b>
                            <div>Rp {{ number_format($order->total_amount, 2) }}</div>
                        </div>
                    </div>
                    @if ($order->approvals && $order->approvals->count() > 0)
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <b>Approval Status:</b>
                                <div class="mt-2">
                                    @foreach ($order->approvals as $approval)
                                        <div class="d-inline-block mr-3 mb-2">
                                            <span class="badge badge-{{ $approval->status === 'approved' ? 'success' : ($approval->status === 'rejected' ? 'danger' : 'warning') }}">
                                                {{ $approval->user->name ?? 'User #' . $approval->user_id }} 
                                                ({{ ucfirst($approval->approval_level) }}): 
                                                {{ ucfirst($approval->status) }}
                                            </span>
                                            @if ($approval->approved_at)
                                                <br><small class="text-muted">{{ $approval->approved_at->format('d-M-Y H:i') }}</small>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @elseif ($order->status === 'draft' && $order->approval_status === 'pending')
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> 
                            <strong>Approval Required:</strong> 
                            @php
                                $requiredRoles = \App\Models\ApprovalThreshold::getRequiredApprovals('purchase_order', $order->total_amount);
                            @endphp
                            @if (count($requiredRoles) > 0)
                                This Purchase Order (Amount: Rp {{ number_format($order->total_amount, 2) }}) requires approval from users with the following roles: 
                                <strong>{{ implode(', ', $requiredRoles) }}</strong>.
                                @if (auth()->user()->hasRole('superadmin'))
                                    <br><strong>As superadmin, you can approve this PO directly.</strong>
                                @else
                                    <br>Please contact a user with one of these roles to approve, or assign yourself the required role.
                                @endif
                            @else
                                Approval workflow is not configured for this amount range. Contact administrator.
                            @endif
                        </div>
                    @endif
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

    {{-- Include Relationship Map Modal --}}
    @include('components.relationship-map-modal')
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // Handle form submission with confirmation
            $('form[data-confirm]').on('submit', function(e) {
                const confirmText = $(this).data('confirm');
                if (!confirm(confirmText)) {
                    e.preventDefault();
                    return false;
                }
            });
        });
    </script>
@endpush
