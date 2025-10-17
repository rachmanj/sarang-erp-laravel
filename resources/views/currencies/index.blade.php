@extends('layouts.app')

@section('title', 'Currencies')

@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Currencies</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item active">Currencies</li>
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
                            <h3 class="card-title">Currency Management</h3>
                            <div class="card-tools">
                                @can('currencies.create')
                                    <a href="{{ route('currencies.create') }}" class="btn btn-primary btn-sm">
                                        <i class="fas fa-plus"></i> Add Currency
                                    </a>
                                @endcan
                            </div>
                        </div>
                        <div class="card-body">
                            <table id="currencies-table" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Code</th>
                                        <th>Name</th>
                                        <th>Symbol</th>
                                        <th>Decimal Places</th>
                                        <th>Base Currency</th>
                                        <th>Status</th>
                                        <th>Created</th>
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
    </section>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            $('#currencies-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('currencies.data') }}",
                    type: 'GET'
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
                        data: 'symbol',
                        name: 'symbol'
                    },
                    {
                        data: 'decimal_places',
                        name: 'decimal_places'
                    },
                    {
                        data: 'is_base_currency',
                        name: 'is_base_currency',
                        render: function(data) {
                            return data ? '<span class="badge badge-success">Yes</span>' :
                                '<span class="badge badge-secondary">No</span>';
                        }
                    },
                    {
                        data: 'is_active',
                        name: 'is_active',
                        render: function(data) {
                            return data ? '<span class="badge badge-success">Active</span>' :
                                '<span class="badge badge-danger">Inactive</span>';
                        }
                    },
                    {
                        data: 'created_at',
                        name: 'created_at'
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
                ],
                pageLength: 25,
                responsive: true
            });
        });
    </script>
@endpush
