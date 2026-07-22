@extends('layouts.main')

@section('title_page')
    Low Value Assets
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('reports.assets.index') }}">Asset Reports</a></li>
    <li class="breadcrumb-item active">Low Value Assets</li>
@endsection

@section('content')
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Assets at or below threshold</h3>
                    <div class="card-tools">
                        <div class="btn-group">
                            <button type="button" class="btn btn-success btn-sm dropdown-toggle" data-toggle="dropdown">
                                <i class="fas fa-download"></i> Export
                            </button>
                            <div class="dropdown-menu">
                                <a class="dropdown-item"
                                    href="{{ route('reports.assets.low-value', array_merge(request()->query(), ['export' => 'csv'])) }}">
                                    <i class="fas fa-file-csv"></i> Export CSV
                                </a>
                                <a class="dropdown-item"
                                    href="{{ route('reports.assets.low-value', array_merge(request()->query(), ['export' => 'excel'])) }}">
                                    <i class="fas fa-file-excel"></i> Export Excel
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <form method="GET" class="mb-4">
                        <div class="row">
                            <div class="col-md-3">
                                <label>Threshold (IDR)</label>
                                <input type="number" name="threshold" class="form-control" min="0" step="1000"
                                    value="{{ $threshold }}">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fas fa-filter"></i> Apply
                                </button>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Asset Code</th>
                                    <th>Asset Name</th>
                                    <th>Category</th>
                                    <th>Placed in Service</th>
                                    <th>Acquisition Cost</th>
                                    <th>Book Value</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($assets as $asset)
                                    <tr>
                                        <td>{{ $asset->code }}</td>
                                        <td>{{ $asset->name }}</td>
                                        <td>{{ $asset->category->name ?? '-' }}</td>
                                        <td>{{ $asset->placed_in_service_date ? $asset->placed_in_service_date->format('d/m/Y') : '-' }}</td>
                                        <td class="text-right">Rp {{ number_format($asset->acquisition_cost, 0, ',', '.') }}</td>
                                        <td class="text-right">Rp {{ number_format($asset->current_book_value, 0, ',', '.') }}</td>
                                        <td>
                                            <span class="badge badge-success">{{ ucfirst($asset->status) }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">
                                            No active assets found at or below Rp {{ number_format($threshold, 0, ',', '.') }}.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($assets->hasPages())
                        <div class="mt-3">{{ $assets->links() }}</div>
                    @endif
                </div>
            </div>
@endsection
