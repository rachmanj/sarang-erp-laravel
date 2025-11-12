@extends('layouts.main')

@section('title_page')
    Sales Dashboard
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Sales Dashboard</li>
@endsection

@section('content')
    @php
        $kpis = $dashboardData['kpis'] ?? [];
        $arAging = $dashboardData['ar_aging'] ?? [];
        $salesOrders = $dashboardData['sales_orders'] ?? [];
        $salesInvoices = $dashboardData['sales_invoices'] ?? [];
        $deliveryOrders = $dashboardData['delivery_orders'] ?? [];
        $customers = $dashboardData['customers'] ?? [];
        $recentInvoices = $dashboardData['recent_invoices'] ?? collect();
    @endphp

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">
            Sales Dashboard - {{ __('As of :date', ['date' => now()->format('d M Y')]) }}
        </h4>
        <a href="{{ route('sales.dashboard', ['refresh' => 1]) }}" class="btn btn-sm btn-outline-secondary">
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
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>Rp {{ number_format(data_get($kpis, 'outstanding_ar', 0), 0, ',', '.') }}</h3>
                    <p>Outstanding AR</p>
                </div>
                <div class="icon"><i class="fas fa-file-invoice-dollar"></i></div>
                <a href="{{ route('sales-invoices.index') }}" class="small-box-footer">View Invoices <i
                        class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ number_format(data_get($kpis, 'pending_approvals', 0)) }}</h3>
                    <p>Pending Approvals</p>
                </div>
                <div class="icon"><i class="fas fa-clipboard-check"></i></div>
                <a href="{{ route('approvals.dashboard') }}" class="small-box-footer">Approval Queue <i
                        class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ number_format(data_get($kpis, 'open_sales_orders', 0)) }}</h3>
                    <p>Open Sales Orders</p>
                </div>
                <div class="icon"><i class="fas fa-shopping-bag"></i></div>
                <a href="{{ route('sales-orders.index') }}" class="small-box-footer">Sales Orders <i
                        class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-6">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-clock mr-2"></i>AR Invoice Aging (Outstanding Amounts)</h3>
                </div>
                <div class="card-body">
                    @php
                        $buckets = data_get($arAging, 'buckets', []);
                        $totalOutstanding = data_get($buckets, 'total', 0);
                    @endphp
                    <div class="row mb-3">
                        <div class="col-12">
                            <h6 class="text-muted text-uppercase font-weight-bold mb-3">Aging Summary</h6>
                            <ul class="list-unstyled mb-0">
                                <li class="d-flex justify-content-between py-2 border-bottom">
                                    <span><i class="fas fa-circle text-success mr-2"></i>Current (Not Due)</span>
                                    <strong>Rp {{ number_format(data_get($buckets, 'current', 0), 0, ',', '.') }}</strong>
                                </li>
                                <li class="d-flex justify-content-between py-2 border-bottom">
                                    <span><i class="fas fa-circle text-info mr-2"></i>1-30 Days</span>
                                    <strong>Rp {{ number_format(data_get($buckets, '1_30', 0), 0, ',', '.') }}</strong>
                                </li>
                                <li class="d-flex justify-content-between py-2 border-bottom">
                                    <span><i class="fas fa-circle text-warning mr-2"></i>31-60 Days</span>
                                    <strong>Rp {{ number_format(data_get($buckets, '31_60', 0), 0, ',', '.') }}</strong>
                                </li>
                                <li class="d-flex justify-content-between py-2 border-bottom">
                                    <span><i class="fas fa-circle text-orange mr-2"></i>61-90 Days</span>
                                    <strong>Rp {{ number_format(data_get($buckets, '61_90', 0), 0, ',', '.') }}</strong>
                                </li>
                                <li class="d-flex justify-content-between py-2 border-bottom">
                                    <span><i class="fas fa-circle text-danger mr-2"></i>90+ Days</span>
                                    <strong>Rp {{ number_format(data_get($buckets, '90_plus', 0), 0, ',', '.') }}</strong>
                                </li>
                                <li class="d-flex justify-content-between py-2 mt-2">
                                    <span class="font-weight-bold">Total Outstanding</span>
                                    <strong class="text-primary">Rp {{ number_format($totalOutstanding, 0, ',', '.') }}</strong>
                                </li>
                            </ul>
                        </div>
                    </div>
                    @if ($totalOutstanding > 0)
                        <div class="progress mb-3" style="height: 30px;">
                            @php
                                $currentPercent = $totalOutstanding > 0 ? (data_get($buckets, 'current', 0) / $totalOutstanding) * 100 : 0;
                                $days30Percent = $totalOutstanding > 0 ? (data_get($buckets, '1_30', 0) / $totalOutstanding) * 100 : 0;
                                $days60Percent = $totalOutstanding > 0 ? (data_get($buckets, '31_60', 0) / $totalOutstanding) * 100 : 0;
                                $days90Percent = $totalOutstanding > 0 ? (data_get($buckets, '61_90', 0) / $totalOutstanding) * 100 : 0;
                                $days90PlusPercent = $totalOutstanding > 0 ? (data_get($buckets, '90_plus', 0) / $totalOutstanding) * 100 : 0;
                            @endphp
                            <div class="progress-bar bg-success" role="progressbar" style="width: {{ $currentPercent }}%"
                                aria-valuenow="{{ $currentPercent }}" aria-valuemin="0" aria-valuemax="100"
                                title="Current: Rp {{ number_format(data_get($buckets, 'current', 0), 0, ',', '.') }}">
                            </div>
                            <div class="progress-bar bg-info" role="progressbar" style="width: {{ $days30Percent }}%"
                                aria-valuenow="{{ $days30Percent }}" aria-valuemin="0" aria-valuemax="100"
                                title="1-30 Days: Rp {{ number_format(data_get($buckets, '1_30', 0), 0, ',', '.') }}">
                            </div>
                            <div class="progress-bar bg-warning" role="progressbar" style="width: {{ $days60Percent }}%"
                                aria-valuenow="{{ $days60Percent }}" aria-valuemin="0" aria-valuemax="100"
                                title="31-60 Days: Rp {{ number_format(data_get($buckets, '31_60', 0), 0, ',', '.') }}">
                            </div>
                            <div class="progress-bar bg-orange" role="progressbar" style="width: {{ $days90Percent }}%"
                                aria-valuenow="{{ $days90Percent }}" aria-valuemin="0" aria-valuemax="100"
                                title="61-90 Days: Rp {{ number_format(data_get($buckets, '61_90', 0), 0, ',', '.') }}">
                            </div>
                            <div class="progress-bar bg-danger" role="progressbar" style="width: {{ $days90PlusPercent }}%"
                                aria-valuenow="{{ $days90PlusPercent }}" aria-valuemin="0" aria-valuemax="100"
                                title="90+ Days: Rp {{ number_format(data_get($buckets, '90_plus', 0), 0, ',', '.') }}">
                            </div>
                        </div>
                    @endif
                    <a href="{{ route('sales-invoices.index') }}" class="btn btn-sm btn-outline-primary">
                        View All Invoices
                    </a>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card card-outline card-success">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-shopping-bag mr-2"></i>Sales Orders Overview</h3>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-sm-6 border-right">
                            <h6 class="text-muted text-uppercase font-weight-bold">Status Summary</h6>
                            <p class="mb-1">Total: <strong>{{ number_format(data_get($salesOrders, 'total', 0)) }}</strong></p>
                            <p class="mb-1">Draft: <strong>{{ number_format(data_get($salesOrders, 'draft', 0)) }}</strong></p>
                            <p class="mb-1">Approved: <strong>{{ number_format(data_get($salesOrders, 'approved', 0)) }}</strong></p>
                            <p class="mb-1">Closed: <strong>{{ number_format(data_get($salesOrders, 'closed', 0)) }}</strong></p>
                            <p class="mb-0">Open: <strong>{{ number_format(data_get($salesOrders, 'open', 0)) }}</strong></p>
                        </div>
                        <div class="col-sm-6">
                            <h6 class="text-muted text-uppercase font-weight-bold">Value</h6>
                            <p class="mb-0">
                                <span class="text-muted d-block">Open SO Value</span>
                                <strong>Rp {{ number_format(data_get($salesOrders, 'total_value', 0), 0, ',', '.') }}</strong>
                            </p>
                        </div>
                    </div>
                    <a href="{{ route('sales-orders.index') }}" class="btn btn-sm btn-outline-success">
                        View Sales Orders
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-6">
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-file-invoice-dollar mr-2"></i>Sales Invoices Overview</h3>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-sm-6 border-right">
                            <h6 class="text-muted text-uppercase font-weight-bold">Status Summary</h6>
                            <p class="mb-1">Total: <strong>{{ number_format(data_get($salesInvoices, 'total', 0)) }}</strong></p>
                            <p class="mb-1">Draft: <strong>{{ number_format(data_get($salesInvoices, 'draft', 0)) }}</strong></p>
                            <p class="mb-1">Posted: <strong>{{ number_format(data_get($salesInvoices, 'posted', 0)) }}</strong></p>
                            <p class="mb-0">Open: <strong>{{ number_format(data_get($salesInvoices, 'open', 0)) }}</strong></p>
                        </div>
                        <div class="col-sm-6">
                            <h6 class="text-muted text-uppercase font-weight-bold">Amounts</h6>
                            <p class="mb-1">
                                <span class="text-muted d-block">Total Amount</span>
                                <strong>Rp {{ number_format(data_get($salesInvoices, 'total_amount', 0), 0, ',', '.') }}</strong>
                            </p>
                            <p class="mb-0">
                                <span class="text-muted d-block">Outstanding</span>
                                <strong class="text-warning">Rp {{ number_format(data_get($salesInvoices, 'outstanding_amount', 0), 0, ',', '.') }}</strong>
                            </p>
                        </div>
                    </div>
                    <a href="{{ route('sales-invoices.index') }}" class="btn btn-sm btn-outline-info">
                        View Sales Invoices
                    </a>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card card-outline card-warning">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-truck mr-2"></i>Delivery Orders Overview</h3>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-sm-6 border-right">
                            <h6 class="text-muted text-uppercase font-weight-bold">Status Summary</h6>
                            <p class="mb-1">Total: <strong>{{ number_format(data_get($deliveryOrders, 'total', 0)) }}</strong></p>
                            <p class="mb-1">Pending: <strong>{{ number_format(data_get($deliveryOrders, 'pending', 0)) }}</strong></p>
                            <p class="mb-1">Delivered: <strong>{{ number_format(data_get($deliveryOrders, 'delivered', 0)) }}</strong></p>
                            <p class="mb-0">Completed: <strong>{{ number_format(data_get($deliveryOrders, 'completed', 0)) }}</strong></p>
                        </div>
                        <div class="col-sm-6">
                            <h6 class="text-muted text-uppercase font-weight-bold">Actions</h6>
                            <a href="{{ route('delivery-orders.index') }}" class="btn btn-sm btn-outline-warning mb-2">
                                View Delivery Orders
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-6">
            <div class="card card-outline card-secondary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-users mr-2"></i>Top Customers by Outstanding AR</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th class="text-right">Invoices</th>
                                    <th class="text-right">Outstanding</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse (collect(data_get($customers, 'top_customers', [])) as $customer)
                                    <tr>
                                        <td>
                                            <strong>{{ $customer['name'] }}</strong>
                                        </td>
                                        <td class="text-right">{{ number_format($customer['invoice_count']) }}</td>
                                        <td class="text-right">
                                            <strong>Rp {{ number_format($customer['outstanding_amount'], 0, ',', '.') }}</strong>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">{{ __('No customer data') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card card-outline card-dark">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-list mr-2"></i>Recent Sales Invoices</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Invoice No</th>
                                    <th>Date</th>
                                    <th>Customer</th>
                                    <th class="text-right">Amount</th>
                                    <th class="text-right">Outstanding</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentInvoices as $invoice)
                                    <tr>
                                        <td>
                                            <a href="{{ route('sales-invoices.show', $invoice['id']) }}">
                                                {{ $invoice['invoice_no'] }}
                                            </a>
                                        </td>
                                        <td>{{ \Carbon\Carbon::parse($invoice['date'])->format('d-M-Y') }}</td>
                                        <td>{{ $invoice['customer_name'] ?? 'N/A' }}</td>
                                        <td class="text-right">Rp {{ number_format($invoice['total_amount'], 0, ',', '.') }}</td>
                                        <td class="text-right">
                                            <strong class="{{ $invoice['outstanding_amount'] > 0 ? 'text-warning' : 'text-success' }}">
                                                Rp {{ number_format($invoice['outstanding_amount'], 0, ',', '.') }}
                                            </strong>
                                        </td>
                                        <td>
                                            @if ($invoice['closure_status'] === 'open')
                                                <span class="badge badge-warning">Open</span>
                                            @else
                                                <span class="badge badge-success">Closed</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">{{ __('No recent invoices') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <a href="{{ route('sales-invoices.index') }}" class="btn btn-sm btn-outline-dark mt-3">
                        View All Invoices
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .bg-orange {
            background-color: #ff9800 !important;
        }
        .text-orange {
            color: #ff9800 !important;
        }
    </style>
@endpush

@push('scripts')
    @if (session('status'))
        <script>
            toastr.success(@json(session('status')));
        </script>
    @endif
@endpush
