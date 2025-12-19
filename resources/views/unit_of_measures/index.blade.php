@extends('layouts.main')

@section('title_page')
    Units of Measure
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">Units of Measure</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        @can('create_unit_of_measure')
                            <a href="{{ route('unit-of-measures.create') }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Add Unit
                            </a>
                        @endcan
                    </div>
                    <form class="form-inline" id="filters">
                        <select name="unit_type" class="form-control form-control-sm mr-1">
                            <option value="">All Unit Types</option>
                            <option value="count">Count</option>
                            <option value="weight">Weight</option>
                            <option value="length">Length</option>
                            <option value="volume">Volume</option>
                            <option value="area">Area</option>
                            <option value="time">Time</option>
                        </select>
                        <select name="status" class="form-control form-control-sm mr-1">
                            <option value="">All Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                        <button class="btn btn-sm btn-secondary" type="submit">
                            <i class="fas fa-search"></i> Filter
                        </button>
                    </form>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped" id="tbl-units">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Unit Type</th>
                                <th>Base Unit</th>
                                <th>Status</th>
                                <th>Conversions</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the unit <strong id="unitName"></strong>?</p>
                    <p class="text-danger"><small>This action cannot be undone.</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <form id="deleteForm" method="POST" style="display: inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(function() {
            const table = $('#tbl-units').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('unit-of-measures.data') }}',
                    data: function(d) {
                        const f = $('#filters').serializeArray();
                        f.forEach(p => d[p.name] = p.value);
                    }
                },
                columns: [{
                        data: 'code'
                    },
                    {
                        data: 'name'
                    },
                    {
                        data: 'description',
                        orderable: false
                    },
                    {
                        data: 'unit_type'
                    },
                    {
                        data: 'is_base_unit',
                        render: function(data) {
                            return data ? '<span class="badge badge-success">Base Unit</span>' : '<span class="text-muted">-</span>';
                        },
                        orderable: false
                    },
                    {
                        data: 'is_active',
                        render: function(data) {
                            return data ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-secondary">Inactive</span>';
                        }
                    },
                    {
                        data: 'conversions',
                        render: function(data) {
                            return data > 0 ? '<span class="badge badge-primary">' + data + ' conversion(s)</span>' : '<span class="text-muted">No conversions</span>';
                        },
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'actions',
                        orderable: false,
                        searchable: false
                    }
                ]
            });

            $('#filters').on('submit', function(e) {
                e.preventDefault();
                table.ajax.reload();
            });

            window.deleteUnit = function(unitId, unitName) {
                document.getElementById('unitName').textContent = unitName;
                document.getElementById('deleteForm').action = '/unit-of-measures/' + unitId;
                $('#deleteModal').modal('show');
            };

            $('#deleteForm').on('submit', function(e) {
                e.preventDefault();
                const formData = $(this).serialize();
                const action = $(this).attr('action');

                $.ajax({
                    url: action,
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        $('#deleteModal').modal('hide');
                        table.ajax.reload();
                        toastr.success('Unit deleted successfully');
                    },
                    error: function(xhr) {
                        const message = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Error deleting unit';
                        toastr.error(message);
                    }
                });
            });
        });
    </script>
@endsection
