@extends('layouts.main')

@section('title', 'Roles Management')

@section('title_page')
    Roles Management
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Admin</a></li>
    <li class="breadcrumb-item active">Roles</li>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                            {{ session('success') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                            {{ session('error') }}
                        </div>
                    @endif

                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-user-shield mr-1"></i>
                                Roles Management
                            </h3>
                            <div class="card-tools">
                                @can('roles.create')
                                    <a href="{{ route('admin.roles.create') }}" class="btn btn-primary btn-sm">
                                        <i class="fas fa-plus"></i>
                                        Add New Role
                                    </a>
                                @endcan
                            </div>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered table-striped" id="tbl-roles">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Name</th>
                                        <th>Permissions</th>
                                        <th>Users Count</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {{-- DataTables will populate this --}}
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
        $(function() {
            $('#tbl-roles').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('admin.roles.data') }}',
                columns: [{
                        data: 'id',
                        name: 'id',
                        orderable: true,
                        searchable: false
                    },
                    {
                        data: 'name',
                        name: 'name'
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
                                    html += '<span class="badge badge-secondary">+' +
                                        (permissions.length - maxDisplay) + ' more</span>';
                                }

                                return html;
                            }
                            return data;
                        }
                    },
                    {
                        data: 'users_count',
                        name: 'users_count',
                        render: function(data, type, row) {
                            return '<span class="badge badge-success">' + (data || 0) + '</span>';
                        }
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false,
                        render: function(data, type, row) {
                            var html = '';
                            html += '<a href="/admin/roles/' + row.id +
                                '" class="btn btn-sm btn-info mr-1" title="View">';
                            html += '<i class="fas fa-eye"></i></a>';

                            html += '<a href="/admin/roles/' + row.id +
                                '/edit" class="btn btn-sm btn-warning mr-1" title="Edit">';
                            html += '<i class="fas fa-edit"></i></a>';

                            // Don't show delete for superadmin
                            if (row.name !== 'superadmin') {
                                html +=
                                    '<button class="btn btn-sm btn-danger delete-role" data-id="' +
                                    row.id + '" title="Delete">';
                                html += '<i class="fas fa-trash"></i></button>';
                            }

                            return html;
                        }
                    }
                ],
                "responsive": true,
                "lengthChange": false,
                "autoWidth": false
            });

            // Delete role functionality
            $('#tbl-roles').on('click', '.delete-role', function() {
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
        });
    </script>
@endpush
