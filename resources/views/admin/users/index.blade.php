@extends('layouts.main')

@section('title_page')
    Users
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">Users</li>
@endsection

@section('content')
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Users</h3>
                <div class="card-tools">
                    <a class="btn btn-primary btn-sm" href="{{ route('admin.users.create') }}"><i class="fas fa-plus"></i>
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
                <table class="table table-bordered table-striped table-sm" id="users-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Roles</th>
                            <th>Created</th>
                            <th style="width:120px;"></th>
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
            var table = $('#users-table').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: '{{ route('admin.users.data') }}',
                columns: [{
                        data: 'id',
                        name: 'id'
                    },
                    {
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'email',
                        name: 'email'
                    },
                    {
                        data: 'roles',
                        name: 'roles',
                        orderable: false,
                        searchable: false
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
                    },
                ]
            });

            // Delete
            $('#users-table').on('click', '.delete-user', function() {
                var id = $(this).data('id');
                Swal.fire({
                    title: 'Delete user?',
                    icon: 'warning',
                    showCancelButton: true
                }).then((res) => {
                    if (res.isConfirmed) {
                        var form = $('<form method="POST" action="/admin/users/' + id +
                            '">@csrf<input type="hidden" name="_method" value="DELETE"></form>');
                        $('body').append(form);
                        form.submit();
                    }
                })
            });
        });
    </script>
@endsection
