@extends('adminlte::page')

@section('title', 'Control Accounts')

@section('content_header')
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1>Control Accounts</h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('accounts.index') }}">Accounting</a></li>
                <li class="breadcrumb-item active">Control Accounts</li>
            </ol>
        </div>
    </div>
@stop

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3 class="card-title">
                            <i class="fas fa-layer-group"></i> Control Accounts
                        </h3>
                        <div class="card-tools">
                            <a href="{{ route('control-accounts.reconciliation') }}" class="btn btn-warning btn-sm">
                                <i class="fas fa-balance-scale"></i> Reconciliation
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="control-accounts-table" class="table table-bordered table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Account Code</th>
                                    <th>Account Name</th>
                                    <th>Control Type</th>
                                    <th>Subsidiaries</th>
                                    <th>Status</th>
                                    <th>Description</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('js')
<script>
$(document).ready(function() {
    $('#control-accounts-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('control-accounts.data') }}",
        columns: [
            { data: 'account_code', name: 'account_code' },
            { data: 'account_name', name: 'account_name' },
            { data: 'control_type', name: 'control_type' },
            { data: 'subsidiary_count', name: 'subsidiary_count', searchable: false },
            { data: 'status', name: 'is_active', searchable: false },
            { data: 'description', name: 'description' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        order: [[0, 'asc']],
        pageLength: 25,
        responsive: true,
        dom: 'Bfrtip',
        buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ]
    });
});

function reconcileAccount(id) {
    if (confirm('Are you sure you want to reconcile this control account?')) {
        $.post(`/control-accounts/${id}/reconcile`, {
            _token: '{{ csrf_token() }}'
        })
        .done(function(response) {
            if (response.success) {
                Swal.fire({
                    title: 'Success!',
                    text: response.message,
                    icon: 'success',
                    confirmButtonText: 'OK'
                }).then(() => {
                    $('#control-accounts-table').DataTable().ajax.reload();
                });
            }
        })
        .fail(function(xhr) {
            Swal.fire({
                title: 'Error!',
                text: 'An error occurred while reconciling the account.',
                icon: 'error',
                confirmButtonText: 'OK'
            });
        });
    }
}
</script>
@stop
