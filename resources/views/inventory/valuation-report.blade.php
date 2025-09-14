@extends('layouts.main')

@section('title_page')
    Inventory Valuation Report
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('inventory.index') }}">Inventory</a></li>
    <li class="breadcrumb-item active">Valuation Report</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">
                        <i class="fas fa-chart-line text-info"></i>
                        Inventory Valuation Report
                    </h3>
                    <div>
                        <a href="{{ route('inventory.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to Inventory
                        </a>
                        <button class="btn btn-success btn-sm" id="exportValuation">
                            <i class="fas fa-download"></i> Export Report
                        </button>
                        <button class="btn btn-primary btn-sm" id="refreshValuation">
                            <i class="fas fa-sync"></i> Refresh
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-primary">
                                    <i class="fas fa-boxes"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Total Items</span>
                                    <span class="info-box-number">{{ $items->count() }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-success">
                                    <i class="fas fa-dollar-sign"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Total Value</span>
                                    <span class="info-box-number">
                                        Rp {{ number_format($items->sum('current_value'), 0) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-info">
                                    <i class="fas fa-chart-bar"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Avg Unit Cost</span>
                                    <span class="info-box-number">
                                        @php
                                            $totalItems = $items->count();
                                            $totalValue = $items->sum('current_value');
                                            $totalStock = $items->sum('current_stock');
                                            $avgUnitCost = $totalStock > 0 ? $totalValue / $totalStock : 0;
                                        @endphp
                                        Rp {{ number_format($avgUnitCost, 2) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-warning">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Low Stock Items</span>
                                    <span class="info-box-number">
                                        {{ $items->filter(function ($item) {return $item->current_stock <= $item->min_stock_level;})->count() }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="valuationTable">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Current Stock</th>
                                    <th>Unit Cost</th>
                                    <th>Total Value</th>
                                    <th>Valuation Method</th>
                                    <th>Last Valuation</th>
                                    <th>Stock Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($items as $item)
                                    <tr>
                                        <td>{{ $item->code }}</td>
                                        <td>
                                            <a href="{{ route('inventory.show', $item->id) }}">
                                                {{ $item->name }}
                                            </a>
                                        </td>
                                        <td>{{ $item->category->name ?? 'N/A' }}</td>
                                        <td>
                                            <span
                                                class="badge badge-{{ $item->current_stock <= 0 ? 'danger' : ($item->current_stock <= $item->min_stock_level ? 'warning' : 'success') }}">
                                                {{ $item->current_stock }} {{ $item->unit_of_measure }}
                                            </span>
                                        </td>
                                        <td>Rp {{ number_format($item->latest_valuation->unit_cost ?? 0, 2) }}</td>
                                        <td>
                                            <strong>Rp {{ number_format($item->current_value, 2) }}</strong>
                                        </td>
                                        <td>
                                            <span class="badge badge-info">
                                                {{ strtoupper($item->valuation_method) }}
                                            </span>
                                        </td>
                                        <td>
                                            @if ($item->latest_valuation)
                                                {{ \Carbon\Carbon::parse($item->latest_valuation->valuation_date)->format('d/m/Y') }}
                                            @else
                                                <span class="text-muted">Never</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($item->current_stock <= 0)
                                                <span class="badge badge-danger">Out of Stock</span>
                                            @elseif($item->current_stock <= $item->min_stock_level)
                                                <span class="badge badge-warning">Low Stock</span>
                                            @else
                                                <span class="badge badge-success">In Stock</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('inventory.show', $item->id) }}" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <button class="btn btn-sm btn-warning btn-adjust-stock"
                                                data-item-id="{{ $item->id }}" data-item-name="{{ $item->name }}">
                                                <i class="fas fa-adjust"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Valuation Methods Distribution</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="valuationMethodChart" width="400" height="200"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Stock Status Distribution</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="stockStatusChart" width="400" height="200"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Top 10 Most Valuable Items</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Rank</th>
                                                    <th>Item</th>
                                                    <th>Stock</th>
                                                    <th>Unit Cost</th>
                                                    <th>Total Value</th>
                                                    <th>% of Total</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php
                                                    $topItems = $items->sortByDesc('current_value')->take(10);
                                                    $totalValue = $items->sum('current_value');
                                                @endphp
                                                @foreach ($topItems as $index => $item)
                                                    <tr>
                                                        <td>{{ $index + 1 }}</td>
                                                        <td>{{ $item->name }}</td>
                                                        <td>{{ $item->current_stock }} {{ $item->unit_of_measure }}</td>
                                                        <td>Rp
                                                            {{ number_format($item->latest_valuation->unit_cost ?? 0, 2) }}
                                                        </td>
                                                        <td><strong>Rp
                                                                {{ number_format($item->current_value, 2) }}</strong></td>
                                                        <td>
                                                            <div class="progress">
                                                                <div class="progress-bar" role="progressbar"
                                                                    style="width: {{ $totalValue > 0 ? ($item->current_value / $totalValue) * 100 : 0 }}%">
                                                                    {{ $totalValue > 0 ? number_format(($item->current_value / $totalValue) * 100, 1) : 0 }}%
                                                                </div>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        $(function() {
            // Initialize DataTable
            $('#valuationTable').DataTable({
                order: [
                    [5, 'desc']
                ], // Sort by total value descending
                pageLength: 25,
                responsive: true
            });

            // Valuation Methods Chart
            const valuationMethodCtx = document.getElementById('valuationMethodChart').getContext('2d');
            const valuationMethodData = {
                @php
                    $fifoCount = $items->where('valuation_method', 'fifo')->count();
                    $lifoCount = $items->where('valuation_method', 'lifo')->count();
                    $weightedCount = $items->where('valuation_method', 'weighted_average')->count();
                @endphp
                labels: ['FIFO', 'LIFO', 'Weighted Average'],
                datasets: [{
                    data: [{{ $fifoCount }}, {{ $lifoCount }}, {{ $weightedCount }}],
                    backgroundColor: ['#007bff', '#28a745', '#ffc107'],
                    borderWidth: 1
                }]
            };
            new Chart(valuationMethodCtx, {
                type: 'doughnut',
                data: valuationMethodData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });

            // Stock Status Chart
            const stockStatusCtx = document.getElementById('stockStatusChart').getContext('2d');
            const stockStatusData = {
                @php
                    $inStockCount = $items
                        ->filter(function ($item) {
                            return $item->current_stock > $item->min_stock_level;
                        })
                        ->count();
                    $lowStockCount = $items
                        ->filter(function ($item) {
                            return $item->current_stock > 0 && $item->current_stock <= $item->min_stock_level;
                        })
                        ->count();
                    $outOfStockCount = $items
                        ->filter(function ($item) {
                            return $item->current_stock <= 0;
                        })
                        ->count();
                @endphp
                labels: ['In Stock', 'Low Stock', 'Out of Stock'],
                datasets: [{
                    data: [{{ $inStockCount }}, {{ $lowStockCount }}, {{ $outOfStockCount }}],
                    backgroundColor: ['#28a745', '#ffc107', '#dc3545'],
                    borderWidth: 1
                }]
            };
            new Chart(stockStatusCtx, {
                type: 'doughnut',
                data: stockStatusData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });

            // Export valuation report
            $('#exportValuation').on('click', function() {
                window.location = '{{ route('inventory.export-valuation') }}';
            });

            // Refresh valuation
            $('#refreshValuation').on('click', function() {
                location.reload();
            });

            // Individual stock adjustment
            $('.btn-adjust-stock').on('click', function() {
                const itemId = $(this).data('item-id');
                const itemName = $(this).data('item-name');

                // Redirect to inventory show page with adjustment modal
                window.location = '{{ route('inventory.show', '') }}/' + itemId;
            });
        });
    </script>
@endsection
