@extends('layouts.main')

@section('title_page')
    User Activity - {{ $user->name }}
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('audit-logs.index') }}">Audit Logs</a></li>
    <li class="breadcrumb-item active">{{ $user->name }}</li>
@endsection

@section('content')
    <!-- User Information Card -->
    <div class="row">
        <div class="col-12">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-user"></i> User Information
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <strong>Name:</strong><br>
                            {{ $user->name }}
                        </div>
                        <div class="col-md-3">
                            <strong>Email:</strong><br>
                            {{ $user->email }}
                        </div>
                        <div class="col-md-3">
                            <strong>Total Activities:</strong><br>
                            {{ number_format($stats['total'] ?? 0) }}
                        </div>
                        <div class="col-md-3">
                            <strong>Activities This Week:</strong><br>
                            {{ number_format($stats['this_week'] ?? 0) }}
                        </div>
                    </div>
                    @if($firstActivity || $lastActivity)
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <strong>First Activity:</strong> 
                                {{ $firstActivity ? $firstActivity->created_at->format('Y-m-d H:i:s') : 'N/A' }}
                            </div>
                            <div class="col-md-6">
                                <strong>Last Activity:</strong> 
                                {{ $lastActivity ? $lastActivity->created_at->format('Y-m-d H:i:s') : 'N/A' }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row">
        <div class="col-md-6">
            <div class="info-box">
                <span class="info-box-icon bg-info"><i class="fas fa-tasks"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Most Common Action</span>
                    <span class="info-box-number">
                        {{ $stats['most_common_action'] ? ucfirst($stats['most_common_action']->action) . ' (' . $stats['most_common_action']->count . ')' : 'N/A' }}
                    </span>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="info-box">
                <span class="info-box-icon bg-success"><i class="fas fa-layer-group"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Most Modified Entity</span>
                    <span class="info-box-number">
                        {{ $stats['most_modified_entity'] ? ucwords(str_replace('_', ' ', $stats['most_modified_entity']->entity_type)) . ' (' . $stats['most_modified_entity']->count . ')' : 'N/A' }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Activity Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-list"></i> Activity Log (Last {{ $days }} days)
                    </h3>
                    <div class="card-tools">
                        <form class="form-inline" method="GET" action="{{ route('audit-logs.by-user', $userId) }}">
                            <input type="number" name="days" class="form-control form-control-sm" value="{{ $days }}" min="1" max="365" style="width: 80px;">
                            <button type="submit" class="btn btn-sm btn-primary ml-1">Update</button>
                        </form>
                    </div>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>Action</th>
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
                                    <td>@include('audit-logs.partials.action-badge', ['log' => $log])</td>
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
                                    <td colspan="7" class="text-center">No activity found for this user.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
