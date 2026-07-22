@extends('layouts.main')

@section('title_page')
    Depreciation Run History
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('reports.assets.index') }}">Asset Reports</a></li>
    <li class="breadcrumb-item active">Depreciation History</li>
@endsection

@section('content')
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Depreciation Runs</h3>
                    <div class="card-tools">
                        <div class="btn-group">
                            <button type="button" class="btn btn-success btn-sm dropdown-toggle" data-toggle="dropdown">
                                <i class="fas fa-download"></i> Export
                            </button>
                            <div class="dropdown-menu">
                                <a class="dropdown-item"
                                    href="{{ route('reports.assets.depreciation-history', array_merge(request()->query(), ['export' => 'csv'])) }}">
                                    <i class="fas fa-file-csv"></i> Export CSV
                                </a>
                                <a class="dropdown-item"
                                    href="{{ route('reports.assets.depreciation-history', array_merge(request()->query(), ['export' => 'excel'])) }}">
                                    <i class="fas fa-file-excel"></i> Export Excel
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <form method="GET" class="mb-4">
                        <div class="row">
                            <div class="col-md-2">
                                <select name="status" class="form-control">
                                    <option value="">All Status</option>
                                    @foreach (['draft', 'posted', 'reversed'] as $status)
                                        <option value="{{ $status }}"
                                            {{ ($filters['status'] ?? '') == $status ? 'selected' : '' }}>
                                            {{ ucfirst($status) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <input type="month" name="period_from" class="form-control"
                                    value="{{ $filters['period_from'] ?? '' }}">
                            </div>
                            <div class="col-md-2">
                                <input type="month" name="period_to" class="form-control"
                                    value="{{ $filters['period_to'] ?? '' }}">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fas fa-filter"></i> Filter
                                </button>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Period</th>
                                    <th>Status</th>
                                    <th>Asset Count</th>
                                    <th>Entry Count</th>
                                    <th>Total Depreciation</th>
                                    <th>Posted At</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($runs as $run)
                                    <tr>
                                        <td>{{ $run->period }}</td>
                                        <td>
                                            <span class="badge badge-{{ $run->status === 'posted' ? 'success' : ($run->status === 'reversed' ? 'danger' : 'warning') }}">
                                                {{ ucfirst($run->status) }}
                                            </span>
                                        </td>
                                        <td class="text-right">{{ $run->asset_count }}</td>
                                        <td class="text-right">{{ $run->entry_count }}</td>
                                        <td class="text-right">Rp {{ number_format($run->total_depreciation, 0, ',', '.') }}</td>
                                        <td>{{ $run->posted_at ? $run->posted_at->format('d/m/Y H:i') : '-' }}</td>
                                        <td>{{ $run->notes ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">
                                            No depreciation runs found matching the criteria.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($runs->hasPages())
                        <div class="mt-3">{{ $runs->links() }}</div>
                    @endif
                </div>
            </div>
@endsection
