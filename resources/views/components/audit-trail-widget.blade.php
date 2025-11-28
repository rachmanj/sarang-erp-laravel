@props(['entityType', 'entityId', 'limit' => 10, 'collapsible' => true])

@php
    $auditTrail = app(\App\Services\AuditLogService::class)
        ->getAuditTrail($entityType, $entityId)
        ->take($limit);
@endphp

<div class="card card-outline card-info">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-history mr-2"></i>
            Audit Trail
            <span class="badge badge-info ml-2">{{ $auditTrail->count() }}</span>
        </h3>
        @if($collapsible)
            <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                    <i class="fas fa-minus"></i>
                </button>
            </div>
        @endif
    </div>
    <div class="card-body p-0">
        @if($auditTrail->count() > 0)
            <div class="timeline">
                @foreach($auditTrail as $log)
                    <div class="time-label">
                        <span class="bg-{{ $log->action_color }}">
                            {{ $log->created_at->format('M d, Y') }}
                        </span>
                    </div>
                    <div>
                        @php
                            $actionIcons = [
                                'created' => 'fa-plus-circle',
                                'updated' => 'fa-edit',
                                'deleted' => 'fa-trash',
                                'approved' => 'fa-check-circle',
                                'rejected' => 'fa-times-circle',
                                'transferred' => 'fa-exchange-alt',
                                'adjusted' => 'fa-adjust',
                            ];
                            $icon = $actionIcons[$log->action] ?? 'fa-circle';
                        @endphp
                        <i class="fas {{ $icon }} bg-{{ $log->action_color }}"></i>
                        <div class="timeline-item">
                            <span class="time">
                                <i class="fas fa-clock"></i> {{ $log->created_at->format('H:i') }}
                            </span>
                            <h3 class="timeline-header">
                                <strong>{{ $log->user ? $log->user->name : 'System' }}</strong>
                                <span class="badge badge-{{ $log->action_color }} ml-2">
                                    {{ ucfirst($log->action) }}
                                </span>
                            </h3>
                            <div class="timeline-body">
                                {{ $log->description }}
                            </div>
                            @if($log->old_values || $log->new_values)
                                <div class="timeline-footer">
                                    <button class="btn btn-sm btn-primary view-changes-btn" 
                                            data-log-id="{{ $log->id }}">
                                        <i class="fas fa-exchange-alt"></i> View Changes
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
            @if($auditTrail->count() >= $limit)
                <div class="card-footer">
                    <a href="{{ route('audit-logs.show', [$entityType, $entityId]) }}" 
                       class="btn btn-sm btn-info">
                        <i class="fas fa-eye"></i> View Full Audit Trail
                    </a>
                </div>
            @endif
        @else
            <div class="p-3 text-center text-muted">
                <i class="fas fa-info-circle"></i> No audit trail available
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        $('.view-changes-btn').on('click', function() {
            var logId = $(this).data('log-id');
            loadChangeModal(logId);
        });
    });

    function loadChangeModal(logId) {
        $.ajax({
            url: '/audit-logs/' + logId + '/changes',
            method: 'GET',
            success: function(data) {
                showChangeModal(data);
            },
            error: function() {
                toastr.error('Failed to load change details');
            }
        });
    }

    function showChangeModal(data) {
        var html = '<div class="table-responsive"><table class="table table-bordered">';
        html += '<thead><tr><th>Field</th><th>Old Value</th><th>New Value</th></tr></thead>';
        html += '<tbody>';
        
        if (data.changes && data.changes.length > 0) {
            data.changes.forEach(function(change) {
                html += '<tr>';
                html += '<td><strong>' + change.field + '</strong></td>';
                html += '<td>' + (change.old_value !== null ? change.old_value : '<em>N/A</em>') + '</td>';
                html += '<td>' + (change.new_value !== null ? change.new_value : '<em>N/A</em>') + '</td>';
                html += '</tr>';
            });
        } else {
            html += '<tr><td colspan="3" class="text-center text-muted">No changes recorded</td></tr>';
        }
        
        html += '</tbody></table></div>';
        
        Swal.fire({
            title: 'Change Details',
            html: html,
            width: '800px',
            showCloseButton: true,
            showConfirmButton: true,
            confirmButtonText: 'Close'
        });
    }
</script>
@endpush
