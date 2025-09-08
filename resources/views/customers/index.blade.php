@extends('layouts.main')

@section('title_page')
    Customers
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">Customers</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">List</h4>
                    @can('customers.manage')
                        <button class="btn btn-sm btn-primary float-right" id="btnNew">Create</button>
                    @endcan
                </div>
                @if (session('success'))
                    <script>
                        toastr.success(@json(session('success')));
                    </script>
                @endif
                <div class="card-body">
                    <table class="table table-striped table-sm mb-0" id="cust-table">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="custModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Customer</h5><button type="button" class="close" data-dismiss="modal"
                        aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <form id="custForm" action="#" method="post">
                    <div class="modal-body">
                        <input type="hidden" id="cust_id">
                        <div class="form-group"><label>Code</label><input id="code" class="form-control" required>
                        </div>
                        <div class="form-group"><label>Name</label><input id="name" class="form-control" required>
                        </div>
                        <div class="form-group"><label>Email</label><input id="email" class="form-control"></div>
                        <div class="form-group"><label>Phone</label><input id="phone" class="form-control"></div>
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
            var table = $('#cust-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('customers.data') }}',
                columns: [{
                        data: 'code',
                        name: 'code'
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
                        data: 'phone',
                        name: 'phone'
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
                $('#custModal form')[0].reset();
                $('#custModal').modal('show');
                $('#custModal form').attr('action', '{{ route('customers.store') }}').data('method',
                    'POST');
            });
            $(document).on('click', '.btn-edit', function() {
                const btn = $(this);
                $('#cust_id').val(btn.data('id'));
                $('#code').val(btn.data('code'));
                $('#name').val(btn.data('name'));
                $('#email').val(btn.data('email'));
                $('#phone').val(btn.data('phone'));
                $('#custModal').modal('show');
                $('#custModal form').attr('action', btn.data('url')).data('method', 'PATCH');
            });
            $('#custForm').on('submit', async function(e) {
                e.preventDefault();
                const form = $(this);
                const url = form.attr('action');
                const method = form.data('method') || 'POST';
                const payload = {
                    code: $('#code').val(),
                    name: $('#name').val(),
                    email: $('#email').val(),
                    phone: $('#phone').val(),
                    _token: '{{ csrf_token() }}'
                };
                try {
                    await $.ajax({
                        url,
                        method,
                        data: payload,
                        headers: {
                            'Accept': 'application/json'
                        }
                    });
                    $('#custModal').modal('hide');
                    toastr.success('Saved');
                    table.ajax.reload();
                } catch (err) {
                    toastr.error('Failed to save');
                }
            });
        });
    </script>
@endsection
