@extends('layouts.main')

@section('title_page')
    Permissions
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">Permissions</li>
@endsection

@section('content')
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Permissions</h3>
                <div class="card-tools">
                    <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#permModal"><i
                            class="fas fa-plus"></i> New</button>
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
                <table class="table table-bordered table-striped table-sm" id="perms-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th style="width:120px;"></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="permModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form method="POST" id="permForm" action="{{ route('admin.permissions.store') }}">
                @csrf
                <input type="hidden" name="_method" id="permFormMethod" value="POST" />
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Permission</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group"><label>Name</label><input class="form-control" name="name" id="perm_name"
                                required></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(function() {
            var table = $('#perms-table').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: '{{ route('admin.permissions.data') }}',
                columns: [{
                        data: 'id',
                        name: 'id'
                    },
                    {
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false
                    },
                ]
            });

            $('#permModal').on('show.bs.modal', function(e) {
                var btn = $(e.relatedTarget);
                if (btn && btn.hasClass('edit-perm')) return;
                $('#permForm').attr('action', '{{ route('admin.permissions.store') }}');
                $('#permFormMethod').val('POST');
                $('#perm_name').val('');
            });

            $('#perms-table').on('click', '.edit-perm', function() {
                var id = $(this).data('id');
                var row = table.row($(this).closest('tr')).data();
                $('#permModal').modal('show');
                $('#permForm').attr('action', '/admin/permissions/' + id);
                $('#permFormMethod').val('PATCH');
                $('#perm_name').val(row.name);
            });

            $('#perms-table').on('click', '.delete-perm', function() {
                var id = $(this).data('id');
                Swal.fire({
                    title: 'Delete permission?',
                    icon: 'warning',
                    showCancelButton: true
                }).then((res) => {
                    if (res.isConfirmed) {
                        var f = $('<form method="POST" action="/admin/permissions/' + id +
                            '">@csrf<input type="hidden" name="_method" value="DELETE"></form>');
                        $('body').append(f);
                        f.submit();
                    }
                })
            });
        });
    </script>
@endsection
