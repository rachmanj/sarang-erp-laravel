@extends('layouts.main')

@section('title', 'Sales Receipts')

@section('title_page')
    Sales Receipts
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">Sales Receipts</li>
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
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h3 class="card-title">Sales Receipts</h3>
                            <div>
                                <div class="d-inline-block mr-2">
                                    <label class="mr-1 small mb-0">Entity:</label>
                                    <div class="form-check form-check-inline d-inline">
                                        <input class="form-check-input" type="radio" name="entity_filter" id="entity-all" value="" checked>
                                        <label class="form-check-label" for="entity-all">All</label>
                                    </div>
                                    @if ($ptCahaya ?? null)
                                    <div class="form-check form-check-inline d-inline">
                                        <input class="form-check-input" type="radio" name="entity_filter" id="entity-pt" value="{{ $ptCahaya->id }}">
                                        <label class="form-check-label" for="entity-pt">PT Cahaya Sarange Jaya</label>
                                    </div>
                                    @endif
                                    @if ($cvCahaya ?? null)
                                    <div class="form-check form-check-inline d-inline">
                                        <input class="form-check-input" type="radio" name="entity_filter" id="entity-cv" value="{{ $cvCahaya->id }}">
                                        <label class="form-check-label" for="entity-cv">CV Cahaya Saranghae</label>
                                    </div>
                                    @endif
                                </div>
                                <input type="date" id="filter_from" class="form-control form-control-sm d-inline-block"
                                    style="width:160px">
                                <input type="date" id="filter_to" class="form-control form-control-sm d-inline-block"
                                    style="width:160px">
                                <input type="text" id="filter_q" class="form-control form-control-sm d-inline-block"
                                    style="width:200px" placeholder="Search...">
                                <select id="filter_status" class="form-control form-control-sm d-inline-block"
                                    style="width:140px">
                                    <option value="">All</option>
                                    <option value="draft">Draft</option>
                                    <option value="posted">Posted</option>
                                </select>
                                <button id="apply_filters" class="btn btn-sm btn-info">Apply</button>
                                @can('ar.receipts.create')
                                    <a href="{{ route('sales-receipts.create') }}" class="btn btn-sm btn-primary">Create</a>
                                @endcan
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-bordered table-striped table-sm mb-0" id="sr-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Receipt No</th>
                                        <th>Customer</th>
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
                var table = $('#sr-table').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: '{{ route('sales-receipts.data') }}',
                        data: function(d) {
                            d.from = $('#filter_from').val();
                            d.to = $('#filter_to').val();
                            d.q = $('#filter_q').val();
                            d.status = $('#filter_status').val();
                        }
                    },
                    columns: [{
                            data: 'date',
                            name: 'sr.date'
                        },
                        {
                            data: 'receipt_no',
                            name: 'sr.receipt_no'
                        },
                        {
                            data: 'customer',
                            name: 'c.name',
                            orderable: false
                        },
                        {
                            data: 'total_amount',
                            name: 'sr.total_amount',
                            className: 'text-right',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'status',
                            name: 'sr.status'
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
            });
        </script>
    @endpush
