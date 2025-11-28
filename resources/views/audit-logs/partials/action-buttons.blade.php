<div class="btn-group">
    <a href="{{ route('audit-logs.show', [$log->entity_type, $log->entity_id]) }}" 
       class="btn btn-sm btn-info" 
       title="View Audit Trail">
        <i class="fa fa-eye"></i>
    </a>
    @if($log->old_values || $log->new_values)
        <button type="button" 
                class="btn btn-sm btn-primary view-changes-btn" 
                data-log-id="{{ $log->id }}"
                title="View Changes">
            <i class="fa fa-exchange-alt"></i>
        </button>
    @endif
</div>
