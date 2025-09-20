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
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <a href="{{ route('purchase-orders.create') }}" class="btn btn-primary btn-sm">Create</a>
                    </div>
                    <form class="form-inline" id="filters">
                        <input type="date" name="from" class="form-control form-control-sm mr-1" placeholder="From">
                        <input type="date" name="to" class="form-control form-control-sm mr-1" placeholder="To">
                        <input type="text" name="q" class="form-control form-control-sm mr-1" placeholder="Search">
                        <select name="status" class="form-control form-control-sm mr-1">
                            <option value="">Status</option>
                            <option value="draft">Draft</option>
                            <option value="approved">Approved</option>
                            <option value="closed">Closed</option>
                        </select>
                        <select name="closure_status" class="form-control form-control-sm mr-1">
                            <option value="">Closure Status</option>
                            <option value="open">Open</option>
                            <option value="closed">Closed</option>
                        </select>
                        <button class="btn btn-sm btn-secondary" type="submit">Apply</button>
                        <a class="btn btn-sm btn-outline-secondary ml-1" id="csv" href="#">CSV</a>
                    </form>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped" id="tbl-po">
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
            $('#csv').on('click', function(e) {
                e.preventDefault();
                this.href = '{{ route('purchase-orders.csv') }}?' + $('#filters').serialize();
                window.location = this.href;
            });
        });
    </script>
@endsection
