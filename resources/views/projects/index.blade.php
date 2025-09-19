@extends('layouts.main')

@section('title_page')
    Projects
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">Projects</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">List</h4>
                    @can('projects.manage')
                        <button class="btn btn-sm btn-primary float-right" id="btnNew">Create</button>
                    @endcan
                </div>
                @if (session('success'))
                    <script>
                        toastr.success(@json(session('success')));
                    </script>
                @endif
                <div class="card-body">
                    <table class="table table-striped table-sm mb-0" id="projects-table">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Fund</th>
                                <th>Budget</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="projectModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Project</h5><button type="button" class="close" data-dismiss="modal"
                        aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <form id="projectForm" action="#" method="post">
                    <div class="modal-body">
                        <input type="hidden" id="proj_id">
                        <div class="form-group"><label>Code</label><input id="code" class="form-control" required>
                        </div>
                        <div class="form-group"><label>Name</label><input id="name" class="form-control" required>
                        </div>
                        <div class="form-group"><label>Fund</label>
                            <select id="fund_id" class="form-control">
                                <option value="">-- none --</option>
                            </select>
                        </div>
                        <div class="form-group"><label>Budget Total</label><input id="budget_total" type="number"
                                step="0.01" min="0" class="form-control"></div>
                        <div class="form-group"><label>Status</label>
                            <select id="status" class="form-control">
                                <option value="active">Active</option>
                                <option value="closed">Closed</option>
                            </select>
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
            const table = $('#projects-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('projects.data') }}',
                columns: [{
                        data: 'code',
                        name: 'code'
                    },
                    {
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'fund',
                        name: 'fund'
                    },
                    {
                        data: 'budget_total',
                        name: 'budget_total',
                        className: 'text-right',
                        render: d => Number(d || 0).toLocaleString('en-US', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        })
                    },
                    {
                        data: 'status',
                        name: 'status'
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
                $('#projectModal form')[0].reset();
                $('#proj_id').val('');
                $('#projectModal').modal('show');
                $('#projectModal form').attr('action', '{{ route('projects.store') }}').data('method',
                    'POST');
            });
            $(document).on('click', '.btn-edit', function() {
                const b = $(this);
                $('#proj_id').val(b.data('id'));
                $('#code').val(b.data('code'));
                $('#name').val(b.data('name'));
                $('#status').val(b.data('status'));
                $('#budget_total').val(b.data('budget'));
                $('#fund_id').val(b.data('fund'));
                $('#projectModal').modal('show');
                $('#projectModal form').attr('action', '{{ url('projects') }}/' + b.data('id')).data(
                    'method', 'PATCH');
            });
            $('#projectForm').on('submit', async function(e) {
                e.preventDefault();
                const form = $(this);
                const url = form.attr('action');
                const method = form.data('method') || 'POST';
                const payload = {
                    code: $('#code').val(),
                    name: $('#name').val(),
                    fund_id: $('#fund_id').val(),
                    budget_total: $('#budget_total').val(),
                    status: $('#status').val(),
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

                    $('#projectModal').modal('hide');

                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.message || 'Project saved successfully',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: response.message || 'Failed to save project'
                        });
                    }

                    table.ajax.reload();
                } catch (e) {
                    let errorMessage = 'Failed to save project';

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
                            text: 'Project has been deleted successfully.',
                            timer: 2000,
                            showConfirmButton: false
                        });

                        table.ajax.reload();
                    } catch (e) {
                        let errorMessage = 'Failed to delete project';

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
