<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Field</th>
                <th>Old Value</th>
                <th>New Value</th>
            </tr>
        </thead>
        <tbody>
            @forelse($changes as $change)
                <tr>
                    <td><strong>{{ ucwords(str_replace('_', ' ', $change['field'])) }}</strong></td>
                    <td>
                        @if($change['old_value'] === null)
                            <em class="text-muted">N/A</em>
                        @elseif(is_array($change['old_value']))
                            <pre class="mb-0">{{ json_encode($change['old_value'], JSON_PRETTY_PRINT) }}</pre>
                        @else
                            {{ $change['old_value'] }}
                        @endif
                    </td>
                    <td>
                        @if($change['new_value'] === null)
                            <em class="text-muted">N/A</em>
                        @elseif(is_array($change['new_value']))
                            <pre class="mb-0">{{ json_encode($change['new_value'], JSON_PRETTY_PRINT) }}</pre>
                        @else
                            {{ $change['new_value'] }}
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="text-center text-muted">No changes recorded</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($log->ip_address || $log->user_agent)
    <div class="mt-3">
        <small class="text-muted">
            @if($log->ip_address)
                <strong>IP Address:</strong> {{ $log->ip_address }}
            @endif
            @if($log->user_agent)
                <br><strong>User Agent:</strong> {{ Str::limit($log->user_agent, 100) }}
            @endif
        </small>
    </div>
@endif
