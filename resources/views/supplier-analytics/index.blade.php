@extends('adminlte::page')

@section('title', 'Supplier Analytics Dashboard')

@section('content_header')
    <h1>Supplier Analytics Dashboard</h1>
@stop

@section('content')
    <div class="row">
        <!-- Total Suppliers -->
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $dashboardData['summary']['total_suppliers'] }}</h3>
                    <p>Total Suppliers</p>
                </div>
                <div class="icon">
                    <i class="fas fa-truck"></i>
                </div>
                <a href="{{ route('supplier-analytics.performance') }}" class="small-box-footer">
                    More info <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <!-- Active Suppliers -->
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $dashboardData['summary']['active_suppliers'] }}</h3>
                    <p>Active Suppliers</p>
                </div>
                <div class="icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <a href="{{ route('supplier-analytics.comparisons') }}" class="small-box-footer">
                    More info <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <!-- Average Performance -->
        <div class="col-lg-3 col-6">
            <div
                class="small-box {{ $dashboardData['summary']['average_performance_score'] >= 70 ? 'bg-success' : ($dashboardData['summary']['average_performance_score'] >= 50 ? 'bg-warning' : 'bg-danger') }}">
                <div class="inner">
                    <h3>{{ number_format($dashboardData['summary']['average_performance_score'], 1) }}</h3>
                    <p>Avg Performance Score</p>
                </div>
                <div class="icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <a href="{{ route('supplier-analytics.optimization') }}" class="small-box-footer">
                    More info <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <!-- Underperformers -->
        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ $dashboardData['summary']['underperformers_count'] }}</h3>
                    <p>Underperformers</p>
                </div>
                <div class="icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <a href="{{ route('supplier-analytics.optimization') }}" class="small-box-footer">
                    More info <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Top Performers -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Top 5 Performing Suppliers</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Supplier</th>
                                    <th>Performance Score</th>
                                    <th>Grade</th>
                                    <th>Total Orders</th>
                                    <th>Total Cost</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($dashboardData['top_performers'] as $performer)
                                    <tr>
                                        <td>{{ $performer['supplier']->name ?? 'Unknown' }}</td>
                                        <td>
                                            <span
                                                class="badge badge-{{ $performer['score'] >= 80 ? 'success' : ($performer['score'] >= 60 ? 'warning' : 'danger') }}">
                                                {{ number_format($performer['score'], 1) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-info">{{ $performer['grade'] }}</span>
                                        </td>
                                        <td>{{ $performer['supplier']->purchaseOrders->count() ?? 0 }}</td>
                                        <td>{{ number_format($performer['supplier']->purchaseOrders->sum('total_amount') ?? 0, 2) }}
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-info"
                                                onclick="viewSupplierDetails({{ $performer['supplier']->id }})">
                                                <i class="fas fa-eye"></i> View Details
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">No performance data available</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
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
                    @forelse($dashboardData['opportunities'] as $opportunity)
                        <div class="alert alert-{{ $opportunity['priority'] == 'high' ? 'danger' : 'warning' }}">
                            <h6>{{ ucfirst(str_replace('_', ' ', $opportunity['type'])) }}</h6>
                            <p><strong>Supplier:</strong> {{ $opportunity['supplier_name'] }}</p>
                            @if (isset($opportunity['current_score']))
                                <p><strong>Current Score:</strong> {{ number_format($opportunity['current_score'], 1) }}
                                </p>
                            @endif
                            @if (isset($opportunity['potential_savings']))
                                <p><strong>Potential Savings:</strong>
                                    {{ number_format($opportunity['potential_savings'], 2) }}</p>
                            @endif
                            <p><strong>Recommendation:</strong> {{ $opportunity['recommendation'] }}</p>
                        </div>
                    @empty
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> No optimization opportunities found
                        </div>
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
                            <a href="{{ route('supplier-analytics.performance') }}" class="btn btn-primary btn-block">
                                <i class="fas fa-chart-bar"></i> Performance Analysis
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="{{ route('supplier-analytics.comparisons') }}" class="btn btn-success btn-block">
                                <i class="fas fa-balance-scale"></i> Supplier Comparisons
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="{{ route('supplier-analytics.optimization') }}" class="btn btn-warning btn-block">
                                <i class="fas fa-lightbulb"></i> Optimization Opportunities
                            </a>
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-info btn-block" onclick="generateAnalytics()">
                                <i class="fas fa-sync"></i> Generate Analytics
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Supplier Details Modal -->
    <div class="modal fade" id="supplierDetailsModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Supplier Details</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="supplierDetailsContent">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>
    </div>
@stop

@section('js')
    <script>
        function viewSupplierDetails(supplierId) {
            $.ajax({
                url: '/supplier-analytics/supplier-details',
                method: 'GET',
                data: {
                    supplier_id: supplierId
                },
                success: function(response) {
                    if (response.success) {
                        $('#supplierDetailsContent').html(`
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Supplier Information</h5>
                            <p><strong>Name:</strong> ${response.data.supplier.name}</p>
                            <p><strong>Contact:</strong> ${response.data.supplier.contact_person || 'N/A'}</p>
                            <p><strong>Email:</strong> ${response.data.supplier.email || 'N/A'}</p>
                            <p><strong>Phone:</strong> ${response.data.supplier.phone || 'N/A'}</p>
                        </div>
                        <div class="col-md-6">
                            <h5>Performance Metrics</h5>
                            ${response.data.analysis ? `
                                    <p><strong>Overall Score:</strong> ${response.data.analysis.overall_score}</p>
                                    <p><strong>Cost Efficiency:</strong> ${response.data.analysis.cost_efficiency_score}</p>
                                    <p><strong>Delivery Performance:</strong> ${response.data.analysis.delivery_performance_score}</p>
                                    <p><strong>Quality Score:</strong> ${response.data.analysis.quality_score}</p>
                                ` : '<p>No analysis data available</p>'}
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <h5>Risk Assessment</h5>
                            <p><strong>Risk Level:</strong> 
                                <span class="badge badge-${response.data.risk_assessment.risk_level === 'high' ? 'danger' : 
                                    (response.data.risk_assessment.risk_level === 'medium' ? 'warning' : 'success')}">
                                    ${response.data.risk_assessment.risk_level.toUpperCase()}
                                </span>
                            </p>
                            <p><strong>Risk Score:</strong> ${response.data.risk_assessment.risk_score}</p>
                            <p><strong>Risk Factors:</strong></p>
                            <ul>
                                ${response.data.risk_assessment.risk_factors.map(factor => `<li>${factor}</li>`).join('')}
                            </ul>
                        </div>
                    </div>
                `);
                        $('#supplierDetailsModal').modal('show');
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('Error loading supplier details');
                }
            });
        }

        function generateAnalytics() {
            if (confirm('Are you sure you want to generate supplier analytics for the current month?')) {
                $.ajax({
                    url: '{{ route('supplier-analytics.generate') }}',
                    method: 'POST',
                    data: {
                        start_date: '{{ Carbon\Carbon::now()->startOfMonth()->format('Y-m-d') }}',
                        end_date: '{{ Carbon\Carbon::now()->endOfMonth()->format('Y-m-d') }}',
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Supplier analytics generated successfully!');
                            location.reload();
                        } else {
                            alert('Error: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('An error occurred while generating analytics');
                    }
                });
            }
        }
    </script>
@stop
