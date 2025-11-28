<!DOCTYPE html>
<html>
<head>
    <title>Audit Log Report</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 10px; }
        .header { text-align: center; margin-bottom: 20px; }
        .company-name { font-size: 18px; font-weight: bold; }
        .report-title { font-size: 14px; margin-top: 10px; }
        .summary { margin-bottom: 20px; }
        .summary table { width: 100%; border-collapse: collapse; }
        .summary td { padding: 5px; border: 1px solid #ddd; }
        .summary td:first-child { font-weight: bold; width: 30%; }
        .table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .table th, .table td { border: 1px solid #ddd; padding: 5px; text-align: left; }
        .table th { background-color: #4472C4; color: white; }
        .footer { margin-top: 20px; text-align: center; font-size: 8px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ config('app.name') }}</div>
        <div class="report-title">Audit Log Report</div>
        <div>Generated: {{ $generated_at->format('Y-m-d H:i:s') }}</div>
    </div>

    <div class="summary">
        <table>
            <tr>
                <td>Total Records</td>
                <td>{{ $summary['total_records'] }}</td>
            </tr>
            <tr>
                <td>Date Range</td>
                <td>
                    @if($summary['date_range']['from'] && $summary['date_range']['to'])
                        {{ $summary['date_range']['from']->format('Y-m-d') }} to {{ $summary['date_range']['to']->format('Y-m-d') }}
                    @else
                        N/A
                    @endif
                </td>
            </tr>
            <tr>
                <td>Unique Users</td>
                <td>{{ $summary['unique_users'] }}</td>
            </tr>
        </table>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Timestamp</th>
                <th>User</th>
                <th>Action</th>
                <th>Entity Type</th>
                <th>Entity ID</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            @foreach($logs as $log)
            <tr>
                <td>{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                <td>{{ $log->user ? $log->user->name : 'System' }}</td>
                <td>{{ ucfirst($log->action) }}</td>
                <td>{{ ucwords(str_replace('_', ' ', $log->entity_type)) }}</td>
                <td>{{ $log->entity_id }}</td>
                <td>{{ Str::limit($log->description ?? '', 50) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        This report was generated automatically by {{ config('app.name') }} Audit Log System.
    </div>
</body>
</html>
