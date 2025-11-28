@php
    $colors = [
        'created' => 'success',
        'updated' => 'info',
        'deleted' => 'danger',
        'approved' => 'success',
        'rejected' => 'danger',
        'transferred' => 'warning',
        'adjusted' => 'primary',
    ];
    $color = $colors[$log->action] ?? 'secondary';
@endphp
<span class="badge badge-{{ $color }}">
    {{ ucfirst($log->action) }}
</span>
