@extends('layouts.main')

@section('title_page')
    Sales Quotations
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">Sales Quotations</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <div class="d-flex flex-wrap justify-content-between align-items-center">
                        <h3 class="card-title mb-2 mb-md-0">
                            <i class="fas fa-file-alt mr-1"></i>
                            Sales Quotations
                        </h3>
                        <div class="d-flex flex-wrap align-items-center">
                            <a href="{{ route('sales-quotations.create') }}" class="btn btn-sm btn-primary mr-1 mb-1">
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

                            <x-document-index-filter-group label="Period">
                                <div class="d-flex align-items-center">
                                    <input type="date" name="from" class="form-control form-control-sm" style="width:150px">
                                    <span class="text-muted mx-1">–</span>
                                    <input type="date" name="to" class="form-control form-control-sm" style="width:150px">
                                </div>
                            </x-document-index-filter-group>

                            <x-document-index-filter-group label="Search" for="filter_q">
                                <input type="text" name="q" id="filter_q" class="form-control form-control-sm"
                                    style="width:220px" placeholder="Quotation no, customer…">
                            </x-document-index-filter-group>

                            <x-document-index-filter-group label="Status" for="filter_status">
                                <select name="status" id="filter_status" class="form-control form-control-sm" style="width:130px">
                                    <option value="">Any</option>
                                    <option value="draft">Draft</option>
                                    <option value="sent">Sent</option>
                                    <option value="accepted">Accepted</option>
                                    <option value="rejected">Rejected</option>
                                    <option value="expired">Expired</option>
                                    <option value="converted">Converted</option>
                                </select>
                            </x-document-index-filter-group>

                            <x-document-index-filter-group label="Approval" for="filter_approval">
                                <select name="approval_status" id="filter_approval" class="form-control form-control-sm" style="width:130px">
                                    <option value="">Any</option>
                                    <option value="pending">Pending</option>
                                    <option value="approved">Approved</option>
                                    <option value="rejected">Rejected</option>
                                </select>
                            </x-document-index-filter-group>

                            <x-document-index-filter-group label="Expiry" for="filter_expired">
                                <select name="expired" id="filter_expired" class="form-control form-control-sm" style="width:130px">
                                    <option value="">Any</option>
                                    <option value="yes">Expired</option>
                                    <option value="no">Not Expired</option>
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
                    <table class="table table-bordered table-striped table-sm mb-0" id="tbl-quotations">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Date</th>
                                <th>Quotation No</th>
                                <th>Customer</th>
                                <th>Valid Until</th>
                                <th>Total Amount</th>
                                <th>Net Amount</th>
                                <th>Status</th>
                                <th>Approval</th>
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
            const table = $('#tbl-quotations').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('sales-quotations.data') }}',
                    data: function(d) {
                        const f = $('#filters').serializeArray();
                        f.forEach(function(p) {
                            d[p.name] = p.value;
                        });
                        var entityVal = $('input[name="entity_filter"]:checked').val();
                        if (entityVal) d.company_entity_id = entityVal;
                    }
                },
                columns: [{
                        data: 'id'
                    },
                    {
                        data: 'date'
                    },
                    {
                        data: 'quotation_no'
                    },
                    {
                        data: 'customer'
                    },
                    {
                        data: 'valid_until_date',
                        orderable: false
                    },
                    {
                        data: 'total_amount',
                        orderable: false
                    },
                    {
                        data: 'net_amount',
                        orderable: false
                    },
                    {
                        data: 'status_badge',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'approval_badge',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'actions',
                        orderable: false,
                        searchable: false
                    },
                ],
                order: [[1, 'desc']]
            });
            $('#filters').on('submit', function(e) {
                e.preventDefault();
                table.ajax.reload();
            });
            $('input[name="entity_filter"]').on('change', function() {
                table.ajax.reload();
            });
            $('#csv').on('click', function(e) {
                e.preventDefault();
                var params = $('#filters').serialize();
                var entityVal = $('input[name="entity_filter"]:checked').val();
                if (entityVal) params += (params ? '&' : '') + 'company_entity_id=' + entityVal;
                this.href = '{{ route('sales-quotations.csv') }}?' + params;
                window.location = this.href;
            });
        });
    </script>
@endsection
