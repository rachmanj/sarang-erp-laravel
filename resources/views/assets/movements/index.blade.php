@extends('layouts.main')

@section('title', 'Asset Movements')

@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Asset Movements</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('assets.index') }}">Assets</a></li>
                        <li class="breadcrumb-item active">Movements</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Asset Movements</h3>
                            <div class="card-tools">
                                @can('create', App\Models\AssetMovement::class)
                                    <a href="{{ route('assets.movements.create') }}" class="btn btn-primary btn-sm">
                                        <i class="fas fa-plus"></i> New Movement
                                    </a>
                                @endcan
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <select class="form-control select2bs4" id="status-filter">
                                        <option value="">All Status</option>
                                        <option value="draft">Draft</option>
                                        <option value="approved">Approved</option>
                                        <option value="completed">Completed</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <select class="form-control select2bs4" id="type-filter">
                                        <option value="">All Types</option>
                                        <option value="transfer">Transfer</option>
                                        <option value="relocation">Relocation</option>
                                        <option value="custodian_change">Custodian Change</option>
                                        <option value="maintenance">Maintenance</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <input type="date" class="form-control" id="date-from" placeholder="From Date">
                                </div>
                                <div class="col-md-2">
                                    <input type="date" class="form-control" id="date-to" placeholder="To Date">
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-info btn-block" id="apply-filters">
                                        <i class="fas fa-filter"></i> Filter
                                    </button>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table id="movements-table" class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Asset Code</th>
                                            <th>Name</th>
                                            <th>Category</th>
                                            <th>Movement Date</th>
                                            <th>Type</th>
                                            <th>From Location</th>
                                            <th>To Location</th>
                                            <th>From Custodian</th>
                                            <th>To Custodian</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            $('.select2bs4').select2({
                theme: 'bootstrap4',
                width: '100%'
            });

            var table = $('#movements-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('assets.movements.data') }}',
                    data: function(d) {
                        d.status = $('#status-filter').val();
                        d.movement_type = $('#type-filter').val();
                        d.date_from = $('#date-from').val();
                        d.date_to = $('#date-to').val();
                        d.search = d.search.value;
                    }
                },
                columns: [{
                        data: 'asset_code',
                        name: 'asset_code'
                    },
                    {
                        data: 'asset_name',
                        name: 'asset_name'
                    },
                    {
                        data: 'category_name',
                        name: 'category_name'
                    },
                    {
                        data: 'movement_date',
                        name: 'movement_date',
                        render: function(data) {
                            return data ? moment(data).format('DD/MM/YYYY') : '';
                        }
                    },
                    {
                        data: 'movement_type',
                        name: 'movement_type',
                        render: function(data) {
                            const types = {
                                'transfer': 'Transfer',
                                'relocation': 'Relocation',
                                'custodian_change': 'Custodian Change',
                                'maintenance': 'Maintenance',
                                'other': 'Other'
                            };
                            return types[data] || data;
                        }
                    },
                    {
                        data: 'from_location',
                        name: 'from_location',
                        render: function(data) {
                            return data || '-';
                        }
                    },
                    {
                        data: 'to_location',
                        name: 'to_location',
                        render: function(data) {
                            return data || '-';
                        }
                    },
                    {
                        data: 'from_custodian',
                        name: 'from_custodian',
                        render: function(data) {
                            return data || '-';
                        }
                    },
                    {
                        data: 'to_custodian',
                        name: 'to_custodian',
                        render: function(data) {
                            return data || '-';
                        }
                    },
                    {
                        data: 'status',
                        name: 'status',
                        render: function(data) {
                            const badges = {
                                'draft': '<span class="badge badge-warning">Draft</span>',
                                'approved': '<span class="badge badge-info">Approved</span>',
                                'completed': '<span class="badge badge-success">Completed</span>',
                                'cancelled': '<span class="badge badge-danger">Cancelled</span>'
                            };
                            return badges[data] || data;
                        }
                    },
                    {
                        data: 'id',
                        name: 'actions',
                        orderable: false,
                        searchable: false,
                        render: function(data, type, row) {
                            let actions = '';

                            actions += `<a href="/assets/movements/${data}" class="btn btn-info btn-sm" title="View">
                        <i class="fas fa-eye"></i>
                    </a> `;

                            if (row.status === 'draft') {
                                actions += `<a href="/assets/movements/${data}/edit" class="btn btn-warning btn-sm" title="Edit">
                            <i class="fas fa-edit"></i>
                        </a> `;
                            }

                            return actions;
                        }
                    }
                ],
                order: [
                    [3, 'desc']
                ],
                pageLength: 15,
                responsive: true,
                language: {
                    processing: "Loading...",
                    emptyTable: "No movements found",
                    zeroRecords: "No matching movements found"
                }
            });

            $('#apply-filters').click(function() {
                table.ajax.reload();
            });
        });
    </script>
@endsection
