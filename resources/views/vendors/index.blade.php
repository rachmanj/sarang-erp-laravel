@extends('layouts.main')

@section('title_page')
    Suppliers
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">Suppliers</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">

            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">List</h4>
                    @can('vendors.manage')
                        <button class="btn btn-sm btn-primary float-right" id="btnNewVendor">Create</button>
                    @endcan
                </div>
                @if (session('success'))
                    <script>
                        toastr.success(@json(session('success')));
                    </script>
                @endif
                <div class="card-body">
                    <table class="table table-striped table-sm mb-0" id="vendor-table">
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
    <div class="modal fade" id="vendorModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Supplier</h5><button type="button" class="close" data-dismiss="modal"
                        aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <form id="vendorForm" action="#" method="post">
                    <div class="modal-body">
                        <input type="hidden" id="vendor_id">
                        <div class="form-group"><label>Code</label><input id="vcode" class="form-control" required>
                        </div>
                        <div class="form-group"><label>Name</label><input id="vname" class="form-control" required>
                        </div>
                        <div class="form-group"><label>Email</label><input id="vemail" class="form-control"></div>
                        <div class="form-group"><label>Phone</label><input id="vphone" class="form-control"></div>
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
            var table = $('#vendor-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('vendors.data') }}',
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

            $('#btnNewVendor').on('click', function() {
                $('#vendorModal form')[0].reset();
                $('#vendorModal').modal('show');
                $('#vendorModal form').attr('action', '{{ route('vendors.store') }}').data('method',
                    'POST');
            });
            $(document).on('click', '.btn-edit', function() {
                const btn = $(this);
                $('#vendor_id').val(btn.data('id'));
                $('#vcode').val(btn.data('code'));
                $('#vname').val(btn.data('name'));
                $('#vemail').val(btn.data('email'));
                $('#vphone').val(btn.data('phone'));
                $('#vendorModal').modal('show');
                $('#vendorModal form').attr('action', btn.data('url')).data('method', 'PATCH');
            });
            $('#vendorForm').on('submit', async function(e) {
                e.preventDefault();
                const form = $(this);
                const url = form.attr('action');
                const method = form.data('method') || 'POST';
                const payload = {
                    code: $('#vcode').val(),
                    name: $('#vname').val(),
                    email: $('#vemail').val(),
                    phone: $('#vphone').val(),
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
                    $('#vendorModal').modal('hide');
                    toastr.success('Saved');
                    table.ajax.reload();
                } catch (err) {
                    toastr.error('Failed to save');
                }
            });
        });
    </script>
@endsection
