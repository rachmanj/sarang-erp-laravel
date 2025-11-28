@extends('layouts.main')

@section('title_page')
    Activity Dashboard
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">Activity Dashboard</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-chart-line mr-2"></i>
                        Activity Dashboard
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('activity-dashboard.index', ['refresh' => true]) }}" class="btn btn-sm btn-primary">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ number_format($dashboardData['statistics']['total_logs'] ?? 0) }}</h3>
                    <p>Total Logs</p>
                </div>
                <div class="icon">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <div class="small-box-footer">
                    All time
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ number_format($dashboardData['statistics']['today']['count'] ?? 0) }}</h3>
                    <p>Today's Activity</p>
                    @if(isset($dashboardData['statistics']['today']['change']))
                        <small>
                            <i class="fas fa-arrow-{{ $dashboardData['statistics']['today']['change']['direction'] === 'up' ? 'up' : ($dashboardData['statistics']['today']['change']['direction'] === 'down' ? 'down' : 'right') }}"></i>
                            {{ abs($dashboardData['statistics']['today']['change']['percentage']) }}% 
                            {{ $dashboardData['statistics']['today']['change']['direction'] === 'up' ? 'increase' : ($dashboardData['statistics']['today']['change']['direction'] === 'down' ? 'decrease' : '') }} from yesterday
                        </small>
                    @endif
                </div>
                <div class="icon">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div class="small-box-footer">
                    Last 24 hours
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ number_format($dashboardData['statistics']['this_week']['count'] ?? 0) }}</h3>
                    <p>This Week</p>
                    @if(isset($dashboardData['statistics']['this_week']['change']))
                        <small>
                            <i class="fas fa-arrow-{{ $dashboardData['statistics']['this_week']['change']['direction'] === 'up' ? 'up' : ($dashboardData['statistics']['this_week']['change']['direction'] === 'down' ? 'down' : 'right') }}"></i>
                            {{ abs($dashboardData['statistics']['this_week']['change']['percentage']) }}% 
                            {{ $dashboardData['statistics']['this_week']['change']['direction'] === 'up' ? 'increase' : ($dashboardData['statistics']['this_week']['change']['direction'] === 'down' ? 'decrease' : '') }} from last week
                        </small>
                    @endif
                </div>
                <div class="icon">
                    <i class="fas fa-calendar-week"></i>
                </div>
                <div class="small-box-footer">
                    This week
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ number_format($dashboardData['statistics']['this_month']['count'] ?? 0) }}</h3>
                    <p>This Month</p>
                    @if(isset($dashboardData['statistics']['this_month']['change']))
                        <small>
                            <i class="fas fa-arrow-{{ $dashboardData['statistics']['this_month']['change']['direction'] === 'up' ? 'up' : ($dashboardData['statistics']['this_month']['change']['direction'] === 'down' ? 'down' : 'right') }}"></i>
                            {{ abs($dashboardData['statistics']['this_month']['change']['percentage']) }}% 
                            {{ $dashboardData['statistics']['this_month']['change']['direction'] === 'up' ? 'increase' : ($dashboardData['statistics']['this_month']['change']['direction'] === 'down' ? 'decrease' : '') }} from last month
                        </small>
                    @endif
                </div>
                <div class="icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="small-box-footer">
                    This month
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-secondary">
                <div class="inner">
                    <h3>{{ number_format($dashboardData['statistics']['unique_users'] ?? 0) }}</h3>
                    <p>Active Users</p>
                </div>
                <div class="icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="small-box-footer">
                    Last 7 days
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ number_format($dashboardData['statistics']['unique_entities'] ?? 0) }}</h3>
                    <p>Entity Types</p>
                </div>
                <div class="icon">
                    <i class="fas fa-cubes"></i>
                </div>
                <div class="small-box-footer">
                    Tracked entities
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row">
        <!-- Activity Trends Chart -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-chart-line mr-2"></i>
                        Activity Trends (Last 30 Days)
                    </h3>
                </div>
                <div class="card-body">
                    <canvas id="activityTrendsChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                </div>
            </div>
        </div>

        <!-- Activity by Module Chart -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-chart-pie mr-2"></i>
                        Activity by Module
                    </h3>
                </div>
                <div class="card-body">
                    <canvas id="activityByModuleChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Hourly Activity and Recent Activity -->
    <div class="row">
        <!-- Hourly Activity Chart -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-chart-bar mr-2"></i>
                        Hourly Activity (Last 24 Hours)
                    </h3>
                </div>
                <div class="card-body">
                    <canvas id="hourlyActivityChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                </div>
            </div>
        </div>

        <!-- Recent Activity Feed -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-stream mr-2"></i>
                        Recent Activity Feed
                    </h3>
                    <div class="card-tools">
                        <span class="badge badge-info" id="activity-count">{{ count($dashboardData['recent_activity'] ?? []) }}</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-sm table-hover">
                            <thead class="thead-light sticky-top">
                                <tr>
                                    <th>Time</th>
                                    <th>User</th>
                                    <th>Action</th>
                                    <th>Entity</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody id="recent-activity-body">
                                @forelse($dashboardData['recent_activity'] ?? [] as $activity)
                                    <tr>
                                        <td>
                                            <small>{{ \Carbon\Carbon::parse($activity['timestamp'])->format('H:i:s') }}</small>
                                        </td>
                                        <td>{{ $activity['user'] }}</td>
                                        <td>
                                            <span class="badge badge-{{ $activity['action_color'] ?? 'secondary' }}">
                                                {{ ucfirst($activity['action']) }}
                                            </span>
                                        </td>
                                        <td>
                                            <small>{{ ucwords(str_replace('_', ' ', $activity['entity_type'])) }}</small>
                                        </td>
                                        <td>
                                            <small>{{ Str::limit($activity['description'] ?? '', 40) }}</small>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">No recent activity</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Active Users and Most Modified Entities -->
    <div class="row">
        <!-- Top Active Users -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-user-chart mr-2"></i>
                        Top Active Users (Last 30 Days)
                    </h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th class="text-right">Activities</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($dashboardData['top_active_users'] ?? [] as $user)
                                <tr>
                                    <td>{{ $user['user_name'] }}</td>
                                    <td class="text-right">
                                        <span class="badge badge-info">{{ number_format($user['count']) }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-center text-muted">No data available</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Most Modified Entities -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-edit mr-2"></i>
                        Most Modified Entities (Last 30 Days)
                    </h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Entity</th>
                                <th class="text-right">Changes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($dashboardData['most_modified_entities'] ?? [] as $entity)
                                <tr>
                                    <td>
                                        <small>{{ ucwords(str_replace('_', ' ', $entity['entity_type'])) }}</small>
                                        <br>
                                        <strong>{{ $entity['entity_name'] }}</strong>
                                    </td>
                                    <td class="text-right">
                                        <span class="badge badge-warning">{{ number_format($entity['count']) }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-center text-muted">No data available</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
    $(document).ready(function() {
        // Activity Trends Chart
        const trendsCtx = document.getElementById('activityTrendsChart').getContext('2d');
        const trendsData = @json($dashboardData['activity_trends'] ?? []);
        
        new Chart(trendsCtx, {
            type: 'line',
            data: {
                labels: trendsData.map(item => item.date_display),
                datasets: [{
                    label: 'Activities',
                    data: trendsData.map(item => item.count),
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Activity by Module Chart
        const moduleCtx = document.getElementById('activityByModuleChart').getContext('2d');
        const moduleData = @json($dashboardData['activity_by_module'] ?? []);
        
        new Chart(moduleCtx, {
            type: 'doughnut',
            data: {
                labels: moduleData.map(item => item.entity_type_display),
                datasets: [{
                    data: moduleData.map(item => item.count),
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.8)',
                        'rgba(54, 162, 235, 0.8)',
                        'rgba(255, 206, 86, 0.8)',
                        'rgba(75, 192, 192, 0.8)',
                        'rgba(153, 102, 255, 0.8)',
                        'rgba(255, 159, 64, 0.8)',
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
            }
        });

        // Hourly Activity Chart
        const hourlyCtx = document.getElementById('hourlyActivityChart').getContext('2d');
        const hourlyData = @json($dashboardData['hourly_activity'] ?? []);
        
        new Chart(hourlyCtx, {
            type: 'bar',
            data: {
                labels: hourlyData.map(item => item.hour),
                datasets: [{
                    label: 'Activities',
                    data: hourlyData.map(item => item.count),
                    backgroundColor: 'rgba(54, 162, 235, 0.8)',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Auto-refresh recent activity every 30 seconds
        setInterval(function() {
            $.ajax({
                url: '{{ route("activity-dashboard.recent-activity") }}',
                method: 'GET',
                success: function(response) {
                    updateRecentActivity(response.activity);
                }
            });
        }, 30000);
    });

    function updateRecentActivity(activities) {
        const tbody = $('#recent-activity-body');
        tbody.empty();
        
        if (activities.length === 0) {
            tbody.append('<tr><td colspan="5" class="text-center text-muted">No recent activity</td></tr>');
            return;
        }

        activities.forEach(function(activity) {
            const time = new Date(activity.timestamp).toLocaleTimeString('en-US', {hour: '2-digit', minute: '2-digit', second: '2-digit'});
            const row = `
                <tr>
                    <td><small>${time}</small></td>
                    <td>${activity.user}</td>
                    <td><span class="badge badge-${activity.action_color || 'secondary'}">${activity.action.charAt(0).toUpperCase() + activity.action.slice(1)}</span></td>
                    <td><small>${activity.entity_type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}</small></td>
                    <td><small>${activity.description ? (activity.description.length > 40 ? activity.description.substring(0, 40) + '...' : activity.description) : ''}</small></td>
                </tr>
            `;
            tbody.prepend(row);
        });

        $('#activity-count').text(activities.length);
    }
</script>
@endpush
