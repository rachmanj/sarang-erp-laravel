@extends('layouts.main')

@section('title', 'Delivery Orders')

@section('title_page')
    Delivery Orders
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">Delivery Orders</li>
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
                                Delivery Orders
                            </h3>
                            <a href="{{ route('delivery-orders.create') }}" class="btn btn-sm btn-primary float-right">
                                <i class="fas fa-plus"></i> Create Delivery Order
                            </a>
                        </div>
                        <div class="card-body">
                            <!-- Filters -->
                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <select class="form-control form-control-sm" id="status-filter">
                                        <option value="">All Status</option>
                                        <option value="draft">Draft</option>
                                        <option value="picking">Picking</option>
                                        <option value="packed">Packed</option>
                                        <option value="ready">Ready</option>
                                        <option value="in_transit">In Transit</option>
                                        <option value="delivered">Delivered</option>
                                        <option value="completed">Completed</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <select class="form-control form-control-sm" id="customer-filter">
                                        <option value="">All Customers</option>
                                        @foreach ($customers as $customer)
                                            <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <input type="date" class="form-control form-control-sm" id="date-from"
                                        placeholder="From Date">
                                </div>
                                <div class="col-md-2">
                                    <input type="date" class="form-control form-control-sm" id="date-to"
                                        placeholder="To Date">
                                </div>
                                <div class="col-md-2">
                                    <button class="btn btn-sm btn-secondary" onclick="applyFilters()">
                                        <i class="fas fa-filter"></i> Filter
                                    </button>
                                </div>
                            </div>

                            <!-- DataTable -->
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" id="delivery-orders-table">
                                    <thead>
                                        <tr>
                                            <th>DO Number</th>
                                            <th>Sales Order</th>
                                            <th>Customer</th>
                                            <th>Planned Delivery</th>
                                            <th>Status</th>
                                            <th>Approval</th>
                                            <th>Created By</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            var table = $('#delivery-orders-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('delivery-orders.data') }}",
                    data: function(d) {
                        d.status = $('#status-filter').val();
                        d.customer_id = $('#customer-filter').val();
                        d.date_from = $('#date-from').val();
                        d.date_to = $('#date-to').val();
                    }
                },
                columns: [{
                        data: 'do_number',
                        name: 'do_number'
                    },
                    {
                        data: 'sales_order_no',
                        name: 'sales_order_no'
                    },
                    {
                        data: 'customer',
                        name: 'customer'
                    },
                    {
                        data: 'planned_delivery_date',
                        name: 'planned_delivery_date'
                    },
                    {
                        data: 'status',
                        name: 'status',
                        render: function(data, type, row) {
                            var badgeClass = getStatusBadgeClass(data);
                            return '<span class="badge badge-' + badgeClass + '">' + data.charAt(0)
                                .toUpperCase() + data.slice(1).replace('_', ' ') + '</span>';
                        }
                    },
                    {
                        data: 'approval_status',
                        name: 'approval_status',
                        render: function(data, type, row) {
                            var badgeClass = getApprovalBadgeClass(data);
                            return '<span class="badge badge-' + badgeClass + '">' + data.charAt(0)
                                .toUpperCase() + data.slice(1) + '</span>';
                        }
                    },
                    {
                        data: 'created_by',
                        name: 'created_by'
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false
                    }
                ],
                order: [
                    [0, 'desc']
                ],
                pageLength: 25
            });

            function getStatusBadgeClass(status) {
                switch (status) {
                    case 'draft':
                        return 'secondary';
                    case 'picking':
                        return 'warning';
                    case 'packed':
                        return 'info';
                    case 'ready':
                        return 'primary';
                    case 'in_transit':
                        return 'warning';
                    case 'delivered':
                        return 'success';
                    case 'completed':
                        return 'success';
                    case 'cancelled':
                        return 'danger';
                    default:
                        return 'secondary';
                }
            }

            function getApprovalBadgeClass(status) {
                switch (status) {
                    case 'pending':
                        return 'warning';
                    case 'approved':
                        return 'success';
                    case 'rejected':
                        return 'danger';
                    default:
                        return 'secondary';
                }
            }

            window.applyFilters = function() {
                table.ajax.reload();
            };
        });
    </script>
@endpush
