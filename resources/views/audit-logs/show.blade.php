@extends('layouts.main')

@section('title_page')
    Audit Trail - {{ ucwords(str_replace('_', ' ', $entityType)) }} #{{ $entityId }}
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('audit-logs.index') }}">Audit Logs</a></li>
    <li class="breadcrumb-item active">{{ ucwords(str_replace('_', ' ', $entityType)) }} #{{ $entityId }}</li>
@endsection

@section('content')
    <!-- Entity Information Card -->
    <div class="row">
        <div class="col-12">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-info-circle"></i> Entity Information
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <strong>Entity Type:</strong><br>
                            {{ ucwords(str_replace('_', ' ', $entityType)) }}
                        </div>
                        <div class="col-md-3">
                            <strong>Entity ID:</strong><br>
                            #{{ $entityId }}
                        </div>
                        <div class="col-md-3">
                            <strong>Total Changes:</strong><br>
                            {{ $auditTrail->count() }} {{ Str::plural('entry', $auditTrail->count()) }}
                        </div>
                        <div class="col-md-3">
                            @php
                                $entityRoute = null;
                                if ($entityType === 'inventory_item') {
                                    $entityRoute = route('inventory.show', $entityId);
                                } elseif ($entityType === 'warehouse') {
                                    $entityRoute = route('warehouses.show', $entityId);
                                }
                            @endphp
                            @if($entityRoute)
                                <a href="{{ $entityRoute }}" class="btn btn-sm btn-info">
                                    <i class="fas fa-external-link-alt"></i> View Entity
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Timeline View -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-history"></i> Audit Trail Timeline
                    </h3>
                </div>
                <div class="card-body">
                    @if($auditTrail->count() > 0)
                        <div class="timeline">
                            @foreach($auditTrail as $index => $log)
                                <div class="time-label">
                                    <span class="bg-{{ $log->action_color }}">
                                        {{ $log->created_at->format('M d, Y') }}
                                    </span>
                                </div>
                                <div>
                                    <i class="fas fa-{{ $log->action === 'created' ? 'plus' : ($log->action === 'updated' ? 'edit' : ($log->action === 'deleted' ? 'trash' : 'info')) }} bg-{{ $log->action_color }}"></i>
                                    <div class="timeline-item">
                                        <span class="time">
                                            <i class="fas fa-clock"></i> {{ $log->created_at->format('H:i:s') }}
                                        </span>
                                        <h3 class="timeline-header">
                                            <strong>{{ $log->user ? $log->user->name : 'System' }}</strong>
                                            @include('audit-logs.partials.action-badge', ['log' => $log])
                                        </h3>
                                        <div class="timeline-body">
                                            <p>{{ $log->description ?? 'No description provided.' }}</p>
                                            @if($log->old_values || $log->new_values)
                                                <button type="button" 
                                                        class="btn btn-sm btn-primary view-changes-btn" 
                                                        data-log-id="{{ $log->id }}">
                                                    <i class="fas fa-exchange-alt"></i> View Changes
                                                </button>
                                            @endif
                                        </div>
                                        <div class="timeline-footer">
                                            <small class="text-muted">
                                                <i class="fas fa-globe"></i> {{ $log->ip_address ?? 'N/A' }}
                                                @if($log->user_agent)
                                                    | <i class="fas fa-desktop"></i> {{ Str::limit($log->user_agent, 50) }}
                                                @endif
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                            <div>
                                <i class="fas fa-clock bg-gray"></i>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No audit trail entries found for this entity.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Change Comparison Modal -->
    <div class="modal fade" id="change-modal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Change Details</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="change-modal-body">
                    <div class="text-center">
                        <i class="fa fa-spinner fa-spin"></i> Loading...
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // View changes button
    $('.view-changes-btn').on('click', function() {
        var logId = $(this).data('log-id');
        loadChangeModal(logId);
    });

    function loadChangeModal(logId) {
        $('#change-modal-body').html('<div class="text-center"><i class="fa fa-spinner fa-spin"></i> Loading...</div>');
        $('#change-modal').modal('show');
        
        $.ajax({
            url: '{{ url("audit-logs") }}/' + logId + '/changes',
            method: 'GET',
            success: function(data) {
                populateChangeModal(data);
            },
            error: function() {
                $('#change-modal-body').html('<div class="alert alert-danger">Error loading change details.</div>');
            }
        });
    }

    function populateChangeModal(data) {
        var html = '<div class="row mb-3">';
        html += '<div class="col-md-4"><strong>Action:</strong> ' + data.action + '</div>';
        html += '<div class="col-md-4"><strong>Timestamp:</strong> ' + data.created_at + '</div>';
        html += '<div class="col-md-4"><strong>User:</strong> ' + (data.user || 'System') + '</div>';
        html += '</div>';

        if (data.changes && data.changes.length > 0) {
            html += '<table class="table table-bordered table-sm">';
            html += '<thead><tr><th>Field</th><th>Old Value</th><th>New Value</th></tr></thead>';
            html += '<tbody>';
            data.changes.forEach(function(change) {
                html += '<tr>';
                html += '<td><strong>' + change.field + '</strong></td>';
                html += '<td>' + (change.old_value !== null ? change.old_value : '<em>null</em>') + '</td>';
                html += '<td>' + (change.new_value !== null ? change.new_value : '<em>null</em>') + '</td>';
                html += '</tr>';
            });
            html += '</tbody></table>';
        } else {
            html += '<div class="alert alert-info">No field changes recorded.</div>';
        }

        if (data.old_values || data.new_values) {
            html += '<div class="mt-3">';
            html += '<button class="btn btn-sm btn-secondary" type="button" data-toggle="collapse" data-target="#raw-data">Show Raw Data</button>';
            html += '<div class="collapse mt-2" id="raw-data">';
            if (data.old_values) {
                html += '<div class="mb-2"><strong>Old Values:</strong><pre class="bg-light p-2">' + JSON.stringify(data.old_values, null, 2) + '</pre></div>';
            }
            if (data.new_values) {
                html += '<div><strong>New Values:</strong><pre class="bg-light p-2">' + JSON.stringify(data.new_values, null, 2) + '</pre></div>';
            }
            html += '</div></div>';
        }

        html += '<div class="mt-3"><small class="text-muted">';
        html += '<strong>IP Address:</strong> ' + (data.ip_address || 'N/A') + ' | ';
        html += '<strong>User Agent:</strong> ' + (data.user_agent ? data.user_agent.substring(0, 50) + '...' : 'N/A');
        html += '</small></div>';

        $('#change-modal-body').html(html);
    }
});
</script>
@endpush
