@extends('layouts.main')

@section('title_page')
    Subledger Reconciliation
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">Subledger Reconciliation</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h3 class="card-title">AR/AP Subledger vs GL Control Account</h3>
                    <form method="get" class="form-inline">
                        <label class="mr-2 mb-0">As of
                            <input type="date" name="as_of" value="{{ $as_of ?? now()->toDateString() }}"
                                class="form-control form-control-sm ml-1">
                        </label>
                        <label class="mr-2 mb-0 small">
                            <input type="checkbox" name="include_unposted" value="1" @checked(request()->boolean('include_unposted'))>
                            Include unposted journals
                        </label>
                        <button class="btn btn-sm btn-primary">Apply</button>
                    </form>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Control Account</th>
                                <th class="text-right">Subledger Total</th>
                                <th class="text-right">GL Control Balance</th>
                                <th class="text-right">Variance</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($sections as $section)
                                <tr>
                                    <td>{{ $section['label'] }}</td>
                                    <td>
                                        @if ($section['control_account_code'])
                                            {{ $section['control_account_code'] }} - {{ $section['control_account_name'] }}
                                        @else
                                            <span class="text-muted">No control account configured</span>
                                        @endif
                                    </td>
                                    <td class="text-right">{{ number_format($section['subledger_total'], 2) }}</td>
                                    <td class="text-right">{{ number_format($section['gl_control_balance'], 2) }}</td>
                                    <td class="text-right">{{ number_format($section['variance'], 2) }}</td>
                                    <td>
                                        @if ($section['is_balanced'])
                                            <span class="badge badge-success">Balanced</span>
                                        @else
                                            <span class="badge badge-warning">Out of balance</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <p class="text-muted small mb-0">
                        Subledger totals use allocation-netted outstanding balances as of the selected date.
                        GL control balances use posted journal activity on the configured control account.
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection
