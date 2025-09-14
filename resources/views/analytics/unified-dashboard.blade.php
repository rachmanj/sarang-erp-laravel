@extends('adminlte::page')

@section('title', 'Unified Analytics Dashboard')

@section('content_header')
    <h1>Unified Analytics Dashboard</h1>
    <p class="text-muted">Comprehensive view of COGS, Supplier Analytics, and Business Intelligence</p>
@stop

@section('content')
    <div class="row">
        <!-- Key Performance Indicators -->
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ number_format($dashboardData['business_intelligence']['revenue_growth'], 1) }}%</h3>
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
                    <h3>{{ number_format($dashboardData['business_intelligence']['profit_margin'], 1) }}%</h3>
                    <p>Profit Margin</p>
                </div>
                <div class="icon">
                    <i class="fas fa-percentage"></i>
                </div>
                <a href="{{ route('cogs.margin-analysis') }}" class="small-box-footer">
                    More info <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ number_format($dashboardData['supplier_summary']['average_performance_score'], 1) }}</h3>
                    <p>Supplier Performance</p>
                </div>
                <div class="icon">
                    <i class="fas fa-truck"></i>
                </div>
                <a href="{{ route('supplier-analytics.performance') }}" class="small-box-footer">
                    More info <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ number_format($dashboardData['business_intelligence']['roi'], 1) }}%</h3>
                    <p>Return on Investment</p>
                </div>
                <div class="icon">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <a href="{{ route('business-intelligence.insights') }}" class="small-box-footer">
                    More info <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- COGS Summary -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">COGS Analysis</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <h5>Total COGS</h5>
                            <p class="text-lg">${{ number_format($dashboardData['cogs_summary']['total_cogs']) }}</p>
                        </div>
                        <div class="col-6">
                            <h5>Avg Margin</h5>
                            <p class="text-lg">{{ number_format($dashboardData['cogs_summary']['average_margin'], 1) }}%</p>
                        </div>
                    </div>

                    <h6>Top Cost Products</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Cost</th>
                                    <th>Margin</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($dashboardData['cogs_summary']['top_cost_products'] as $product)
                                    <tr>
                                        <td>{{ $product['name'] }}</td>
                                        <td>${{ number_format($product['cost']) }}</td>
                                        <td>
                                            <span
                                                class="badge badge-{{ $product['margin'] > 25 ? 'success' : 'warning' }}">
                                                {{ number_format($product['margin'], 1) }}%
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        <a href="{{ route('cogs.index') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-chart-bar"></i> View COGS Details
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Supplier Analytics Summary -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Supplier Analytics</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <h5>Active Suppliers</h5>
                            <p class="text-lg">
                                {{ $dashboardData['supplier_summary']['active_suppliers'] }}/{{ $dashboardData['supplier_summary']['total_suppliers'] }}
                            </p>
                        </div>
                        <div class="col-6">
                            <h5>Avg Performance</h5>
                            <p class="text-lg">
                                {{ number_format($dashboardData['supplier_summary']['average_performance_score'], 1) }}/100
                            </p>
                        </div>
                    </div>

                    <h6>Top Performers</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Supplier</th>
                                    <th>Score</th>
                                    <th>Efficiency</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($dashboardData['supplier_summary']['top_performers'] as $supplier)
                                    <tr>
                                        <td>{{ $supplier['name'] }}</td>
                                        <td>
                                            <span class="badge badge-{{ $supplier['score'] > 90 ? 'success' : 'info' }}">
                                                {{ number_format($supplier['score'], 1) }}
                                            </span>
                                        </td>
                                        <td>{{ number_format($supplier['cost_efficiency'], 1) }}%</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        <a href="{{ route('supplier-analytics.index') }}" class="btn btn-success btn-sm">
                            <i class="fas fa-truck"></i> View Supplier Analytics
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Business Intelligence Summary -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Business Intelligence</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <h5>Customer Retention</h5>
                            <p class="text-lg">
                                {{ number_format($dashboardData['business_intelligence']['customer_retention'], 1) }}%</p>
                        </div>
                        <div class="col-6">
                            <h5>ROI</h5>
                            <p class="text-lg">{{ number_format($dashboardData['business_intelligence']['roi'], 1) }}%</p>
                        </div>
                    </div>

                    <h6>Key Insights</h6>
                    <ul class="list-unstyled">
                        @foreach (array_slice($dashboardData['business_intelligence']['key_insights'], 0, 2) as $insight)
                            <li class="mb-2">
                                <small class="text-muted">{{ $insight }}</small>
                            </li>
                        @endforeach
                    </ul>

                    <div class="mt-3">
                        <a href="{{ route('business-intelligence.index') }}" class="btn btn-info btn-sm">
                            <i class="fas fa-brain"></i> View BI Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Integrated Insights -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Integrated Insights & Recommendations</h3>
                </div>
                <div class="card-body">
                    @forelse($dashboardData['integrated_insights'] as $insight)
                        <div class="alert alert-{{ $insight['priority'] == 'high' ? 'danger' : 'warning' }}">
                            <h6>{{ $insight['title'] }}</h6>
                            <p>{{ $insight['description'] }}</p>
                            <p><strong>Impact:</strong> {{ $insight['impact'] }}</p>
                            <p><strong>Action Required:</strong> {{ $insight['action_required'] }}</p>
                        </div>
                    @empty
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> No critical insights at this time
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Optimization Opportunities -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Optimization Opportunities</h3>
                </div>
                <div class="card-body">
                    @forelse($dashboardData['optimization_opportunities'] as $opportunity)
                        <div class="info-box">
                            <span
                                class="info-box-icon bg-{{ $opportunity['effort_level'] == 'low' ? 'success' : ($opportunity['effort_level'] == 'medium' ? 'warning' : 'danger') }}">
                                <i class="fas fa-lightbulb"></i>
                            </span>
                            <div class="info-box-content">
                                <span class="info-box-text">{{ $opportunity['title'] }}</span>
                                <span
                                    class="info-box-number">${{ number_format($opportunity['potential_savings']) }}</span>
                                <div class="progress">
                                    <div class="progress-bar"
                                        style="width: {{ $opportunity['effort_level'] == 'low' ? '25' : ($opportunity['effort_level'] == 'medium' ? '50' : '75') }}%">
                                    </div>
                                </div>
                                <span class="progress-description">{{ $opportunity['timeline'] }}</span>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted">No optimization opportunities identified</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Performance Metrics -->
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Performance Metrics Overview</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <h6>Financial Metrics</h6>
                            <ul class="list-unstyled">
                                <li><strong>Revenue:</strong>
                                    ${{ number_format($dashboardData['performance_metrics']['financial']['revenue']) }}
                                </li>
                                <li><strong>Costs:</strong>
                                    ${{ number_format($dashboardData['performance_metrics']['financial']['costs']) }}</li>
                                <li><strong>Profit:</strong>
                                    ${{ number_format($dashboardData['performance_metrics']['financial']['profit']) }}</li>
                                <li><strong>Margin:</strong>
                                    {{ number_format($dashboardData['performance_metrics']['financial']['margin_percentage'], 1) }}%
                                </li>
                            </ul>
                        </div>
                        <div class="col-md-3">
                            <h6>Operational Metrics</h6>
                            <ul class="list-unstyled">
                                <li><strong>Inventory Turnover:</strong>
                                    {{ number_format($dashboardData['performance_metrics']['operational']['inventory_turnover'], 1) }}
                                </li>
                                <li><strong>Supplier Performance:</strong>
                                    {{ number_format($dashboardData['performance_metrics']['operational']['supplier_performance'], 1) }}/100
                                </li>
                                <li><strong>Cost Allocation:</strong>
                                    {{ number_format($dashboardData['performance_metrics']['operational']['cost_allocation_accuracy'], 1) }}%
                                </li>
                                <li><strong>Customer Satisfaction:</strong>
                                    {{ number_format($dashboardData['performance_metrics']['operational']['customer_satisfaction'], 1) }}%
                                </li>
                            </ul>
                        </div>
                        <div class="col-md-3">
                            <h6>Efficiency Metrics</h6>
                            <ul class="list-unstyled">
                                <li><strong>Procurement:</strong>
                                    {{ number_format($dashboardData['performance_metrics']['efficiency']['procurement_efficiency'], 1) }}%
                                </li>
                                <li><strong>Cost Control:</strong>
                                    {{ number_format($dashboardData['performance_metrics']['efficiency']['cost_control'], 1) }}%
                                </li>
                                <li><strong>Margin Optimization:</strong>
                                    {{ number_format($dashboardData['performance_metrics']['efficiency']['margin_optimization'], 1) }}%
                                </li>
                                <li><strong>Supplier Management:</strong>
                                    {{ number_format($dashboardData['performance_metrics']['efficiency']['supplier_management'], 1) }}%
                                </li>
                            </ul>
                        </div>
                        <div class="col-md-3">
                            <h6>Quick Actions</h6>
                            <div class="btn-group-vertical btn-block">
                                <button class="btn btn-primary btn-sm mb-2" onclick="generateIntegratedReport()">
                                    <i class="fas fa-file-alt"></i> Generate Report
                                </button>
                                <a href="{{ route('cogs.index') }}" class="btn btn-info btn-sm mb-2">
                                    <i class="fas fa-chart-bar"></i> COGS Analysis
                                </a>
                                <a href="{{ route('supplier-analytics.index') }}" class="btn btn-success btn-sm mb-2">
                                    <i class="fas fa-truck"></i> Supplier Analytics
                                </a>
                                <a href="{{ route('business-intelligence.index') }}" class="btn btn-warning btn-sm">
                                    <i class="fas fa-brain"></i> Business Intelligence
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('js')
    <script>
        function generateIntegratedReport() {
            if (confirm('Generate comprehensive integrated analytics report?')) {
                const startDate = '{{ $startDate }}';
                const endDate = '{{ $endDate }}';

                $.ajax({
                    url: '{{ route('analytics.generate-integrated-report') }}',
                    method: 'POST',
                    data: {
                        start_date: startDate,
                        end_date: endDate,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Integrated report generated successfully!');
                            // Optionally redirect to report view or download
                        } else {
                            alert('Error: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('An error occurred while generating the report');
                    }
                });
            }
        }
    </script>
@stop
