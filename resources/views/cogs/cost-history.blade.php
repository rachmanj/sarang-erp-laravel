@extends('adminlte::page')

@section('title', 'Cost History')

@section('content_header')
    <h1>Cost History</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Cost History</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Filters -->
                    <form method="GET" action="{{ route('cogs.cost-history') }}" class="mb-4">
                        <div class="row">
                            <div class="col-md-2">
                                <select name="type" class="form-control">
                                    <option value="">All Types</option>
                                    <option value="purchase" {{ request('type') == 'purchase' ? 'selected' : '' }}>Purchase
                                    </option>
                                    <option value="freight" {{ request('type') == 'freight' ? 'selected' : '' }}>Freight
                                    </option>
                                    <option value="handling" {{ request('type') == 'handling' ? 'selected' : '' }}>Handling
                                    </option>
                                    <option value="overhead" {{ request('type') == 'overhead' ? 'selected' : '' }}>Overhead
                                    </option>
                                    <option value="adjustment" {{ request('type') == 'adjustment' ? 'selected' : '' }}>
                                        Adjustment</option>
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
                                <select name="allocated" class="form-control">
                                    <option value="">All</option>
                                    <option value="yes" {{ request('allocated') == 'yes' ? 'selected' : '' }}>Allocated
                                    </option>
                                    <option value="no" {{ request('allocated') == 'no' ? 'selected' : '' }}>Unallocated
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary">Filter</button>
                                <a href="{{ route('cogs.cost-history') }}" class="btn btn-secondary">Clear</a>
                            </div>
                        </div>
                    </form>

                    <!-- Cost History Table -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Transaction Code</th>
                                    <th>Type</th>
                                    <th>Product</th>
                                    <th>Category</th>
                                    <th>Quantity</th>
                                    <th>Unit Cost</th>
                                    <th>Total Cost</th>
                                    <th>Allocated Cost</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($costHistories as $cost)
                                    <tr>
                                        <td>{{ $cost->transaction_code }}</td>
                                        <td>
                                            <span
                                                class="badge badge-{{ $cost->transaction_type == 'purchase' ? 'primary' : ($cost->transaction_type == 'freight' ? 'info' : 'warning') }}">
                                                {{ ucfirst($cost->transaction_type) }}
                                            </span>
                                        </td>
                                        <td>{{ $cost->inventoryItem->name ?? 'N/A' }}</td>
                                        <td>{{ $cost->costCategory->name ?? 'N/A' }}</td>
                                        <td>{{ number_format($cost->quantity, 2) }}</td>
                                        <td>{{ number_format($cost->unit_cost, 2) }}</td>
                                        <td>{{ number_format($cost->total_cost, 2) }}</td>
                                        <td>{{ number_format($cost->allocated_cost, 2) }}</td>
                                        <td>{{ $cost->transaction_date->format('Y-m-d') }}</td>
                                        <td>
                                            @if ($cost->is_fully_allocated)
                                                <span class="badge badge-success">Fully Allocated</span>
                                            @elseif($cost->allocated_cost > 0)
                                                <span class="badge badge-warning">Partially Allocated</span>
                                            @else
                                                <span class="badge badge-danger">Unallocated</span>
                                            @endif
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-info"
                                                onclick="viewCostDetails({{ $cost->id }})">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="11" class="text-center">No cost history found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-center">
                        {{ $costHistories->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cost Details Modal -->
    <div class="modal fade" id="costDetailsModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Cost Details</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="costDetailsContent">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>
    </div>
@stop

@section('js')
    <script>
        function viewCostDetails(costId) {
            $.ajax({
                url: '/cogs/cost-details/' + costId,
                method: 'GET',
                success: function(response) {
                    $('#costDetailsContent').html(response);
                    $('#costDetailsModal').modal('show');
                },
                error: function() {
                    alert('Error loading cost details');
                }
            });
        }
    </script>
@stop
