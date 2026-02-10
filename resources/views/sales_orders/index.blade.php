@extends('layouts.main')

@section('title_page')
    Sales Orders
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">Sales Orders</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                    <div>
                        <a href="{{ route('sales-orders.create') }}" class="btn btn-primary btn-sm">Create</a>
                    </div>
                    <form class="form-inline align-items-end flex-wrap" id="filters">
                        <div class="form-group mr-1 mb-1">
                            <label class="mr-1 small mb-0">From</label>
                            <input type="date" name="from" class="form-control form-control-sm" placeholder="From">
                        </div>
                        <div class="form-group mr-1 mb-1">
                            <label class="mr-1 small mb-0">To</label>
                            <input type="date" name="to" class="form-control form-control-sm" placeholder="To">
                        </div>
                        <div class="form-group mr-1 mb-1">
                            <label class="mr-1 small mb-0">Order No</label>
                            <input type="text" name="order_no" class="form-control form-control-sm" placeholder="Order no">
                        </div>
                        <div class="form-group mr-1 mb-1">
                            <label class="mr-1 small mb-0">Reference No</label>
                            <input type="text" name="reference_no" class="form-control form-control-sm" placeholder="Reference no">
                        </div>
                        <div class="form-group mr-1 mb-1">
                            <label class="mr-1 small mb-0">Customer</label>
                            <select name="business_partner_id" class="form-control form-control-sm select2bs4" style="min-width:180px">
                                <option value="">All Customers</option>
                                @foreach($customers as $bp)
                                    <option value="{{ $bp->id }}">{{ $bp->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group mr-1 mb-1">
                            <label class="mr-1 small mb-0">Search</label>
                            <input type="text" name="q" class="form-control form-control-sm" placeholder="Order no, Ref, Customer, Desc">
                        </div>
                        <div class="form-group mr-1 mb-1">
                            <label class="mr-1 small mb-0">Status</label>
                            <select name="status" class="form-control form-control-sm">
                                <option value="">All Statuses</option>
                                <option value="draft">Draft</option>
                                <option value="ordered">Ordered</option>
                                <option value="confirmed">Confirmed</option>
                                <option value="partial">Partial</option>
                                <option value="delivered">Delivered</option>
                                <option value="closed">Closed</option>
                                <option value="processing">Processing</option>
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </div>
                        <div class="form-group mb-1">
                            <button class="btn btn-sm btn-secondary" type="submit"><i class="fas fa-filter"></i> Apply</button>
                            <a class="btn btn-sm btn-outline-secondary ml-1" id="csv" href="#"><i class="fas fa-download"></i> CSV</a>
                        </div>
                    </form>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped" id="tbl-so">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Date</th>
                                <th>Order No</th>
                                <th>Reference No</th>
                                <th>Customer</th>
                                <th>Total</th>
                                <th>Status</th>
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
            $('.select2bs4').select2({ theme: 'bootstrap4', placeholder: 'Select', allowClear: true });
            const table = $('#tbl-so').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('sales-orders.data') }}',
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
                        data: 'order_no'
                    },
                    {
                        data: 'reference_no'
                    },
                    {
                        data: 'customer'
                    },
                    {
                        data: 'total_amount',
                        className: 'text-right'
                    },
                    {
                        data: 'status'
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
            $('#csv').on('click', function(e) {
                e.preventDefault();
                this.href = '{{ route('sales-orders.csv') }}?' + $('#filters').serialize();
                window.location = this.href;
            });
        });
    </script>
@endsection
