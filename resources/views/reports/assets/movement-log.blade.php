@extends('layouts.main')

@section('title_page')
    Movement Log
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('reports.assets.index') }}">Asset Reports</a></li>
    <li class="breadcrumb-item active">Movement Log</li>
@endsection

@section('content')
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Asset Movements</h3>
                    <div class="card-tools">
                        <div class="btn-group">
                            <button type="button" class="btn btn-success btn-sm dropdown-toggle" data-toggle="dropdown">
                                <i class="fas fa-download"></i> Export
                            </button>
                            <div class="dropdown-menu">
                                <a class="dropdown-item"
                                    href="{{ route('reports.assets.movement-log', array_merge(request()->query(), ['export' => 'csv'])) }}">
                                    <i class="fas fa-file-csv"></i> Export CSV
                                </a>
                                <a class="dropdown-item"
                                    href="{{ route('reports.assets.movement-log', array_merge(request()->query(), ['export' => 'excel'])) }}">
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
                                <select name="asset_id" class="form-control select2bs4">
                                    <option value="">All Assets</option>
                                    @foreach ($filterOptions['assets'] as $asset)
                                        <option value="{{ $asset->id }}"
                                            {{ request('asset_id') == $asset->id ? 'selected' : '' }}>
                                            {{ $asset->code }} - {{ $asset->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select name="movement_type" class="form-control">
                                    <option value="">All Types</option>
                                    @foreach ($filterOptions['movement_types'] as $type)
                                        <option value="{{ $type }}"
                                            {{ request('movement_type') == $type ? 'selected' : '' }}>
                                            {{ ucfirst(str_replace('_', ' ', $type)) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select name="status" class="form-control">
                                    <option value="">All Status</option>
                                    @foreach ($filterOptions['statuses']['movements'] as $status)
                                        <option value="{{ $status }}"
                                            {{ request('status') == $status ? 'selected' : '' }}>
                                            {{ ucfirst($status) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                            </div>
                            <div class="col-md-2">
                                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
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
                                    <th>Date</th>
                                    <th>Asset</th>
                                    <th>Type</th>
                                    <th>From Location</th>
                                    <th>To Location</th>
                                    <th>From Custodian</th>
                                    <th>To Custodian</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($movements as $movement)
                                    <tr>
                                        <td>{{ $movement->movement_date ? $movement->movement_date->format('d/m/Y') : '-' }}</td>
                                        <td>{{ $movement->asset_code }} - {{ $movement->asset_name }}</td>
                                        <td>{{ ucfirst(str_replace('_', ' ', $movement->movement_type)) }}</td>
                                        <td>{{ $movement->from_location ?? '-' }}</td>
                                        <td>{{ $movement->to_location ?? '-' }}</td>
                                        <td>{{ $movement->from_custodian ?? '-' }}</td>
                                        <td>{{ $movement->to_custodian ?? '-' }}</td>
                                        <td>
                                            <span class="badge badge-{{ $movement->status === 'completed' ? 'success' : ($movement->status === 'approved' ? 'info' : ($movement->status === 'cancelled' ? 'danger' : 'warning')) }}">
                                                {{ ucfirst($movement->status) }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">
                                            No movements found matching the criteria.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($movements->hasPages())
                        <div class="mt-3">{{ $movements->links() }}</div>
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
