@extends('adminlte::page')

@section('title', 'Control Account Reconciliation')

@section('content_header')
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1>Control Account Reconciliation</h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('control-accounts.index') }}">Control Accounts</a></li>
                <li class="breadcrumb-item active">Reconciliation</li>
            </ol>
        </div>
    </div>
@stop

@section('content')
    <!-- Summary Cards -->
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ count($reconciliationData) }}</h3>
                    <p>Total Control Accounts</p>
                </div>
                <div class="icon">
                    <i class="fas fa-layer-group"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ collect($reconciliationData)->where('is_reconciled', true)->count() }}</h3>
                    <p>Reconciled</p>
                </div>
                <div class="icon">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ count($exceptions) }}</h3>
                    <p>Exceptions</p>
                </div>
                <div class="icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ collect($reconciliationData)->where('is_reconciled', false)->count() }}</h3>
                    <p>Out of Balance</p>
                </div>
                <div class="icon">
                    <i class="fas fa-times-circle"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Reconciliation Status -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-balance-scale"></i> Reconciliation Status
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-success btn-sm" onclick="reconcileAll()">
                            <i class="fas fa-sync"></i> Reconcile All
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Control Account</th>
                                    <th>Project</th>
                                    <th>Department</th>
                                    <th>Control Balance</th>
                                    <th>Subsidiary Total</th>
                                    <th>Variance</th>
                                    <th>Status</th>
                                    <th>Last Reconciled</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($reconciliationData as $data)
                                <tr class="{{ $data['is_reconciled'] ? 'table-success' : 'table-warning' }}">
                                    <td>
                                        <strong>{{ $data['control_account']->account->code }}</strong><br>
                                        <small>{{ $data['control_account']->account->name }}</small>
                                    </td>
                                    <td>{{ $data['balance']->project->name ?? '-' }}</td>
                                    <td>{{ $data['balance']->department->name ?? '-' }}</td>
                                    <td class="text-right">
                                        Rp {{ number_format($data['balance']->balance, 2) }}
                                    </td>
                                    <td class="text-right">
                                        Rp {{ number_format($data['balance']->reconciled_balance ?? 0, 2) }}
                                    </td>
                                    <td class="text-right">
                                        <span class="{{ abs($data['variance'] ?? 0) > 0.01 ? 'text-danger' : 'text-success' }}">
                                            Rp {{ number_format($data['variance'] ?? 0, 2) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($data['is_reconciled'])
                                            <span class="badge badge-success">Reconciled</span>
                                        @else
                                            <span class="badge badge-warning">Out of Balance</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($data['balance']->last_reconciled_at)
                                            {{ $data['balance']->last_reconciled_at->format('Y-m-d H:i') }}
                                        @else
                                            <span class="text-muted">Never</span>
                                        @endif
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-warning" 
                                                onclick="reconcileAccount({{ $data['control_account']->id }}, {{ $data['balance']->project_id }}, {{ $data['balance']->dept_id }})">
                                            <i class="fas fa-sync"></i> Reconcile
                                        </button>
                                        <a href="{{ route('control-accounts.show', $data['control_account']) }}" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Exceptions -->
    @if(count($exceptions) > 0)
    <div class="row">
        <div class="col-12">
            <div class="card card-warning">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-exclamation-triangle"></i> Reconciliation Exceptions
                    </h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Control Account</th>
                                    <th>Variance</th>
                                    <th>Last Reconciled</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($exceptions as $exception)
                                <tr>
                                    <td>
                                        <strong>{{ $exception['control_account']->account->code }}</strong><br>
                                        <small>{{ $exception['control_account']->account->name }}</small>
                                    </td>
                                    <td class="text-right text-danger">
                                        Rp {{ number_format($exception['variance'], 2) }}
                                    </td>
                                    <td>
                                        @if($exception['last_reconciled'])
                                            {{ $exception['last_reconciled']->format('Y-m-d H:i') }}
                                        @else
                                            <span class="text-muted">Never</span>
                                        @endif
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-warning" 
                                                onclick="reconcileAccount({{ $exception['control_account']->id }})">
                                            <i class="fas fa-sync"></i> Reconcile
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
@stop

@section('js')
<script>
function reconcileAccount(id, projectId = null, deptId = null) {
    let data = {
        _token: '{{ csrf_token() }}'
    };
    
    if (projectId) data.project_id = projectId;
    if (deptId) data.dept_id = deptId;

    $.post(`/control-accounts/${id}/reconcile`, data)
        .done(function(response) {
            if (response.success) {
                Swal.fire({
                    title: 'Success!',
                    text: response.message,
                    icon: 'success',
                    confirmButtonText: 'OK'
                }).then(() => {
                    location.reload();
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

function reconcileAll() {
    if (confirm('Are you sure you want to reconcile all control accounts?')) {
        // This would need to be implemented on the backend
        Swal.fire({
            title: 'Info',
            text: 'Reconcile All functionality would be implemented here.',
            icon: 'info',
            confirmButtonText: 'OK'
        });
    }
}
</script>
@stop
