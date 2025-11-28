@extends('layouts.main')

@section('title_page')
    Audit Logs - {{ ucfirst($action) }} Actions
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('audit-logs.index') }}">Audit Logs</a></li>
    <li class="breadcrumb-item active">{{ ucfirst($action) }}</li>
@endsection

@section('content')
    <!-- Action Information Card -->
    <div class="row">
        <div class="col-12">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-info-circle"></i> Action Information
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <strong>Action:</strong><br>
                            @include('audit-logs.partials.action-badge', ['log' => (object)['action' => $action]])
                        </div>
                        <div class="col-md-3">
                            <strong>Total Occurrences:</strong><br>
                            {{ number_format($stats['total'] ?? 0) }}
                        </div>
                        <div class="col-md-3">
                            <strong>This Week:</strong><br>
                            {{ number_format($stats['this_week'] ?? 0) }}
                        </div>
                        <div class="col-md-3">
                            <form class="form-inline" method="GET" action="{{ route('audit-logs.by-action', $action) }}">
                                <label class="mr-2">Days:</label>
                                <input type="number" name="days" class="form-control form-control-sm" value="{{ $days }}" min="1" max="365" style="width: 80px;">
                                <button type="submit" class="btn btn-sm btn-primary ml-1">Update</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Most Affected Entities -->
    @if($stats['most_affected_entities'] && $stats['most_affected_entities']->count() > 0)
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-chart-bar"></i> Most Affected Entity Types
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            @foreach($stats['most_affected_entities'] as $entity)
                                <div class="col-md-2">
                                    <div class="info-box mb-3">
                                        <span class="info-box-icon bg-info"><i class="fas fa-cube"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">{{ ucwords(str_replace('_', ' ', $entity->entity_type)) }}</span>
                                            <span class="info-box-number">{{ number_format($entity->count) }}</span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Activity Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-list"></i> {{ ucfirst($action) }} Actions (Last {{ $days }} days)
                    </h3>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>User</th>
                                <th>Entity Type</th>
                                <th>Entity ID</th>
                                <th>Description</th>
                                <th>IP Address</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($auditLogs as $log)
                                <tr>
                                    <td>{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                                    <td>{{ $log->user ? $log->user->name : 'System' }}</td>
                                    <td>{{ ucwords(str_replace('_', ' ', $log->entity_type)) }}</td>
                                    <td>{{ $log->entity_id }}</td>
                                    <td>{{ Str::limit($log->description ?? '', 100) }}</td>
                                    <td>{{ $log->ip_address ?? 'N/A' }}</td>
                                    <td>
                                        <a href="{{ route('audit-logs.show', [$log->entity_type, $log->entity_id]) }}" 
                                           class="btn btn-sm btn-info" title="View Audit Trail">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center">No {{ $action }} actions found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
