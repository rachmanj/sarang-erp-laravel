@extends('layouts.main')

@section('title_page')
    Inventory Dashboard
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Inventory Dashboard</li>
@endsection

@section('content')
    @php
        $kpis = $dashboardData['kpis'] ?? [];
        $valuationByCategory = $dashboardData['valuation_by_category'] ?? [];
        $stockByWarehouse = $dashboardData['stock_by_warehouse'] ?? [];
        $lowStockAlerts = $dashboardData['low_stock_alerts'] ?? [];
        $recentMovements = $dashboardData['recent_movements'] ?? [];
        $movementSummary = $dashboardData['movement_summary'] ?? [];
    @endphp

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-1">
                Inventory Dashboard - {{ __('As of :date', ['date' => now()->format('d M Y')]) }}
            </h4>
            @if (!empty($filters['warehouse_id']) || !empty($filters['category_id']) || !empty($filters['date_from']) || !empty($filters['date_to']))
                <span class="badge badge-info">Filters active</span>
            @endif
        </div>
        <form method="GET" action="{{ route('inventory.dashboard') }}" class="form-inline">
            <div class="form-group mr-2">
                <label for="warehouse_id" class="mr-2 mb-0">Warehouse</label>
                <select name="warehouse_id" id="warehouse_id" class="form-control form-control-sm">
                    <option value="">All</option>
                    @foreach ($warehousesList as $wh)
                        <option value="{{ $wh->id }}" @selected(($filters['warehouse_id'] ?? null) == $wh->id)>
                            {{ $wh->code }} - {{ $wh->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group mr-2">
                <label for="category_id" class="mr-2 mb-0">Category</label>
                <select name="category_id" id="category_id" class="form-control form-control-sm">
                    <option value="">All</option>
                    @foreach ($categoriesList as $cat)
                        <option value="{{ $cat->id }}" @selected(($filters['category_id'] ?? null) == $cat->id)>
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group mr-2">
                <label for="date_from" class="mr-2 mb-0">From</label>
                <input type="date" name="date_from" id="date_from" class="form-control form-control-sm"
                    value="{{ $filters['date_from'] ?? '' }}">
            </div>
            <div class="form-group mr-2">
                <label for="date_to" class="mr-2 mb-0">To</label>
                <input type="date" name="date_to" id="date_to" class="form-control form-control-sm"
                    value="{{ $filters['date_to'] ?? '' }}">
            </div>
            <button type="submit" class="btn btn-sm btn-primary mr-2">
                <i class="fas fa-filter"></i> Apply
            </button>
            <a href="{{ route('inventory.dashboard', ['refresh' => 1]) }}" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-sync-alt"></i> Reset
            </a>
        </form>
    </div>

    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>Rp {{ number_format(data_get($kpis, 'total_valuation', 0), 0, ',', '.') }}</h3>
                    <p>Total Valuation</p>
                </div>
                <div class="icon"><i class="fas fa-warehouse"></i></div>
                <a href="{{ route('inventory.valuation-report') }}" class="small-box-footer">Valuation Report <i
                        class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ number_format(data_get($kpis, 'low_stock_items', 0)) }}</h3>
                    <p>Low Stock Items</p>
                </div>
                <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
                <a href="{{ route('inventory.low-stock') }}" class="small-box-footer">Low Stock Report <i
                        class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ number_format(data_get($kpis, 'out_of_stock', 0)) }}</h3>
                    <p>Out of Stock</p>
                </div>
                <div class="icon"><i class="fas fa-box-open"></i></div>
                <a href="{{ route('inventory.low-stock') }}" class="small-box-footer">Low Stock Report <i
                        class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-secondary">
                <div class="inner">
                    <h3>{{ number_format(data_get($kpis, 'gr_gi_pending', 0)) }}</h3>
                    <p>GR/GI Pending</p>
                </div>
                <div class="icon"><i class="fas fa-clipboard-list"></i></div>
                <a href="{{ route('gr-gi.index') }}" class="small-box-footer">GR/GI Management <i
                        class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-6">
            <div class="card card-outline card-primary">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title"><i class="fas fa-chart-pie mr-2"></i>Inventory Valuation by Category</h3>
                    <a href="{{ route('inventory.valuation-report') }}" class="btn btn-sm btn-outline-primary">
                        Valuation Report
                    </a>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        @forelse ($valuationByCategory as $cat)
                            <li class="d-flex justify-content-between py-2 border-bottom">
                                <span>{{ $cat['category_name'] }}</span>
                                <strong>Rp {{ number_format($cat['total_value'], 0, ',', '.') }}</strong>
                            </li>
                        @empty
                            <li class="text-muted py-2">No category data</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card card-outline card-success">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title"><i class="fas fa-warehouse mr-2"></i>Stock by Warehouse</h3>
                    <a href="{{ route('warehouses.index') }}" class="btn btn-sm btn-outline-success">
                        Warehouses
                    </a>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        @forelse ($stockByWarehouse as $wh)
                            <li class="d-flex justify-content-between py-2 border-bottom">
                                <span><strong>{{ $wh['warehouse_code'] }}</strong> {{ $wh['warehouse_name'] }}</span>
                                <strong>{{ number_format($wh['available_quantity']) }} unit</strong>
                            </li>
                        @empty
                            <li class="text-muted py-2">No warehouse data</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-6">
            <div class="card card-outline card-warning">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title"><i class="fas fa-exclamation-triangle mr-2"></i>Low Stock Alerts</h3>
                    <a href="{{ route('inventory.low-stock') }}" class="btn btn-sm btn-outline-warning">
                        Low Stock Report
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th class="text-right">Available</th>
                                    <th class="text-right">Reorder</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($lowStockAlerts as $row)
                                    <tr>
                                        <td>
                                            <strong>{{ $row['item_code'] }}</strong><br>
                                            <small class="text-muted">{{ $row['item_name'] }}</small>
                                            @if (!empty($row['warehouse_code']))
                                                <br><small class="text-muted">{{ $row['warehouse_code'] }}</small>
                                            @endif
                                        </td>
                                        <td class="text-right">{{ number_format($row['available_quantity']) }} {{ $row['unit_of_measure'] }}</td>
                                        <td class="text-right">{{ number_format($row['reorder_point']) }}</td>
                                        <td>
                                            @if ($row['status'] === 'critical')
                                                <span class="badge badge-danger">Critical</span>
                                            @elseif ($row['status'] === 'warning')
                                                <span class="badge badge-warning">Warning</span>
                                            @else
                                                <span class="badge badge-success">OK</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-3">No low stock items</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-exchange-alt mr-2"></i>Movement Summary (MTD)</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <p class="mb-1"><span class="text-muted">Purchases In</span></p>
                            <p class="mb-2"><strong class="text-success">+{{ number_format(data_get($movementSummary, 'purchases_in', 0)) }}</strong></p>
                        </div>
                        <div class="col-6">
                            <p class="mb-1"><span class="text-muted">Sales Out</span></p>
                            <p class="mb-2"><strong class="text-danger">-{{ number_format(data_get($movementSummary, 'sales_out', 0)) }}</strong></p>
                        </div>
                        <div class="col-6">
                            <p class="mb-1"><span class="text-muted">Adjustments</span></p>
                            <p class="mb-2"><strong>{{ number_format(data_get($movementSummary, 'adjustments', 0)) }}</strong></p>
                        </div>
                        <div class="col-6">
                            <p class="mb-1"><span class="text-muted">Net Change</span></p>
                            <p class="mb-2">
                                @php $net = data_get($movementSummary, 'net_change', 0); @endphp
                                <strong class="{{ $net >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ $net >= 0 ? '+' : '' }}{{ number_format($net) }}
                                </strong>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card card-outline card-secondary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-history mr-2"></i>Recent Inventory Movements</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Item</th>
                                    <th class="text-right">Qty</th>
                                    <th>Reference</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentMovements as $tx)
                                    <tr>
                                        <td>{{ $tx['transaction_date'] }}</td>
                                        <td>
                                            <span class="badge badge-{{ $tx['transaction_type'] === 'purchase' ? 'success' : ($tx['transaction_type'] === 'sale' ? 'danger' : ($tx['transaction_type'] === 'adjustment' ? 'info' : 'secondary')) }}">
                                                {{ ucfirst($tx['transaction_type']) }}
                                            </span>
                                        </td>
                                        <td>
                                            <strong>{{ $tx['item_code'] }}</strong><br>
                                            <small class="text-muted">{{ $tx['item_name'] }}</small>
                                        </td>
                                        <td class="text-right">
                                            <span class="{{ $tx['quantity'] >= 0 ? 'text-success' : 'text-danger' }}">
                                                {{ $tx['quantity'] >= 0 ? '+' : '' }}{{ number_format($tx['quantity']) }}
                                            </span>
                                        </td>
                                        <td>
                                            @if ($tx['reference_url'])
                                                <a href="{{ $tx['reference_url'] }}" target="_blank">{{ $tx['notes'] ?? $tx['reference_type'] }}</a>
                                            @else
                                                {{ $tx['notes'] ?? $tx['reference_type'] ?? '-' }}
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-3">No recent movements</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
