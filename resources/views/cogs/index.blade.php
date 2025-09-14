@extends('adminlte::page')

@section('title', 'COGS Dashboard')

@section('content_header')
    <h1>Cost of Goods Sold Dashboard</h1>
@stop

@section('content')
    <div class="row">
        <!-- Current Month COGS -->
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ number_format($currentCOGS, 0) }}</h3>
                    <p>Current Month COGS</p>
                </div>
                <div class="icon">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <a href="{{ route('cogs.cost-history') }}" class="small-box-footer">
                    More info <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <!-- Last Month COGS -->
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ number_format($lastCOGS, 0) }}</h3>
                    <p>Last Month COGS</p>
                </div>
                <div class="icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <a href="{{ route('cogs.product-costs') }}" class="small-box-footer">
                    More info <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <!-- Percentage Change -->
        <div class="col-lg-3 col-6">
            <div class="small-box {{ $percentageChange >= 0 ? 'bg-warning' : 'bg-danger' }}">
                <div class="inner">
                    <h3>{{ number_format($percentageChange, 1) }}%</h3>
                    <p>Month-over-Month Change</p>
                </div>
                <div class="icon">
                    <i class="fas fa-percentage"></i>
                </div>
                <a href="{{ route('cogs.margin-analysis') }}" class="small-box-footer">
                    More info <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <!-- Unallocated Costs -->
        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ number_format($unallocatedCosts, 0) }}</h3>
                    <p>Unallocated Costs</p>
                </div>
                <div class="icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <a href="{{ route('cogs.cost-history', ['allocated' => 'no']) }}" class="small-box-footer">
                    More info <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Top Products by Cost -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Top 10 Products by Cost</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Total Cost</th>
                                    <th>Quantity</th>
                                    <th>Average Unit Cost</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topProductsByCost as $product)
                                    <tr>
                                        <td>{{ $product->inventoryItem->name ?? 'Unknown' }}</td>
                                        <td>{{ number_format($product->total_cost, 2) }}</td>
                                        <td>{{ number_format($product->total_quantity, 0) }}</td>
                                        <td>{{ number_format($product->average_unit_cost, 2) }}</td>
                                        <td>
                                            <a href="{{ route('cogs.product-costs', ['item_id' => $product->inventory_item_id]) }}"
                                                class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i> View Details
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center">No data available</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cost Optimization Opportunities -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Cost Optimization Opportunities</h3>
                </div>
                <div class="card-body">
                    @forelse($optimizationOpportunities as $opportunity)
                        <div class="alert alert-warning">
                            <h5>{{ ucfirst(str_replace('_', ' ', $opportunity['type'])) }}</h5>
                            @if (isset($opportunity['product_name']))
                                <p><strong>Product:</strong> {{ $opportunity['product_name'] }}</p>
                            @endif
                            @if (isset($opportunity['current_cost']))
                                <p><strong>Current Cost:</strong> {{ number_format($opportunity['current_cost'], 2) }}</p>
                            @endif
                            @if (isset($opportunity['amount']))
                                <p><strong>Amount:</strong> {{ number_format($opportunity['amount'], 2) }}</p>
                            @endif
                            <p><strong>Recommendation:</strong> {{ $opportunity['recommendation'] }}</p>
                            @if (isset($opportunity['potential_savings']))
                                <p><strong>Potential Savings:</strong>
                                    {{ number_format($opportunity['potential_savings'], 2) }}</p>
                            @endif
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
                            <a href="{{ route('cogs.cost-history') }}" class="btn btn-primary btn-block">
                                <i class="fas fa-history"></i> View Cost History
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="{{ route('cogs.product-costs') }}" class="btn btn-success btn-block">
                                <i class="fas fa-boxes"></i> Product Costs
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="{{ route('cogs.margin-analysis') }}" class="btn btn-info btn-block">
                                <i class="fas fa-chart-pie"></i> Margin Analysis
                            </a>
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-warning btn-block" onclick="allocateCosts()">
                                <i class="fas fa-calculator"></i> Allocate Costs
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('js')
    <script>
        function allocateCosts() {
            if (confirm('Are you sure you want to allocate indirect costs for the current month?')) {
                $.ajax({
                    url: '{{ route('cogs.allocate') }}',
                    method: 'POST',
                    data: {
                        start_date: '{{ Carbon\Carbon::now()->startOfMonth()->format('Y-m-d') }}',
                        end_date: '{{ Carbon\Carbon::now()->endOfMonth()->format('Y-m-d') }}',
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Costs allocated successfully!');
                            location.reload();
                        } else {
                            alert('Error: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('An error occurred while allocating costs');
                    }
                });
            }
        }
    </script>
@stop
