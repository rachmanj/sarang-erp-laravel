@extends('layouts.main')

@section('title_page')
    Purchase Orders
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">Purchase Orders</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <div class="d-flex flex-wrap justify-content-between align-items-center">
                        <h3 class="card-title mb-2 mb-md-0">
                            <i class="fas fa-file-invoice mr-1"></i>
                            Purchase Orders
                        </h3>
                        <div class="d-flex flex-wrap align-items-center">
                            <a href="{{ route('purchase-orders.create') }}" class="btn btn-sm btn-primary mr-1 mb-1">
                                <i class="fas fa-plus mr-1"></i>Create
                            </a>
                            <a class="btn btn-sm btn-success mb-1" id="csv" href="#">
                                <i class="fas fa-file-csv mr-1"></i>Export CSV
                            </a>
                        </div>
                    </div>

                    <div class="border-top pt-3 mt-1">
                        <form class="d-flex flex-wrap align-items-end" id="filters">
                            <x-document-index-filter-group label="Entity">
                                <x-entity-filter-buttons />
                            </x-document-index-filter-group>

                            <x-document-index-filter-group label="Completion">
                                @include('components.open-closed-filter')
                            </x-document-index-filter-group>

                            <x-document-index-filter-group label="Period">
                                <div class="d-flex align-items-center">
                                    <input type="date" name="from" class="form-control form-control-sm" style="width:150px">
                                    <span class="text-muted mx-1">–</span>
                                    <input type="date" name="to" class="form-control form-control-sm" style="width:150px">
                                </div>
                            </x-document-index-filter-group>

                            <x-document-index-filter-group label="Search" for="filter_q">
                                <input type="text" name="q" id="filter_q" class="form-control form-control-sm"
                                    style="width:220px" placeholder="Order no, vendor, description…">
                            </x-document-index-filter-group>

                            <x-document-index-filter-group label="Status" for="filter_status">
                                <select name="status" id="filter_status" class="form-control form-control-sm" style="width:140px">
                                    <option value="">Any</option>
                                    <option value="draft">Draft</option>
                                    <option value="approved">Approved</option>
                                    <option value="closed">Closed</option>
                                </select>
                            </x-document-index-filter-group>

                            <x-document-index-filter-group label="&nbsp;">
                                <button class="btn btn-sm btn-info" type="submit">
                                    <i class="fas fa-filter mr-1"></i>Apply
                                </button>
                            </x-document-index-filter-group>
                        </form>
                    </div>
                </div>
                <div class="card-body p-0">
                    <table class="table table-bordered table-striped table-sm mb-0" id="tbl-po">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Date</th>
                                <th>Order No</th>
                                <th>Vendor</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Closure Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(function() {
            const table = $('#tbl-po').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('purchase-orders.data') }}',
                    data: function(d) {
                        const f = $('#filters').serializeArray();
                        f.forEach(p => d[p.name] = p.value);
                        var entityVal = $('input[name="entity_filter"]:checked').val();
                        if (entityVal) d.company_entity_id = entityVal;
                        d.open_state = $('input[name="open_state"]:checked').val() || 'open';
                    }
                },
                columns: [{
                        data: 'id'
                    },
                    {
                        data: 'date'
                    },
                    {
                        data: 'order_no'
                    },
                    {
                        data: 'vendor'
                    },
                    {
                        data: 'total_amount'
                    },
                    {
                        data: 'status'
                    },
                    {
                        data: 'closure_status',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'actions',
                        orderable: false,
                        searchable: false
                    },
                ]
            });
            $('#filters').on('submit', function(e) {
                e.preventDefault();
                table.ajax.reload();
            });
            $('input[name="entity_filter"]').on('change', function() {
                table.ajax.reload();
            });
            $('input[name="open_state"]').on('change', function() {
                table.ajax.reload();
            });
            $('#csv').on('click', function(e) {
                e.preventDefault();
                var params = $('#filters').serialize();
                var entityVal = $('input[name="entity_filter"]:checked').val();
                if (entityVal) params += (params ? '&' : '') + 'company_entity_id=' + entityVal;
                this.href = '{{ route('purchase-orders.csv') }}?' + params;
                window.location = this.href;
            });
        });
    </script>
@endsection
