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
                    <div class="card card-outline card-primary">
                        <div class="card-header">
                            <div class="d-flex flex-wrap justify-content-between align-items-center">
                                <h3 class="card-title mb-2 mb-md-0">
                                    <i class="fas fa-truck mr-1"></i>
                                    Delivery Orders
                                </h3>
                                <div class="d-flex flex-wrap align-items-center">
                                    <a href="{{ route('delivery-orders.create') }}" class="btn btn-sm btn-primary mb-1">
                                        <i class="fas fa-plus mr-1"></i>Create
                                    </a>
                                </div>
                            </div>

                            <div class="border-top pt-3 mt-1">
                                <div class="d-flex flex-wrap align-items-end">
                                    <x-document-index-filter-group label="Entity">
                                        <x-entity-filter-buttons />
                                    </x-document-index-filter-group>

                                    <x-document-index-filter-group label="Completion">
                                        @include('components.open-closed-filter')
                                    </x-document-index-filter-group>

                                    <x-document-index-filter-group label="Period" for="date-from">
                                        <div class="d-flex align-items-center">
                                            <input type="date" class="form-control form-control-sm" id="date-from" style="width:150px">
                                            <span class="text-muted mx-1">–</span>
                                            <input type="date" class="form-control form-control-sm" id="date-to" style="width:150px">
                                        </div>
                                    </x-document-index-filter-group>

                                    <x-document-index-filter-group label="Customer" for="customer-filter">
                                        <select class="form-control form-control-sm" id="customer-filter" style="width:180px">
                                            <option value="">Any</option>
                                            @foreach ($customers as $customer)
                                                <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                            @endforeach
                                        </select>
                                    </x-document-index-filter-group>

                                    <x-document-index-filter-group label="Customer Ref" for="customer-ref-filter">
                                        <input type="text" class="form-control form-control-sm" id="customer-ref-filter"
                                            style="width:140px" placeholder="Ref no">
                                    </x-document-index-filter-group>

                                    <x-document-index-filter-group label="Status" for="status-filter">
                                        <select class="form-control form-control-sm" id="status-filter" style="width:140px">
                                            <option value="">Any</option>
                                            <option value="draft">Draft</option>
                                            <option value="picking">Picking</option>
                                            <option value="packed">Packed</option>
                                            <option value="ready">Ready</option>
                                            <option value="in_transit">In Transit</option>
                                            <option value="delivered">Delivered</option>
                                            <option value="completed">Completed</option>
                                            <option value="cancelled">Cancelled</option>
                                        </select>
                                    </x-document-index-filter-group>

                                    <x-document-index-filter-group label="&nbsp;">
                                        <button type="button" class="btn btn-sm btn-info" onclick="applyFilters()">
                                            <i class="fas fa-filter mr-1"></i>Apply
                                        </button>
                                    </x-document-index-filter-group>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped table-sm mb-0" id="delivery-orders-table">
                                    <thead>
                                        <tr>
                                            <th>DO Number</th>
                                            <th>Sales Order</th>
                                            <th>Customer</th>
                                            <th>Customer Ref No</th>
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
                        d.customer_ref_no = $('#customer-ref-filter').val();
                        var entityVal = $('input[name="entity_filter"]:checked').val();
                        if (entityVal) d.company_entity_id = entityVal;
                        d.open_state = $('input[name="open_state"]:checked').val() || 'open';
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
                        data: 'customer_ref_no',
                        name: 'customer_ref_no'
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

            $('input[name="entity_filter"]').on('change', function() {
                table.ajax.reload();
            });
            $('input[name="open_state"]').on('change', function() {
                table.ajax.reload();
            });
        });
    </script>
@endpush
