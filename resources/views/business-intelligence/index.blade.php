@extends('adminlte::page')

@section('title', 'Business Intelligence Dashboard')

@section('content_header')
    <h1>Business Intelligence Dashboard</h1>
@stop

@section('content')
    <div class="row">
        <!-- Quick Stats -->
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $latestReport ? number_format($latestReport->kpi_metrics['revenue_growth'] ?? 0, 1) : '0' }}%
                    </h3>
                    <p>Revenue Growth</p>
                </div>
                <div class="icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <a href="{{ route('business-intelligence.kpi-dashboard') }}" class="small-box-footer">
                    More info <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $latestReport ? number_format($latestReport->kpi_metrics['profit_margin'] ?? 0, 1) : '0' }}%</h3>
                    <p>Profit Margin</p>
                </div>
                <div class="icon">
                    <i class="fas fa-percentage"></i>
                </div>
                <a href="{{ route('business-intelligence.insights') }}" class="small-box-footer">
                    More info <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $latestReport ? number_format($latestReport->kpi_metrics['customer_retention'] ?? 0, 1) : '0' }}%
                    </h3>
                    <p>Customer Retention</p>
                </div>
                <div class="icon">
                    <i class="fas fa-users"></i>
                </div>
                <a href="{{ route('business-intelligence.reports') }}" class="small-box-footer">
                    More info <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ $latestReport ? number_format($latestReport->kpi_metrics['roi'] ?? 0, 1) : '0' }}%</h3>
                    <p>Return on Investment</p>
                </div>
                <div class="icon">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <a href="{{ route('business-intelligence.reports') }}" class="small-box-footer">
                    More info <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Latest Report Summary -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Latest Analytics Report</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    @if ($latestReport)
                        <div class="row">
                            <div class="col-md-6">
                                <h5>Report Period</h5>
                                <p>{{ $latestReport->period_start->format('M d, Y') }} to
                                    {{ $latestReport->period_end->format('M d, Y') }}</p>

                                <h5>Key Metrics</h5>
                                <ul class="list-unstyled">
                                    <li><strong>Revenue Growth:</strong>
                                        {{ number_format($latestReport->kpi_metrics['revenue_growth'] ?? 0, 1) }}%</li>
                                    <li><strong>Profit Margin:</strong>
                                        {{ number_format($latestReport->kpi_metrics['profit_margin'] ?? 0, 1) }}%</li>
                                    <li><strong>Inventory Turnover:</strong>
                                        {{ number_format($latestReport->kpi_metrics['inventory_turnover'] ?? 0, 2) }}</li>
                                    <li><strong>Supplier Performance:</strong>
                                        {{ number_format($latestReport->kpi_metrics['supplier_performance'] ?? 0, 1) }}/100
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h5>Top Insights</h5>
                                @forelse(array_slice($latestReport->insights, 0, 3) as $insight)
                                    <div
                                        class="alert alert-{{ $insight['impact'] == 'positive' ? 'success' : 'warning' }} alert-sm">
                                        <small>{{ $insight['message'] }}</small>
                                    </div>
                                @empty
                                    <p class="text-muted">No insights available</p>
                                @endforelse
                            </div>
                        </div>
                    @else
                        <div class="alert alert-info">
                            <h5>No Analytics Report Available</h5>
                            <p>Generate your first analytics report to see business intelligence insights.</p>
                            <button class="btn btn-primary" onclick="generateReport()">
                                <i class="fas fa-chart-bar"></i> Generate Report
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Recent Reports -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Recent Reports</h3>
                </div>
                <div class="card-body">
                    @forelse($recentReports as $report)
                        <div class="info-box">
                            <span class="info-box-icon bg-info">
                                <i class="fas fa-chart-bar"></i>
                            </span>
                            <div class="info-box-content">
                                <span class="info-box-text">{{ $report->report_name }}</span>
                                <span class="info-box-number">{{ $report->period_start->format('M d') }} -
                                    {{ $report->period_end->format('M d') }}</span>
                                <div class="progress">
                                    <div class="progress-bar" style="width: 100%"></div>
                                </div>
                                <span class="progress-description">
                                    {{ $report->report_date->format('M d, Y H:i') }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted">No recent reports available</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Quick Actions -->
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Quick Actions</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <a href="{{ route('business-intelligence.reports') }}" class="btn btn-primary btn-block">
                                <i class="fas fa-file-alt"></i> View Reports
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="{{ route('business-intelligence.insights') }}" class="btn btn-success btn-block">
                                <i class="fas fa-lightbulb"></i> Insights & Recommendations
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="{{ route('business-intelligence.kpi-dashboard') }}" class="btn btn-info btn-block">
                                <i class="fas fa-tachometer-alt"></i> KPI Dashboard
                            </a>
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-warning btn-block" onclick="generateReport()">
                                <i class="fas fa-sync"></i> Generate New Report
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Generate Report Modal -->
    <div class="modal fade" id="generateReportModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Generate Analytics Report</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="generateReportForm">
                        <div class="form-group">
                            <label for="start_date">Start Date</label>
                            <input type="date" name="start_date" id="start_date" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="end_date">End Date</label>
                            <input type="date" name="end_date" id="end_date" class="form-control" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitReportGeneration()">Generate
                        Report</button>
                </div>
            </div>
        </div>
    </div>
@stop

@section('js')
    <script>
        function generateReport() {
            // Set default dates (current month)
            const today = new Date();
            const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
            const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);

            document.getElementById('start_date').value = firstDay.toISOString().split('T')[0];
            document.getElementById('end_date').value = lastDay.toISOString().split('T')[0];

            $('#generateReportModal').modal('show');
        }

        function submitReportGeneration() {
            const formData = $('#generateReportForm').serialize();

            $.ajax({
                url: '{{ route('business-intelligence.generate') }}',
                method: 'POST',
                data: formData + '&_token=' + '{{ csrf_token() }}',
                success: function(response) {
                    if (response.success) {
                        alert('Analytics report generated successfully!');
                        $('#generateReportModal').modal('hide');
                        location.reload();
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('An error occurred while generating the report');
                }
            });
        }
    </script>
@stop
