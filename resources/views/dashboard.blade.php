@extends('layouts.main')

@section('title_page')
    Dashboard
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item active">Dashboard</li>
@endsection

@section('content')
    @php
        $kpis = $dashboardData['kpis'] ?? [];
        $finance = $dashboardData['finance'] ?? [];
        $salesProcurement = $dashboardData['sales_procurement'] ?? [];
        $inventory = $dashboardData['inventory'] ?? [];
        $assets = $dashboardData['assets'] ?? [];
        $compliance = $dashboardData['compliance'] ?? [];
        $configuration = $dashboardData['configuration'] ?? [];
    @endphp

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">
            {{ __('As of :date', ['date' => now()->format('d M Y')]) }}
        </h4>
        <a href="{{ route('dashboard', ['refresh' => 1]) }}" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-sync-alt"></i> Refresh Data
        </a>
    </div>

    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>Rp {{ number_format(data_get($kpis, 'sales_mtd', 0), 0, ',', '.') }}</h3>
                    <p>Sales (MTD)</p>
                </div>
                <div class="icon"><i class="fas fa-cash-register"></i></div>
                <a href="{{ route('sales-invoices.index') }}" class="small-box-footer">Sales Invoices <i
                        class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>Rp {{ number_format(data_get($kpis, 'purchases_mtd', 0), 0, ',', '.') }}</h3>
                    <p>Purchases (MTD)</p>
                </div>
                <div class="icon"><i class="fas fa-shopping-cart"></i></div>
                <a href="{{ route('purchase-invoices.index') }}" class="small-box-footer">Purchase Invoices <i
                        class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>Rp {{ number_format(data_get($kpis, 'cash_on_hand', 0), 0, ',', '.') }}</h3>
                    <p>Cash on Hand</p>
                </div>
                <div class="icon"><i class="fas fa-wallet"></i></div>
                <a href="{{ route('control-accounts.index') }}" class="small-box-footer">Control Accounts <i
                        class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ number_format(data_get($kpis, 'approvals_pending', 0)) }}</h3>
                    <p>Pending Approvals</p>
                </div>
                <div class="icon"><i class="fas fa-clipboard-check"></i></div>
                <a href="{{ route('approvals.dashboard') }}" class="small-box-footer">Approval Queue <i
                        class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-6">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-balance-scale-left mr-2"></i>Receivables vs Payables Aging</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-muted text-uppercase font-weight-bold">Accounts Receivable</h6>
                            <ul class="list-unstyled mb-0">
                                @foreach (['0_30' => '0-30', '31_60' => '31-60', '61_90' => '61-90', '90_plus' => '90+'] as $bucketKey => $label)
                                    <li class="d-flex justify-content-between py-1">
                                        <span>{{ $label }} hari</span>
                                        <span>Rp {{ number_format(data_get($finance, "ar_aging.$bucketKey", 0), 0, ',', '.') }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted text-uppercase font-weight-bold">Accounts Payable</h6>
                            <ul class="list-unstyled mb-0">
                                @foreach (['0_30' => '0-30', '31_60' => '31-60', '61_90' => '61-90', '90_plus' => '90+'] as $bucketKey => $label)
                                    <li class="d-flex justify-content-between py-1">
                                        <span>{{ $label }} hari</span>
                                        <span>Rp {{ number_format(data_get($finance, "ap_aging.$bucketKey", 0), 0, ',', '.') }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card card-outline card-secondary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-calendar-check mr-2"></i>Period Close Readiness</h3>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <span class="text-muted d-block">Open Periods</span>
                            <strong>
                                {{ data_get($finance, 'period_close.open_periods', collect())->count() }}
                            </strong>
                        </div>
                        <div>
                            <span class="text-muted d-block">Unposted Journals</span>
                            <strong>{{ number_format(data_get($finance, 'period_close.unposted_journals', 0)) }}</strong>
                        </div>
                    </div>
                    <ul class="list-inline mb-0">
                        @forelse (collect(data_get($finance, 'period_close.open_periods', [])) as $period)
                            @php
                                $monthName = \Illuminate\Support\Carbon::create($period['year'], $period['month'])->translatedFormat('M Y');
                            @endphp
                            <li class="list-inline-item badge badge-light px-3 py-2 mb-2">{{ $monthName }}</li>
                        @empty
                            <li class="text-muted">{{ __('All periods closed') }}</li>
                        @endforelse
                    </ul>
                    <a href="{{ route('periods.index') }}" class="btn btn-sm btn-outline-primary mt-3">
                        Manage Period Closing
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-6">
            <div class="card card-outline card-success">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-handshake mr-2"></i>Sales & Procurement Pulse</h3>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-sm-6 border-right">
                            <h6 class="text-muted text-uppercase font-weight-bold">Sales Orders</h6>
                            <p class="mb-1">Draft: <strong>{{ number_format(data_get($salesProcurement, 'sales_orders.draft', 0)) }}</strong></p>
                            <p class="mb-1">Approved: <strong>{{ number_format(data_get($salesProcurement, 'sales_orders.approved', 0)) }}</strong></p>
                            <p class="mb-0">Closed: <strong>{{ number_format(data_get($salesProcurement, 'sales_orders.closed', 0)) }}</strong></p>
                        </div>
                        <div class="col-sm-6">
                            <h6 class="text-muted text-uppercase font-weight-bold">Purchase Orders</h6>
                            <p class="mb-1">Draft: <strong>{{ number_format(data_get($salesProcurement, 'purchase_orders.draft', 0)) }}</strong></p>
                            <p class="mb-1">Approved: <strong>{{ number_format(data_get($salesProcurement, 'purchase_orders.approved', 0)) }}</strong></p>
                            <p class="mb-0">Open Items: <strong>{{ number_format(data_get($salesProcurement, 'open_purchase_orders', 0)) }}</strong></p>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <span class="text-muted d-block">Delivery Backlog</span>
                            <strong>{{ number_format(data_get($salesProcurement, 'delivery_backlog', 0)) }}</strong>
                        </div>
                        <a href="{{ route('delivery-orders.index') }}" class="btn btn-sm btn-outline-success">
                            Delivery Orders
                        </a>
                    </div>
                    <h6 class="text-muted text-uppercase font-weight-bold">Top Suppliers</h6>
                    <ul class="list-unstyled mb-0">
                        @forelse (collect(data_get($salesProcurement, 'top_suppliers', [])) as $supplier)
                            <li class="d-flex justify-content-between py-1">
                                <span>{{ $supplier['name'] }}</span>
                                <span class="badge badge-success">{{ number_format($supplier['overall_rating'], 2) }}</span>
                            </li>
                        @empty
                            <li class="text-muted">{{ __('No supplier performance data') }}</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-warehouse mr-2"></i>Inventory Outlook</h3>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <span class="text-muted d-block">Inventory Valuation</span>
                            <strong>Rp {{ number_format(data_get($inventory, 'total_value', 0), 0, ',', '.') }}</strong>
                        </div>
                        <a href="{{ route('inventory.valuation-report') }}" class="btn btn-sm btn-outline-info">
                            Valuation Report
                        </a>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-muted text-uppercase font-weight-bold">Top Categories</h6>
                            <ul class="list-unstyled mb-3">
                                @forelse (collect(data_get($inventory, 'by_category', [])) as $category)
                                    <li class="d-flex justify-content-between py-1">
                                        <span>Kategori #{{ $category['category_id'] ?? '-' }}</span>
                                        <span>Rp {{ number_format($category['total_value'] ?? 0, 0, ',', '.') }}</span>
                                    </li>
                                @empty
                                    <li class="text-muted">{{ __('No category data') }}</li>
                                @endforelse
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted text-uppercase font-weight-bold">Warehouse Availability</h6>
                            <ul class="list-unstyled mb-3">
                                @forelse (collect(data_get($inventory, 'by_warehouse', [])) as $warehouse)
                                    <li class="d-flex justify-content-between py-1">
                                        <span>Gudang #{{ $warehouse['warehouse_id'] ?? '-' }}</span>
                                        <span>{{ number_format($warehouse['available_quantity'] ?? 0) }} unit</span>
                                    </li>
                                @empty
                                    <li class="text-muted">{{ __('No warehouse data') }}</li>
                                @endforelse
                            </ul>
                        </div>
                    </div>
                    <h6 class="text-muted text-uppercase font-weight-bold">Low Stock Alerts</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th class="text-right">Available</th>
                                    <th class="text-right">Reorder</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse (collect(data_get($inventory, 'low_stock', [])) as $row)
                                    <tr>
                                        <td>
                                            <strong>{{ $row['item_code'] }}</strong><br>
                                            <span class="text-muted">{{ $row['item_name'] }}</span>
                                        </td>
                                        <td class="text-right">{{ number_format($row['available_quantity']) }}</td>
                                        <td class="text-right">{{ number_format($row['reorder_point']) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">{{ __('No low stock items') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3 d-flex justify-content-between">
                        <span class="text-muted">GR/GI Pending</span>
                        <span class="badge badge-info">{{ number_format(data_get($inventory, 'gr_gi_pending', 0)) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @canany(['assets.view', 'assets.disposal.view', 'assets.movement.view'])
        <div class="row">
            <div class="col-12">
                <div class="card card-outline card-warning">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-cubes mr-2"></i>Fixed Asset Snapshot</h3>
                        <div class="card-tools">
                            <a href="{{ route('assets.index') }}" class="btn btn-sm btn-outline-warning">
                                Asset Register
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <span class="text-muted d-block">Total Assets</span>
                                <strong>{{ number_format(data_get($assets, 'counts.total_assets', 0)) }}</strong>
                            </div>
                            <div class="col-md-3">
                                <span class="text-muted d-block">Acquisition Value</span>
                                <strong>Rp {{ number_format(data_get($assets, 'values.acquisition', 0), 0, ',', '.') }}</strong>
                            </div>
                            <div class="col-md-3">
                                <span class="text-muted d-block">Book Value</span>
                                <strong>Rp {{ number_format(data_get($assets, 'values.book', 0), 0, ',', '.') }}</strong>
                            </div>
                            <div class="col-md-3">
                                <span class="text-muted d-block">Draft Depreciation Runs</span>
                                <strong>{{ number_format(data_get($assets, 'depreciation_pending', 0)) }}</strong>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-3">
                                <a href="{{ route('reports.assets.depreciation-schedule') }}" class="btn btn-outline-primary btn-block">
                                    <i class="fas fa-calculator mr-1"></i> Depreciation Schedule
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="{{ route('reports.assets.disposal-summary') }}" class="btn btn-outline-danger btn-block">
                                    <i class="fas fa-trash-alt mr-1"></i> Disposal Summary
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="{{ route('assets.depreciation.index') }}" class="btn btn-outline-success btn-block">
                                    <i class="fas fa-play mr-1"></i> Run Depreciation
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="{{ route('assets.data-quality.index') }}" class="btn btn-outline-secondary btn-block">
                                    <i class="fas fa-check-double mr-1"></i> Data Quality
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endcanany

    <div class="row">
        <div class="col-xl-6">
            <div class="card card-outline card-danger">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-file-invoice-dollar mr-2"></i>Compliance Calendar</h3>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-3">
                        @forelse (collect(data_get($compliance, 'tax_deadlines', [])) as $deadline)
                            <li class="d-flex justify-content-between align-items-center py-1 border-bottom">
                                <div>
                                    <strong>{{ $deadline['event_name'] }}</strong>
                                    <div class="text-muted small">{{ strtoupper($deadline['tax_type']) }} · {{ ucfirst($deadline['event_type']) }}</div>
                                </div>
                                <span class="badge badge-danger">
                                    {{ \Illuminate\Support\Carbon::parse($deadline['event_date'])->format('d M Y') }}
                                </span>
                            </li>
                        @empty
                            <li class="text-muted">{{ __('No upcoming tax events') }}</li>
                        @endforelse
                    </ul>
                    <a href="{{ route('tax.calendar') }}" class="btn btn-sm btn-outline-danger">
                        Open Tax Calendar
                    </a>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card card-outline card-dark">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-tools mr-2"></i>Configuration Alerts</h3>
                </div>
                <div class="card-body">
                    <div class="row text-center mb-3">
                        <div class="col">
                            <span class="text-muted d-block small">Inactive Tax Settings</span>
                            <strong>{{ number_format(data_get($configuration, 'inactive_tax_settings', 0)) }}</strong>
                        </div>
                        <div class="col">
                            <span class="text-muted d-block small">Pending Tax Reports</span>
                            <strong>{{ number_format(data_get($configuration, 'pending_tax_reports', 0)) }}</strong>
                        </div>
                        <div class="col">
                            <span class="text-muted d-block small">Pending Tax Transactions</span>
                            <strong>{{ number_format(data_get($configuration, 'unposted_tax_transactions', 0)) }}</strong>
                        </div>
                    </div>
                    <h6 class="text-muted text-uppercase font-weight-bold">Document Relationships</h6>
                    <ul class="list-unstyled mb-0">
                        @forelse (collect(data_get($configuration, 'open_document_links', [])) as $link)
                            <li class="d-flex justify-content-between py-1">
                                <span>{{ \Illuminate\Support\Str::of($link['document_type'])->headline() }}</span>
                                <span class="badge badge-dark">{{ number_format($link['total']) }}</span>
                            </li>
                        @empty
                            <li class="text-muted">{{ __('No document relationship alerts') }}</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @if (session('status'))
        <script>
            toastr.success(@json(session('status')));
        </script>
    @endif
@endpush
