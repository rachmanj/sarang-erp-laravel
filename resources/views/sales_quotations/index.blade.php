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
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <a href="{{ route('sales-quotations.create') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Create Quotation
                        </a>
                    </div>
                    <form class="form-inline" id="filters">
                        <input type="date" name="from" class="form-control form-control-sm mr-1" placeholder="From">
                        <input type="date" name="to" class="form-control form-control-sm mr-1" placeholder="To">
                        <input type="text" name="q" class="form-control form-control-sm mr-1" placeholder="Search">
                        <select name="status" class="form-control form-control-sm mr-1">
                            <option value="">All Status</option>
                            <option value="draft">Draft</option>
                            <option value="sent">Sent</option>
                            <option value="accepted">Accepted</option>
                            <option value="rejected">Rejected</option>
                            <option value="expired">Expired</option>
                            <option value="converted">Converted</option>
                        </select>
                        <select name="approval_status" class="form-control form-control-sm mr-1">
                            <option value="">All Approval</option>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                        <select name="expired" class="form-control form-control-sm mr-1">
                            <option value="">All</option>
                            <option value="yes">Expired</option>
                            <option value="no">Not Expired</option>
                        </select>
                        <button class="btn btn-sm btn-secondary" type="submit">Apply</button>
                        <a class="btn btn-sm btn-outline-secondary ml-1" id="csv" href="#">CSV</a>
                    </form>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped" id="tbl-quotations">
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
            $('#csv').on('click', function(e) {
                e.preventDefault();
                this.href = '{{ route('sales-quotations.csv') }}?' + $('#filters').serialize();
                window.location = this.href;
            });
        });
    </script>
@endsection
