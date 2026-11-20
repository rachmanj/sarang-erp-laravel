@extends('layouts.main')

@section('title', 'Uang Muka Pembelian')

@section('title_page')
    Uang Muka Pembelian
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">Uang Muka Pembelian</li>
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
                                    <i class="fas fa-hand-holding-usd mr-1"></i>
                                    Uang Muka Pembelian
                                </h3>
                                <div class="d-flex flex-wrap align-items-center">
                                    @can('ap.payments.create')
                                        <a href="{{ route('purchase-advances.create') }}" class="btn btn-sm btn-primary mr-1 mb-1">
                                            <i class="fas fa-plus mr-1"></i>Buat
                                        </a>
                                    @endcan
                                </div>
                            </div>

                            <div class="border-top pt-3 mt-1">
                                <div class="d-flex flex-wrap align-items-end">
                                    <x-document-index-filter-group label="Entity">
                                        <x-entity-filter-buttons />
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
                                            style="width:220px" placeholder="No. dokumen, supplier…">
                                    </x-document-index-filter-group>

                                    <x-document-index-filter-group label="Status" for="filter_status">
                                        <select id="filter_status" class="form-control form-control-sm" style="width:140px">
                                            <option value="">Semua</option>
                                            <option value="draft">Draft</option>
                                            <option value="posted">Posted</option>
                                            <option value="cancelled">Cancelled</option>
                                        </select>
                                    </x-document-index-filter-group>

                                    <x-document-index-filter-group label="&nbsp;">
                                        <button id="apply_filters" type="button" class="btn btn-sm btn-info">
                                            <i class="fas fa-filter mr-1"></i>Terapkan
                                        </button>
                                    </x-document-index-filter-group>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-bordered table-striped table-sm mb-0" id="pa-table">
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>No. DP</th>
                                        <th>Supplier</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
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
                var table = $('#pa-table').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: '{{ route('purchase-advances.data') }}',
                        data: function(d) {
                            d.from = $('#filter_from').val();
                            d.to = $('#filter_to').val();
                            d.q = $('#filter_q').val();
                            d.status = $('#filter_status').val();
                            var entityVal = $('input[name="entity_filter"]:checked').val();
                            if (entityVal) d.company_entity_id = entityVal;
                        }
                    },
                    columns: [{
                            data: 'date',
                            name: 'pa.date'
                        },
                        {
                            data: 'advance_no',
                            name: 'pa.advance_no'
                        },
                        {
                            data: 'vendor',
                            name: 'v.name',
                            orderable: false
                        },
                        {
                            data: 'total_amount',
                            name: 'pa.total_amount',
                            className: 'text-right',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'status',
                            name: 'pa.status'
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