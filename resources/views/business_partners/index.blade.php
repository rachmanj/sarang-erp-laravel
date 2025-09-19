@extends('layouts.main')

@section('title_page')
    Business Partners
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">Business Partners</li>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <!-- Statistics Cards -->
            <div class="row">
                <div class="col-lg-2 col-6">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3>{{ $statistics['total'] }}</h3>
                            <p>Total Partners</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-handshake"></i>
                        </div>
                        <a href="{{ route('business_partners.index') }}" class="small-box-footer">
                            View All <i class="fas fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>
                <div class="col-lg-2 col-6">
                    <div class="small-box bg-primary">
                        <div class="inner">
                            <h3>{{ $statistics['customers'] }}</h3>
                            <p>Customers</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <a href="{{ route('business_partners.index', ['type' => 'customer']) }}" class="small-box-footer">
                            View Customers <i class="fas fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>
                <div class="col-lg-2 col-6">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3>{{ $statistics['suppliers'] }}</h3>
                            <p>Suppliers</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-truck"></i>
                        </div>
                        <a href="{{ route('business_partners.index', ['type' => 'supplier']) }}" class="small-box-footer">
                            View Suppliers <i class="fas fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>
                <div class="col-lg-2 col-6">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3>{{ $statistics['both'] }}</h3>
                            <p>Both Types</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-sync-alt"></i>
                        </div>
                        <a href="{{ route('business_partners.index', ['type' => 'both']) }}" class="small-box-footer">
                            View Both <i class="fas fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>
                <div class="col-lg-2 col-6">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3>{{ $statistics['active'] }}</h3>
                            <p>Active</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <a href="#" class="small-box-footer">
                            &nbsp;
                        </a>
                    </div>
                </div>
                <div class="col-lg-2 col-6">
                    <div class="small-box bg-secondary">
                        <div class="inner">
                            <h3>{{ $statistics['inactive'] }}</h3>
                            <p>Inactive</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <a href="#" class="small-box-footer">
                            &nbsp;
                        </a>
                    </div>
                </div>
            </div>

            <!-- Main content -->
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Business Partners</h3>
                    <div class="card-tools">
                        <div class="btn-group">
                            <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                                <i class="fas fa-filter"></i> Filter by Type
                            </button>
                            <div class="dropdown-menu">
                                <a class="dropdown-item {{ $type == 'all' ? 'active' : '' }}"
                                    href="{{ route('business_partners.index', ['type' => 'all']) }}">All Types</a>
                                <a class="dropdown-item {{ $type == 'customer' ? 'active' : '' }}"
                                    href="{{ route('business_partners.index', ['type' => 'customer']) }}">Customers</a>
                                <a class="dropdown-item {{ $type == 'supplier' ? 'active' : '' }}"
                                    href="{{ route('business_partners.index', ['type' => 'supplier']) }}">Suppliers</a>
                                <a class="dropdown-item {{ $type == 'both' ? 'active' : '' }}"
                                    href="{{ route('business_partners.index', ['type' => 'both']) }}">Both Types</a>
                            </div>
                        </div>
                        @can('business_partners.manage')
                            <a href="{{ route('business_partners.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Add New
                            </a>
                        @endcan
                    </div>
                </div>
                <div class="card-body">
                    <table id="business-partners-table" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Primary Contact</th>
                                <th>Primary Address</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Data will be loaded via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('styles')
    <!-- DataTables -->
    <link rel="stylesheet" href="{{ asset('plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
@endpush

@push('scripts')
    <!-- DataTables -->
    <script src="{{ asset('plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>

    <script>
        $(function() {
            $('#business-partners-table').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: {
                    url: "{{ route('business_partners.data') }}",
                    data: function(d) {
                        d.type = "{{ $type }}";
                    }
                },
                columns: [{
                        data: 'code',
                        name: 'code'
                    },
                    {
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'partner_type_badge',
                        name: 'partner_type',
                        searchable: false
                    },
                    {
                        data: 'status_badge',
                        name: 'status',
                        searchable: false
                    },
                    {
                        data: 'primary_contact',
                        name: 'primary_contact',
                        searchable: false
                    },
                    {
                        data: 'primary_address',
                        name: 'primary_address',
                        searchable: false
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false
                    }
                ],
                order: [
                    [0, 'asc']
                ]
            });
        });
    </script>
@endpush
