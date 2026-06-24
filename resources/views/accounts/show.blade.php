@extends('layouts.main')

@section('title_page')
    Account {{ $account->code }}
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('accounts.index') }}">Accounts</a></li>
    <li class="breadcrumb-item active">{{ $account->code }}</li>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="card card-outline card-primary mb-3">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
                    <div>
                        <h3 class="card-title mb-1">
                            <i class="fas fa-book mr-1"></i>
                            {{ $account->code }} — {{ $account->name }}
                        </h3>
                        <div class="text-muted small">
                            Type: {{ strtoupper($account->type) }}
                            @if ($account->normal_balance)
                                · Normal balance: {{ ucfirst($account->normal_balance) }}
                            @endif
                            · {{ $account->is_postable ? 'Postable' : 'Non-postable' }}
                        </div>
                    </div>
                    <div class="d-flex flex-wrap align-items-center mt-2 mt-md-0">
                        <a href="{{ route('accounts.index') }}" class="btn btn-sm btn-secondary mr-1 mb-1">
                            <i class="fas fa-arrow-left mr-1"></i>Back to Chart of Accounts
                        </a>
                        @can('reports.view')
                            <a href="{{ route('reports.gl-detail', ['account_id' => $account->id, 'from' => $from, 'to' => $to]) }}"
                                class="btn btn-sm btn-outline-primary mb-1">
                                <i class="fas fa-external-link-alt mr-1"></i>Open in GL Detail
                            </a>
                        @endcan
                    </div>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-sm-6 col-lg-3">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3>{{ number_format($ledger['opening_balance'], 2) }}</h3>
                            <p>Opening Balance</p>
                        </div>
                        <div class="icon"><i class="fas fa-door-open"></i></div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3>{{ number_format($ledger['total_debit'], 2) }}</h3>
                            <p>Total Debit</p>
                        </div>
                        <div class="icon"><i class="fas fa-arrow-down"></i></div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3>{{ number_format($ledger['total_credit'], 2) }}</h3>
                            <p>Total Credit</p>
                        </div>
                        <div class="icon"><i class="fas fa-arrow-up"></i></div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="small-box bg-primary">
                        <div class="inner">
                            <h3>{{ number_format($ledger['closing_balance'], 2) }}</h3>
                            <p>Closing Balance</p>
                        </div>
                        <div class="icon"><i class="fas fa-balance-scale"></i></div>
                    </div>
                </div>
            </div>

            <div class="card card-outline card-secondary">
                <div class="card-header">
                    <form method="GET" action="{{ route('accounts.show', $account) }}"
                        class="d-flex flex-wrap align-items-end">
                        <div class="form-group mb-2 mr-3">
                            <label class="small mb-0" for="filter_from">From</label>
                            <input type="date" name="from" id="filter_from" class="form-control form-control-sm"
                                value="{{ $from }}">
                        </div>
                        <div class="form-group mb-2 mr-3">
                            <label class="small mb-0" for="filter_to">To</label>
                            <input type="date" name="to" id="filter_to" class="form-control form-control-sm"
                                value="{{ $to }}">
                        </div>
                        <div class="form-group mb-2 mr-3">
                            <div class="custom-control custom-checkbox mt-4">
                                <input type="checkbox" class="custom-control-input" name="include_unposted"
                                    id="include_unposted" value="1" @checked(! $onlyPosted)>
                                <label class="custom-control-label small" for="include_unposted">Include unposted</label>
                            </div>
                        </div>
                        <div class="form-group mb-2">
                            <button type="submit" class="btn btn-sm btn-primary">Apply</button>
                        </div>
                    </form>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Journal No</th>
                                    <th>Description</th>
                                    <th>Source Document</th>
                                    <th class="text-right">Debit</th>
                                    <th class="text-right">Credit</th>
                                    <th class="text-right">Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($rows as $row)
                                    <tr>
                                        <td>{{ \Illuminate\Support\Carbon::parse($row['date'])->format('d M Y') }}</td>
                                        <td>{{ $row['journal_no'] ?? '—' }}</td>
                                        <td>{{ $row['memo'] ?: ($row['journal_desc'] ?? '—') }}</td>
                                        <td>
                                            @if ($row['source_url'])
                                                <a href="{{ $row['source_url'] }}">{{ $row['source_label'] }}</a>
                                            @else
                                                {{ $row['source_label'] }}
                                            @endif
                                        </td>
                                        <td class="text-right">
                                            {{ $row['debit'] > 0 ? number_format($row['debit'], 2) : '' }}
                                        </td>
                                        <td class="text-right">
                                            {{ $row['credit'] > 0 ? number_format($row['credit'], 2) : '' }}
                                        </td>
                                        <td class="text-right">{{ number_format($row['balance'], 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">
                                            No transactions in this period.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
