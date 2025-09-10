@extends('layouts.main')

@section('title_page')
    Roles Management
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">Role</li>
@endsection

@section('content')
    <div class="container-fluid">
        <!-- Header Section -->
        <div class="row mb-3">
            <div class="col-md-6">
                <h1 class="h3 mb-0 text-gray-800">Roles Management</h1>
            </div>
            <div class="col-md-6 text-right">
                <a href="{{ route('admin.roles.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add New Role
                </a>
            </div>
        </div>

        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
                <li class="breadcrumb-item active">Role</li>
            </ol>
        </nav>

        <!-- Main Card -->
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Roles List</h6>
            </div>
            <div class="card-body">
                @if (session('success'))
                    <script>
                        toastr.success(@json(session('success')));
                    </script>
                @endif
                @if (session('error'))
                    <script>
                        toastr.error(@json(session('error')));
                    </script>
                @endif

                <!-- DataTable Controls -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="dataTables_length">
                            <label>Show
                                <select name="roles-table_length" aria-controls="roles-table"
                                    class="custom-select custom-select-sm form-control form-control-sm">
                                    <option value="10">10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select> entries
                            </label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="dataTables_filter text-right">
                            <label>Search:
                                <input type="search" class="form-control form-control-sm" placeholder=""
                                    aria-controls="roles-table">
                            </label>
                        </div>
                    </div>
                </div>

                <table class="table table-bordered table-striped" id="roles-table">
                    <thead class="thead-light">
                        <tr>
                            <th class="sorting" tabindex="0" aria-controls="roles-table" rowspan="1" colspan="1"
                                aria-label="ID: activate to sort column ascending">
                                ID <i class="fas fa-sort"></i>
                            </th>
                            <th class="sorting" tabindex="0" aria-controls="roles-table" rowspan="1" colspan="1"
                                aria-label="Name: activate to sort column ascending">
                                Name <i class="fas fa-sort"></i>
                            </th>
                            <th>Permissions</th>
                            <th>Users Count</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@section('styles')
    <style>
        .card {
            border: none;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }

        .card-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
        }

        .badge-info {
            background-color: #36b9cc;
            color: white;
            font-size: 0.875rem;
            padding: 0.5rem 0.75rem;
        }

        .badge-secondary {
            background-color: #858796;
            color: white;
            font-size: 0.875rem;
            padding: 0.5rem 0.75rem;
        }

        .badge-success {
            background-color: #1cc88a;
            color: white;
        }

        .table thead th {
            border-bottom: 2px solid #e3e6f0;
            font-weight: 600;
            color: #5a5c69;
        }

        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }

        .dataTables_length label,
        .dataTables_filter label {
            font-weight: 600;
            color: #5a5c69;
        }

        .breadcrumb {
            background-color: transparent;
            padding: 0;
            margin-bottom: 1rem;
        }

        .breadcrumb-item a {
            color: #007bff;
            text-decoration: none;
        }

        .breadcrumb-item.active {
            color: #6c757d;
        }
    </style>
@endsection

@section('scripts')
    <script>
        $(function() {
            var table = $('#roles-table').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                pageLength: 10,
                lengthMenu: [
                    [10, 25, 50, 100],
                    [10, 25, 50, 100]
                ],
                ajax: '{{ route('admin.roles.data') }}',
                columns: [{
                        data: 'id',
                        name: 'id',
                        className: 'text-center'
                    },
                    {
                        data: 'name',
                        name: 'name',
                        className: 'font-weight-bold'
                    },
                    {
                        data: 'permissions',
                        name: 'permissions',
                        orderable: false,
                        searchable: false,
                        render: function(data, type, row) {
                            if (type === 'display' && data) {
                                var permissions = data.split(',');
                                var html = '';
                                var maxDisplay = 3;

                                for (var i = 0; i < Math.min(permissions.length, maxDisplay); i++) {
                                    html += '<span class="badge badge-info mr-1 mb-1">' +
                                        permissions[i].trim() + '</span>';
                                }

                                if (permissions.length > maxDisplay) {
                                    html += '<span class="badge badge-secondary">+' + (permissions
                                        .length - maxDisplay) + ' more</span>';
                                }

                                return html;
                            }
                            return data;
                        }
                    },
                    {
                        data: 'users_count',
                        name: 'users_count',
                        className: 'text-center',
                        render: function(data, type, row) {
                            return '<span class="badge badge-success">' + (data || 0) + '</span>';
                        }
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false,
                        className: 'text-center',
                        render: function(data, type, row) {
                            var html = '';
                            html += '<button class="btn btn-sm btn-info mr-1 view-role" data-id="' +
                                row.id + '" title="View"><i class="fas fa-eye"></i></button>';
                            html +=
                                '<button class="btn btn-sm btn-warning mr-1 edit-role" data-id="' +
                                row.id + '" title="Edit"><i class="fas fa-edit"></i></button>';

                            // Don't show delete for superadmin
                            if (row.name !== 'superadmin') {
                                html +=
                                    '<button class="btn btn-sm btn-danger delete-role" data-id="' +
                                    row.id +
                                    '" title="Delete"><i class="fas fa-trash"></i></button>';
                            }

                            return html;
                        }
                    }
                ],
                language: {
                    processing: "Loading...",
                    lengthMenu: "Show _MENU_ entries",
                    zeroRecords: "No roles found",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    infoEmpty: "Showing 0 to 0 of 0 entries",
                    infoFiltered: "(filtered from _MAX_ total entries)",
                    search: "Search:",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Previous"
                    }
                },
                dom: 'rtip',
                drawCallback: function() {
                    // Update pagination info
                    var info = this.api().page.info();
                    $('.dataTables_info').html('Showing ' + (info.start + 1) + ' to ' + info.end +
                        ' of ' + info.recordsTotal + ' entries');
                }
            });

            // Delete role functionality
            $('#roles-table').on('click', '.delete-role', function() {
                var id = $(this).data('id');
                Swal.fire({
                    title: 'Delete Role?',
                    text: "You won't be able to revert this!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        var form = $('<form method="POST" action="/admin/roles/' + id + '">' +
                            '@csrf' +
                            '<input type="hidden" name="_method" value="DELETE">' +
                            '</form>');
                        $('body').append(form);
                        form.submit();
                    }
                });
            });

            // View role functionality
            $('#roles-table').on('click', '.view-role', function() {
                var id = $(this).data('id');
                window.location.href = '/admin/roles/' + id;
            });

            // Edit role functionality
            $('#roles-table').on('click', '.edit-role', function() {
                var id = $(this).data('id');
                window.location.href = '/admin/roles/' + id + '/edit';
            });

            // Permissions modal functionality
            $('#roles-table').on('click', '.perms-role', function() {
                var id = $(this).data('id');
                $('#permsForm').attr('action', '/admin/roles/' + id + '/permissions');
                $('#permsModal').modal('show');
            });
        });
    </script>
@endsection
