@extends('layouts.main')

@section('title', 'Sales Invoices')

@section('title_page')
    Sales Invoices
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">Sales Invoices</li>
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
                            <h3 class="card-title">Sales Invoices</h3>
                            <div>
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
                                @can('ar.invoices.create')
                                    <a href="{{ route('sales-invoices.create') }}" class="btn btn-sm btn-primary">Create</a>
                                @endcan
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-bordered table-striped table-sm mb-0" id="si-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Invoice No</th>
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
                var table = $('#si-table').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: '{{ route('sales-invoices.data') }}',
                        data: function(d) {
                            d.from = $('#filter_from').val();
                            d.to = $('#filter_to').val();
                            d.q = $('#filter_q').val();
                            d.status = $('#filter_status').val();
                        }
                    },
                    columns: [{
                            data: 'date',
                            name: 'si.date'
                        },
                        {
                            data: 'invoice_no',
                            name: 'si.invoice_no'
                        },
                        {
                            data: 'customer',
                            name: 'c.name',
                            orderable: false
                        },
                        {
                            data: 'total_amount',
                            name: 'si.total_amount',
                            className: 'text-right',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'status',
                            name: 'si.status'
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
            });
        </script>
    @endpush
