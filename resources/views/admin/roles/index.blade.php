@extends('layouts.main')

@section('title_page')
    Roles
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">Roles</li>
@endsection

@section('content')
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Roles</h3>
                <div class="card-tools">
                    <a class="btn btn-primary btn-sm" href="{{ route('admin.roles.create') }}"><i class="fas fa-plus"></i>
                        New</a>
                </div>
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
                <table class="table table-bordered table-striped table-sm" id="roles-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Permissions</th>
                            <th style="width:150px;"></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(function() {
            var table = $('#roles-table').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: '{{ route('admin.roles.data') }}',
                columns: [{
                        data: 'id',
                        name: 'id'
                    },
                    {
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'permissions',
                        name: 'permissions',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false
                    }
                ]
            });

            $('#roles-table').on('click', '.delete-role', function() {
                var id = $(this).data('id');
                Swal.fire({
                    title: 'Delete role?',
                    icon: 'warning',
                    showCancelButton: true
                }).then((res) => {
                    if (res.isConfirmed) {
                        var f = $('<form method="POST" action="/admin/roles/' + id +
                            '">@csrf<input type="hidden" name="_method" value="DELETE"></form>');
                        $('body').append(f);
                        f.submit();
                    }
                })
            });

            $('#roles-table').on('click', '.perms-role', function() {
                var id = $(this).data('id');
                $('#permsForm').attr('action', '/admin/roles/' + id + '/permissions');
                $('#permsModal').modal('show');
            });
        });
    </script>
@endsection
