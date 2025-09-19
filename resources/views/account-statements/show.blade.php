@extends('layouts.main')

@section('title_page')
    Account Statement - {{ $accountStatement->statement_no }}
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('account-statements.index') }}">Account Statements</a></li>
    <li class="breadcrumb-item active">{{ $accountStatement->statement_no }}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="card-title">{{ $accountStatement->statement_no }}</h3>
                        <span
                            class="badge badge-{{ $accountStatement->status === 'finalized' ? 'success' : ($accountStatement->status === 'draft' ? 'warning' : 'danger') }}">
                            {{ ucfirst($accountStatement->status) }}
                        </span>
                    </div>
                    <div>
                        <a href="{{ route('account-statements.index') }}" class="btn btn-secondary btn-sm">Back</a>
                        @if ($accountStatement->status === 'draft')
                            @can('account_statements.update')
                                <a href="{{ route('account-statements.edit', $accountStatement) }}"
                                    class="btn btn-warning btn-sm">Edit</a>
                            @endcan
                        @endif
                        <a href="{{ route('account-statements.print', $accountStatement) }}" class="btn btn-info btn-sm"
                            target="_blank">Print</a>
                        @if ($accountStatement->status === 'draft')
                            @can('account_statements.update')
                                <form method="POST" action="{{ route('account-statements.finalize', $accountStatement) }}"
                                    class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-sm"
                                        onclick="return confirm('Finalize this statement?')">Finalize</button>
                                </form>
                            @endcan
                        @endif
                        @if ($accountStatement->status !== 'finalized')
                            @can('account_statements.delete')
                                <form method="POST" action="{{ route('account-statements.destroy', $accountStatement) }}"
                                    class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm"
                                        onclick="return confirm('Delete this statement?')">Delete</button>
                                </form>
                            @endcan
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td><strong>Statement Type:</strong></td>
                                    <td>
                                        <span
                                            class="badge badge-{{ $accountStatement->statement_type === 'gl_account' ? 'info' : 'success' }}">
                                            {{ ucfirst(str_replace('_', ' ', $accountStatement->statement_type)) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Account/Partner:</strong></td>
                                    <td>{{ $accountStatement->display_name }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Period:</strong></td>
                                    <td>{{ $accountStatement->from_date->format('d/m/Y') }} -
                                        {{ $accountStatement->to_date->format('d/m/Y') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Created By:</strong></td>
                                    <td>{{ $accountStatement->creator->name ?? 'N/A' }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td><strong>Opening Balance:</strong></td>
                                    <td class="text-right">
                                        <span
                                            class="{{ $accountStatement->opening_balance >= 0 ? 'text-success' : 'text-danger' }}">
                                            {{ $accountStatement->opening_balance >= 0 ? '+' : '' }}Rp
                                            {{ number_format($accountStatement->opening_balance, 2) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Total Debits:</strong></td>
                                    <td class="text-right">Rp {{ number_format($accountStatement->total_debits, 2) }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Total Credits:</strong></td>
                                    <td class="text-right">Rp {{ number_format($accountStatement->total_credits, 2) }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Closing Balance:</strong></td>
                                    <td class="text-right">
                                        <span
                                            class="{{ $accountStatement->closing_balance >= 0 ? 'text-success' : 'text-danger' }}">
                                            {{ $accountStatement->closing_balance >= 0 ? '+' : '' }}Rp
                                            {{ number_format($accountStatement->closing_balance, 2) }}
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    @if ($accountStatement->lines->count() > 0)
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Reference</th>
                                    <th>Description</th>
                                    <th>Project</th>
                                    <th>Department</th>
                                    <th class="text-right">Debit</th>
                                    <th class="text-right">Credit</th>
                                    <th class="text-right">Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($accountStatement->lines as $line)
                                    <tr>
                                        <td>{{ $line->transaction_date->format('d/m/Y') }}</td>
                                        <td>
                                            @if ($line->getReferenceUrl())
                                                <a href="{{ $line->getReferenceUrl() }}"
                                                    target="_blank">{{ $line->reference_display }}</a>
                                            @else
                                                {{ $line->reference_display }}
                                            @endif
                                        </td>
                                        <td>{{ $line->description }}</td>
                                        <td>{{ $line->project->name ?? '-' }}</td>
                                        <td>{{ $line->department->name ?? '-' }}</td>
                                        <td class="text-right">
                                            @if ($line->debit_amount > 0)
                                                Rp {{ number_format($line->debit_amount, 2) }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="text-right">
                                            @if ($line->credit_amount > 0)
                                                Rp {{ number_format($line->credit_amount, 2) }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="text-right">
                                            <span
                                                class="{{ $line->running_balance >= 0 ? 'text-success' : 'text-danger' }}">
                                                {{ $line->running_balance >= 0 ? '+' : '' }}Rp
                                                {{ number_format($line->running_balance, 2) }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="font-weight-bold">
                                    <td colspan="5" class="text-right">Totals:</td>
                                    <td class="text-right">Rp {{ number_format($accountStatement->total_debits, 2) }}</td>
                                    <td class="text-right">Rp {{ number_format($accountStatement->total_credits, 2) }}</td>
                                    <td class="text-right">
                                        <span
                                            class="{{ $accountStatement->closing_balance >= 0 ? 'text-success' : 'text-danger' }}">
                                            {{ $accountStatement->closing_balance >= 0 ? '+' : '' }}Rp
                                            {{ number_format($accountStatement->closing_balance, 2) }}
                                        </span>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    @else
                        <div class="text-center py-4">
                            <h5 class="text-muted">No transactions found</h5>
                            <p class="text-muted">No transactions were found for the selected period and filters.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
