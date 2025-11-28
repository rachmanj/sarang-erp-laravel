<!DOCTYPE html>
<html>
<head>
    <title>Audit Logs Export</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>Audit Logs Export</h2>
        <p>Generated: {{ now()->format('Y-m-d H:i:s') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Timestamp</th>
                <th>User</th>
                <th>Action</th>
                <th>Entity Type</th>
                <th>Entity ID</th>
                <th>Description</th>
                <th>IP Address</th>
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
                    <td>{{ $log->ip_address ?? 'N/A' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
