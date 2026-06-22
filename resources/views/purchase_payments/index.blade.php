@extends('layouts.main')

@section('title', 'Purchase Payments')

@section('title_page')
    Purchase Payments
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">Purchase Payments</li>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    @if (session('success'))
                        <script>
                            toastr.success(@json(session('success')));
                        </script>
                    @endif
                    <div class="card card-outline card-primary">
                        <div class="card-header">
                            <div class="d-flex flex-wrap justify-content-between align-items-center">
                                <h3 class="card-title mb-2 mb-md-0">
                                    <i class="fas fa-money-check-alt mr-1"></i>
                                    Purchase Payments
                                </h3>
                                <div class="d-flex flex-wrap align-items-center">
                                    @can('ap.payments.create')
                                        <a href="{{ route('purchase-payments.create') }}" class="btn btn-sm btn-primary mr-1 mb-1">
                                            <i class="fas fa-plus mr-1"></i>Create
                                        </a>
                                    @endcan
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

                                    <x-document-index-filter-group label="Period" for="filter_from">
                                        <div class="d-flex align-items-center">
                                            <input type="date" id="filter_from" class="form-control form-control-sm" style="width:150px">
                                            <span class="text-muted mx-1">–</span>
                                            <input type="date" id="filter_to" class="form-control form-control-sm" style="width:150px">
                                        </div>
                                    </x-document-index-filter-group>

                                    <x-document-index-filter-group label="Search" for="filter_q">
                                        <input type="text" id="filter_q" class="form-control form-control-sm"
                                            style="width:220px" placeholder="Payment no, vendor…">
                                    </x-document-index-filter-group>

                                    <x-document-index-filter-group label="Posting" for="filter_status">
                                        <select id="filter_status" class="form-control form-control-sm" style="width:120px">
                                            <option value="">Any</option>
                                            <option value="draft">Draft</option>
                                            <option value="posted">Posted</option>
                                        </select>
                                    </x-document-index-filter-group>

                                    <x-document-index-filter-group label="&nbsp;">
                                        <button id="apply_filters" type="button" class="btn btn-sm btn-info">
                                            <i class="fas fa-filter mr-1"></i>Apply
                                        </button>
                                    </x-document-index-filter-group>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-bordered table-striped table-sm mb-0" id="pp-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Payment No</th>
                                        <th>Vendor</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                        <div class="card-footer"></div>
                    </div>
                </div>
            </div>
        </div>
    @endsection

    @push('scripts')
        <script>
            $(function() {
                var table = $('#pp-table').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: '{{ route('purchase-payments.data') }}',
                        data: function(d) {
                            d.from = $('#filter_from').val();
                            d.to = $('#filter_to').val();
                            d.q = $('#filter_q').val();
                            d.status = $('#filter_status').val();
                            d.open_state = $('input[name="open_state"]:checked').val() || 'open';
                            var entityVal = $('input[name="entity_filter"]:checked').val();
                            if (entityVal) d.company_entity_id = entityVal;
                        }
                    },
                    columns: [{
                            data: 'date',
                            name: 'pp.date'
                        },
                        {
                            data: 'payment_no',
                            name: 'pp.payment_no'
                        },
                        {
                            data: 'vendor',
                            name: 'v.name',
                            orderable: false
                        },
                        {
                            data: 'total_amount',
                            name: 'pp.total_amount',
                            className: 'text-right',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'status',
                            name: 'pp.status'
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
                    ]
                });
                $('#apply_filters').on('click', function() {
                    table.ajax.reload();
                });
                $('input[name="entity_filter"]').on('change', function() {
                    table.ajax.reload();
                });
                $('input[name="open_state"]').on('change', function() {
                    table.ajax.reload();
                });
            });
        </script>
    @endpush
