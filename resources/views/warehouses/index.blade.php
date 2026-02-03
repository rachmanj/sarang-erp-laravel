@extends('layouts.main')

@section('title_page')
    Warehouses
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">Warehouses</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        @can('warehouse.create')
                            <a href="{{ route('warehouses.create') }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i>
                                Create
                            </a>
                        @endcan
                        @can('warehouse.transfer')
                            <a href="{{ route('warehouses.transfer-page') }}" class="btn btn-success btn-sm ml-2">
                                <i class="fas fa-exchange-alt"></i>
                                Transfer Stock
                            </a>
                            <a href="{{ route('warehouses.transfer-history') }}" class="btn btn-info btn-sm ml-1">
                                <i class="fas fa-history"></i>
                                Transfer History
                            </a>
                            <a href="{{ route('warehouses.pending-transfers-page') }}" class="btn btn-warning btn-sm ml-1">
                                <i class="fas fa-clock"></i>
                                Pending Transfers
                            </a>
                        @endcan
                    </div>
                    <form class="form-inline" id="filters">
                        <input type="text" name="q" class="form-control form-control-sm mr-1" placeholder="Search">
                        <select name="status" class="form-control form-control-sm mr-1">
                            <option value="">Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                        <button class="btn btn-sm btn-secondary" type="submit">Apply</button>
                        <a class="btn btn-sm btn-outline-secondary ml-1" id="csv" href="#">CSV</a>
                    </form>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped" id="tbl-warehouses">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Address</th>
                                <th>Contact Person</th>
                                <th>Phone</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($warehouses as $warehouse)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td><strong>{{ $warehouse->code }}</strong></td>
                                    <td>{{ $warehouse->name }}</td>
                                    <td>{{ $warehouse->address ?? '-' }}</td>
                                    <td>{{ $warehouse->contact_person ?? '-' }}</td>
                                    <td>{{ $warehouse->phone ?? '-' }}</td>
                                    <td>{{ $warehouse->email ?? '-' }}</td>
                                    <td>
                                        @if ($warehouse->is_active)
                                            <span class="badge badge-success">Active</span>
                                        @else
                                            <span class="badge badge-danger">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            @can('inventory.view')
                                                <a href="{{ route('warehouses.show', $warehouse->id) }}"
                                                    class="btn btn-info btn-sm">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            @endcan
                                            @can('inventory.update')
                                                <a href="{{ route('warehouses.edit', $warehouse->id) }}"
                                                    class="btn btn-warning btn-sm">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            @endcan
                                            @can('inventory.delete')
                                                <button type="button" class="btn btn-danger btn-sm"
                                                    onclick="deleteWarehouse({{ $warehouse->id }})">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Form -->
    <form id="delete-form" method="POST" style="display: none;">
        @csrf
        @method('DELETE')
    </form>

    @include('components.warehouse-transfer-modal-enhanced')
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#tbl-warehouses').DataTable({
                "responsive": true,
                "lengthChange": false,
                "autoWidth": false,
                "pageLength": 25,
                "order": [
                    [1, 'asc']
                ],
                "columnDefs": [{
                    "orderable": false,
                    "targets": [8]
                }]
            });

            // Filter functionality
            $('#filters').on('submit', function(e) {
                e.preventDefault();
                var table = $('#tbl-warehouses').DataTable();
                var searchTerm = $('input[name="q"]').val();
                var statusFilter = $('select[name="status"]').val();

                table.search(searchTerm).draw();

                if (statusFilter) {
                    table.column(7).search(statusFilter).draw();
                } else {
                    table.column(7).search('').draw();
                }
            });

            // CSV Export
            $('#csv').on('click', function(e) {
                e.preventDefault();
                var table = $('#tbl-warehouses').DataTable();
                var data = table.data().toArray();
                var csv = convertToCSV(data);
                downloadCSV(csv, 'warehouses.csv');
            });
        });

        function deleteWarehouse(id) {
            if (confirm('Are you sure you want to delete this warehouse?')) {
                var form = document.getElementById('delete-form');
                form.action = '/warehouses/' + id;
                form.submit();
            }
        }

        function convertToCSV(data) {
            var csv = 'Code,Name,Address,Contact Person,Phone,Email,Status\n';
            data.forEach(function(row) {
                csv += '"' + row[1] + '","' + row[2] + '","' + row[3] + '","' + row[4] + '","' + row[5] + '","' +
                    row[6] + '","' + row[7] + '"\n';
            });
            return csv;
        }

        function downloadCSV(csv, filename) {
            var blob = new Blob([csv], {
                type: 'text/csv'
            });
            var url = window.URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = url;
            a.download = filename;
            a.click();
            window.URL.revokeObjectURL(url);
        }
    </script>
@endpush
