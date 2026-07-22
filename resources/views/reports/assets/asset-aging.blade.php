@extends('layouts.main')

@section('title_page')
    Asset Aging
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('reports.assets.index') }}">Asset Reports</a></li>
    <li class="breadcrumb-item active">Asset Aging</li>
@endsection

@section('content')
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Active Assets by Age</h3>
                    <div class="card-tools">
                        <div class="btn-group">
                            <button type="button" class="btn btn-success btn-sm dropdown-toggle" data-toggle="dropdown">
                                <i class="fas fa-download"></i> Export
                            </button>
                            <div class="dropdown-menu">
                                <a class="dropdown-item"
                                    href="{{ route('reports.assets.aging', array_merge(request()->query(), ['export' => 'csv'])) }}">
                                    <i class="fas fa-file-csv"></i> Export CSV
                                </a>
                                <a class="dropdown-item"
                                    href="{{ route('reports.assets.aging', array_merge(request()->query(), ['export' => 'excel'])) }}">
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
                                <select name="category_id" class="form-control select2bs4">
                                    <option value="">All Categories</option>
                                    @foreach ($filterOptions['categories'] as $category)
                                        <option value="{{ $category->id }}"
                                            {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fas fa-filter"></i> Filter
                                </button>
                            </div>
                            <div class="col-md-2">
                                <a href="{{ route('reports.assets.aging') }}" class="btn btn-secondary btn-block">Clear</a>
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
                                    <th>Years Owned</th>
                                    <th>Days Owned</th>
                                    <th>Life (Months)</th>
                                    <th>Acquisition Cost</th>
                                    <th>Book Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($assets as $asset)
                                    <tr>
                                        <td>{{ $asset->code }}</td>
                                        <td>{{ $asset->name }}</td>
                                        <td>{{ $asset->category_name }}</td>
                                        <td>{{ $asset->placed_in_service_date ? $asset->placed_in_service_date->format('d/m/Y') : '-' }}</td>
                                        <td class="text-right">{{ number_format($asset->years_owned, 2) }}</td>
                                        <td class="text-right">{{ number_format($asset->days_owned) }}</td>
                                        <td class="text-right">{{ $asset->life_months ?? '-' }}</td>
                                        <td class="text-right">Rp {{ number_format($asset->acquisition_cost, 0, ',', '.') }}</td>
                                        <td class="text-right">Rp {{ number_format($asset->current_book_value, 0, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-4">
                                            No active assets found for aging analysis.
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

@section('scripts')
    <script>
        $(document).ready(function() {
            $('.select2bs4').select2({ theme: 'bootstrap4', width: '100%' });
        });
    </script>
@endsection
