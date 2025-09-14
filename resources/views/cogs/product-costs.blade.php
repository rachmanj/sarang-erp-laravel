@extends('adminlte::page')

@section('title', 'Product Costs')

@section('content_header')
    <h1>Product Costs</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Product Cost Summaries</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Filters -->
                    <form method="GET" action="{{ route('cogs.product-costs') }}" class="mb-4">
                        <div class="row">
                            <div class="col-md-3">
                                <select name="item_id" class="form-control">
                                    <option value="">All Products</option>
                                    @foreach ($inventoryItems as $item)
                                        <option value="{{ $item->id }}"
                                            {{ request('item_id') == $item->id ? 'selected' : '' }}>
                                            {{ $item->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <input type="date" name="date_from" class="form-control"
                                    value="{{ request('date_from') }}" placeholder="From Date">
                            </div>
                            <div class="col-md-2">
                                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}"
                                    placeholder="To Date">
                            </div>
                            <div class="col-md-2">
                                <select name="valuation_method" class="form-control">
                                    <option value="">All Methods</option>
                                    <option value="fifo" {{ request('valuation_method') == 'fifo' ? 'selected' : '' }}>
                                        FIFO</option>
                                    <option value="lifo" {{ request('valuation_method') == 'lifo' ? 'selected' : '' }}>
                                        LIFO</option>
                                    <option value="weighted_average"
                                        {{ request('valuation_method') == 'weighted_average' ? 'selected' : '' }}>Weighted
                                        Average</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary">Filter</button>
                                <a href="{{ route('cogs.product-costs') }}" class="btn btn-secondary">Clear</a>
                                <button type="button" class="btn btn-success" onclick="calculateCOGS()">Calculate
                                    COGS</button>
                            </div>
                        </div>
                    </form>

                    <!-- Product Costs Table -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Period</th>
                                    <th>Purchase Cost</th>
                                    <th>Freight Cost</th>
                                    <th>Handling Cost</th>
                                    <th>Overhead Cost</th>
                                    <th>Total Cost</th>
                                    <th>Quantity</th>
                                    <th>Avg Unit Cost</th>
                                    <th>Valuation Method</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($productCosts as $cost)
                                    <tr>
                                        <td>{{ $cost->inventoryItem->name ?? 'Unknown' }}</td>
                                        <td>{{ $cost->period_start->format('Y-m-d') }} to
                                            {{ $cost->period_end->format('Y-m-d') }}</td>
                                        <td>{{ number_format($cost->total_purchase_cost, 2) }}</td>
                                        <td>{{ number_format($cost->total_freight_cost, 2) }}</td>
                                        <td>{{ number_format($cost->total_handling_cost, 2) }}</td>
                                        <td>{{ number_format($cost->total_overhead_cost, 2) }}</td>
                                        <td><strong>{{ number_format($cost->total_cost, 2) }}</strong></td>
                                        <td>{{ number_format($cost->total_quantity, 0) }}</td>
                                        <td><strong>{{ number_format($cost->average_unit_cost, 2) }}</strong></td>
                                        <td>
                                            <span class="badge badge-info">{{ strtoupper($cost->valuation_method) }}</span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-info"
                                                onclick="viewCostBreakdown({{ $cost->id }})">
                                                <i class="fas fa-chart-pie"></i>
                                            </button>
                                            <button class="btn btn-sm btn-success"
                                                onclick="viewCostTrends({{ $cost->inventory_item_id }})">
                                                <i class="fas fa-chart-line"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="11" class="text-center">No product costs found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-center">
                        {{ $productCosts->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cost Breakdown Modal -->
    <div class="modal fade" id="costBreakdownModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Cost Breakdown</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="costBreakdownContent">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Cost Trends Modal -->
    <div class="modal fade" id="costTrendsModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Cost Trends</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="costTrendsContent">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Calculate COGS Modal -->
    <div class="modal fade" id="calculateCOGSModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Calculate COGS</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="calculateCOGSForm">
                        <div class="form-group">
                            <label for="item_id">Product</label>
                            <select name="item_id" id="item_id" class="form-control" required>
                                <option value="">Select Product</option>
                                @foreach ($inventoryItems as $item)
                                    <option value="{{ $item->id }}">{{ $item->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="start_date">Start Date</label>
                            <input type="date" name="start_date" id="start_date" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="end_date">End Date</label>
                            <input type="date" name="end_date" id="end_date" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="valuation_method">Valuation Method</label>
                            <select name="valuation_method" id="valuation_method" class="form-control" required>
                                <option value="fifo">FIFO</option>
                                <option value="lifo">LIFO</option>
                                <option value="weighted_average">Weighted Average</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitCOGSCalculation()">Calculate</button>
                </div>
            </div>
        </div>
    </div>
@stop

@section('js')
    <script>
        function viewCostBreakdown(costId) {
            $.ajax({
                url: '/cogs/cost-breakdown/' + costId,
                method: 'GET',
                success: function(response) {
                    $('#costBreakdownContent').html(response);
                    $('#costBreakdownModal').modal('show');
                },
                error: function() {
                    alert('Error loading cost breakdown');
                }
            });
        }

        function viewCostTrends(itemId) {
            $.ajax({
                url: '/cogs/product-cost-trends',
                method: 'GET',
                data: {
                    item_id: itemId,
                    months: 12
                },
                success: function(response) {
                    if (response.success) {
                        $('#costTrendsContent').html('<pre>' + JSON.stringify(response.data, null, 2) +
                            '</pre>');
                        $('#costTrendsModal').modal('show');
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('Error loading cost trends');
                }
            });
        }

        function calculateCOGS() {
            $('#calculateCOGSModal').modal('show');
        }

        function submitCOGSCalculation() {
            const formData = $('#calculateCOGSForm').serialize();

            $.ajax({
                url: '/cogs/calculate-product-cogs',
                method: 'POST',
                data: formData + '&_token=' + '{{ csrf_token() }}',
                success: function(response) {
                    if (response.success) {
                        alert('COGS calculated successfully!');
                        $('#calculateCOGSModal').modal('hide');
                        location.reload();
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('Error calculating COGS');
                }
            });
        }
    </script>
@stop
