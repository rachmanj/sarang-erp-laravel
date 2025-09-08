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
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h3 class="card-title">Purchase Payments</h3>
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
                                @can('ap.payments.create')
                                    <a href="{{ route('purchase-payments.create') }}" class="btn btn-sm btn-primary">Create</a>
                                @endcan
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
            });
        </script>
    @endpush
