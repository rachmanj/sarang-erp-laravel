@extends('layouts.main')

@section('title_page')
    Departments
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">Departments</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">List</h4>
                    @can('departments.manage')
                        <button class="btn btn-sm btn-primary float-right" id="btnNew">Create</button>
                    @endcan
                </div>
                @if (session('success'))
                    <script>
                        toastr.success(@json(session('success')));
                    </script>
                @endif
                <div class="card-body">
                    <table class="table table-striped table-sm mb-0" id="depts-table">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deptModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Department</h5><button type="button" class="close" data-dismiss="modal"
                        aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <form id="deptForm" action="#" method="post">
                    <div class="modal-body">
                        <input type="hidden" id="dept_id">
                        <div class="form-group"><label>Code</label><input id="code" class="form-control" required>
                        </div>
                        <div class="form-group"><label>Name</label><input id="name" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer"><button type="submit" class="btn btn-primary">Save</button><button
                            type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button></div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(function() {
            const table = $('#depts-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('departments.data') }}',
                columns: [{
                        data: 'code',
                        name: 'code'
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
                    }
                ]
            });

            $('#btnNew').on('click', function() {
                $('#deptModal form')[0].reset();
                $('#dept_id').val('');
                $('#deptModal').modal('show');
                $('#deptModal form').attr('action', '{{ route('departments.store') }}').data('method',
                    'POST');
            });
            $(document).on('click', '.btn-edit', function() {
                const b = $(this);
                $('#dept_id').val(b.data('id'));
                $('#code').val(b.data('code'));
                $('#name').val(b.data('name'));
                $('#deptModal').modal('show');
                $('#deptModal form').attr('action', '{{ url('departments') }}/' + b.data('id')).data(
                    'method', 'PATCH');
            });
            $('#deptForm').on('submit', async function(e) {
                e.preventDefault();
                const form = $(this);
                const url = form.attr('action');
                const method = form.data('method') || 'POST';
                const payload = {
                    code: $('#code').val(),
                    name: $('#name').val(),
                    _token: '{{ csrf_token() }}'
                };

                try {
                    const response = await $.ajax({
                        url,
                        method,
                        data: payload,
                        headers: {
                            'Accept': 'application/json'
                        }
                    });

                    $('#deptModal').modal('hide');

                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.message || 'Department saved successfully',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: response.message || 'Failed to save department'
                        });
                    }

                    table.ajax.reload();
                } catch (e) {
                    let errorMessage = 'Failed to save department';

                    if (e.responseJSON) {
                        if (e.responseJSON.errors) {
                            // Handle validation errors
                            const errors = Object.values(e.responseJSON.errors).flat();
                            errorMessage = errors.join('<br>');
                        } else if (e.responseJSON.message) {
                            errorMessage = e.responseJSON.message;
                        }
                    }

                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        html: errorMessage
                    });
                }
            });
            $(document).on('click', '.btn-delete', async function() {
                const deleteUrl = $(this).data('url');

                const result = await Swal.fire({
                    title: 'Are you sure?',
                    text: "You won't be able to revert this!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel'
                });

                if (result.isConfirmed) {
                    try {
                        const response = await $.ajax({
                            url: deleteUrl,
                            method: 'DELETE',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            headers: {
                                'Accept': 'application/json'
                            }
                        });

                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: 'Department has been deleted successfully.',
                            timer: 2000,
                            showConfirmButton: false
                        });

                        table.ajax.reload();
                    } catch (e) {
                        let errorMessage = 'Failed to delete department';

                        if (e.responseJSON && e.responseJSON.message) {
                            errorMessage = e.responseJSON.message;
                        }

                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: errorMessage
                        });
                    }
                }
            });
        });
    </script>
@endsection
