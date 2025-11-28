@props(['entityType', 'entityId', 'limit' => 5])

@php
    $recentChanges = app(\App\Services\AuditLogService::class)
        ->getAuditTrail($entityType, $entityId)
        ->where('action', 'updated')
        ->take($limit);
@endphp

<div class="card card-outline card-warning">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-edit mr-2"></i>
            Recent Changes
        </h3>
    </div>
    <div class="card-body p-0">
        @if($recentChanges->count() > 0)
            <ul class="list-group list-group-flush">
                @foreach($recentChanges as $log)
                    <li class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>{{ $log->user ? $log->user->name : 'System' }}</strong>
                                <small class="text-muted ml-2">
                                    {{ $log->created_at->diffForHumans() }}
                                </small>
                                <div class="mt-1">
                                    <small>{{ Str::limit($log->description ?? '', 60) }}</small>
                                </div>
                            </div>
                            <span class="badge badge-info">{{ ucfirst($log->action) }}</span>
                        </div>
                    </li>
                @endforeach
            </ul>
        @else
            <div class="p-3 text-center text-muted">
                <i class="fas fa-info-circle"></i> No recent changes
            </div>
        @endif
    </div>
</div>
