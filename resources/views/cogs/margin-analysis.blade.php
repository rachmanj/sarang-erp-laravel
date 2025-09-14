@extends('adminlte::page')

@section('title', 'Margin Analysis')

@section('content_header')
    <h1>Margin Analysis</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Margin Analysis</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Filters -->
                    <form method="GET" action="{{ route('cogs.margin-analysis') }}" class="mb-4">
                        <div class="row">
                            <div class="col-md-2">
                                <select name="type" class="form-control">
                                    <option value="">All Types</option>
                                    <option value="product" {{ request('type') == 'product' ? 'selected' : '' }}>Product
                                    </option>
                                    <option value="customer" {{ request('type') == 'customer' ? 'selected' : '' }}>Customer
                                    </option>
                                    <option value="supplier" {{ request('type') == 'supplier' ? 'selected' : '' }}>Supplier
                                    </option>
                                    <option value="period" {{ request('type') == 'period' ? 'selected' : '' }}>Period
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-2">
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
                                <input type="number" name="margin_threshold" class="form-control"
                                    value="{{ request('margin_threshold') }}" placeholder="Min Margin %">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary">Filter</button>
                                <a href="{{ route('cogs.margin-analysis') }}" class="btn btn-secondary">Clear</a>
                            </div>
                        </div>
                    </form>

                    <!-- Margin Analysis Table -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Product/Customer</th>
                                    <th>Analysis Date</th>
                                    <th>Revenue</th>
                                    <th>COGS</th>
                                    <th>Gross Margin</th>
                                    <th>Gross Margin %</th>
                                    <th>Operating Expenses</th>
                                    <th>Net Margin</th>
                                    <th>Net Margin %</th>
                                    <th>Quantity Sold</th>
                                    <th>Profitability Rating</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($marginAnalyses as $analysis)
                                    <tr>
                                        <td>
                                            <span
                                                class="badge badge-{{ $analysis->analysis_type == 'product' ? 'primary' : ($analysis->analysis_type == 'customer' ? 'success' : 'info') }}">
                                                {{ ucfirst($analysis->analysis_type) }}
                                            </span>
                                        </td>
                                        <td>
                                            @if ($analysis->analysis_type == 'product')
                                                {{ $analysis->inventoryItem->name ?? 'Unknown' }}
                                            @elseif($analysis->analysis_type == 'customer')
                                                {{ $analysis->customer->name ?? 'Unknown' }}
                                            @elseif($analysis->analysis_type == 'supplier')
                                                {{ $analysis->supplier->name ?? 'Unknown' }}
                                            @else
                                                Period Analysis
                                            @endif
                                        </td>
                                        <td>{{ $analysis->analysis_date->format('Y-m-d') }}</td>
                                        <td>{{ number_format($analysis->revenue, 2) }}</td>
                                        <td>{{ number_format($analysis->cost_of_goods_sold, 2) }}</td>
                                        <td>{{ number_format($analysis->gross_margin, 2) }}</td>
                                        <td>
                                            <span
                                                class="badge badge-{{ $analysis->gross_margin_percentage >= 30 ? 'success' : ($analysis->gross_margin_percentage >= 20 ? 'warning' : 'danger') }}">
                                                {{ number_format($analysis->gross_margin_percentage, 1) }}%
                                            </span>
                                        </td>
                                        <td>{{ number_format($analysis->operating_expenses, 2) }}</td>
                                        <td>{{ number_format($analysis->net_margin, 2) }}</td>
                                        <td>
                                            <span
                                                class="badge badge-{{ $analysis->net_margin_percentage >= 15 ? 'success' : ($analysis->net_margin_percentage >= 10 ? 'warning' : 'danger') }}">
                                                {{ number_format($analysis->net_margin_percentage, 1) }}%
                                            </span>
                                        </td>
                                        <td>{{ number_format($analysis->quantity_sold, 0) }}</td>
                                        <td>
                                            <span
                                                class="badge badge-{{ $analysis->profitability_rating == 'Excellent' ? 'success' : ($analysis->profitability_rating == 'Good' ? 'primary' : ($analysis->profitability_rating == 'Average' ? 'warning' : 'danger')) }}">
                                                {{ $analysis->profitability_rating }}
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-info"
                                                onclick="viewMarginDetails({{ $analysis->id }})">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            @if ($analysis->analysis_type == 'customer')
                                                <button class="btn btn-sm btn-success"
                                                    onclick="calculateCustomerProfitability({{ $analysis->customer_id }})">
                                                    <i class="fas fa-calculator"></i>
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="13" class="text-center">No margin analysis found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-center">
                        {{ $marginAnalyses->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Margin Details Modal -->
    <div class="modal fade" id="marginDetailsModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Margin Details</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="marginDetailsContent">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Customer Profitability Modal -->
    <div class="modal fade" id="customerProfitabilityModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Calculate Customer Profitability</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="customerProfitabilityForm">
                        <input type="hidden" name="customer_id" id="customer_id">
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
                    <button type="button" class="btn btn-primary"
                        onclick="submitCustomerProfitability()">Calculate</button>
                </div>
            </div>
        </div>
    </div>
@stop

@section('js')
    <script>
        function viewMarginDetails(analysisId) {
            $.ajax({
                url: '/cogs/margin-details/' + analysisId,
                method: 'GET',
                success: function(response) {
                    $('#marginDetailsContent').html(response);
                    $('#marginDetailsModal').modal('show');
                },
                error: function() {
                    alert('Error loading margin details');
                }
            });
        }

        function calculateCustomerProfitability(customerId) {
            $('#customer_id').val(customerId);
            $('#customerProfitabilityModal').modal('show');
        }

        function submitCustomerProfitability() {
            const formData = $('#customerProfitabilityForm').serialize();

            $.ajax({
                url: '/cogs/calculate-customer-profitability',
                method: 'POST',
                data: formData + '&_token=' + '{{ csrf_token() }}',
                success: function(response) {
                    if (response.success) {
                        alert('Customer profitability calculated successfully!');
                        $('#customerProfitabilityModal').modal('hide');
                        location.reload();
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('Error calculating customer profitability');
                }
            });
        }
    </script>
@stop
