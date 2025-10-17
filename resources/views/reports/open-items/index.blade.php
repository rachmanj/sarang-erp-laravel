@extends('layouts.main')

@section('title_page')
    Open Items Report
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">Open Items Report</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <!-- Filters -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Filters</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('reports.open-items.index') }}" id="filterForm">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="date_from">Date From</label>
                                    <input type="date" class="form-control" id="date_from" name="date_from"
                                        value="{{ $filters['date_from'] ?? '' }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="date_to">Date To</label>
                                    <input type="date" class="form-control" id="date_to" name="date_to"
                                        value="{{ $filters['date_to'] ?? '' }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="supplier_id">Supplier</label>
                                    <select class="form-control" id="supplier_id" name="supplier_id">
                                        <option value="">All Suppliers</option>
                                        @foreach ($suppliers as $supplier)
                                            <option value="{{ $supplier->id }}"
                                                {{ ($filters['supplier_id'] ?? '') == $supplier->id ? 'selected' : '' }}>
                                                {{ $supplier->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="customer_id">Customer</label>
                                    <select class="form-control" id="customer_id" name="customer_id">
                                        <option value="">All Customers</option>
                                        @foreach ($customers as $customer)
                                            <option value="{{ $customer->id }}"
                                                {{ ($filters['customer_id'] ?? '') == $customer->id ? 'selected' : '' }}>
                                                {{ $customer->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter"></i> Apply Filters
                                </button>
                                <a href="{{ route('reports.open-items.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Clear Filters
                                </a>
                                <button type="button" class="btn btn-success" onclick="exportToExcel()">
                                    <i class="fas fa-file-excel"></i> Export to Excel
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="row">
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3>{{ $summary['total_open_documents'] }}</h3>
                            <p>Total Open Documents</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-file-alt"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3>{{ $summary['total_overdue_documents'] }}</h3>
                            <p>Overdue Documents</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3>{{ number_format($summary['total_open_amount'], 0) }}</h3>
                            <p>Total Open Amount (IDR)</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-danger">
                        <div class="inner">
                            <h3>{{ number_format($summary['total_overdue_amount'], 0) }}</h3>
                            <p>Overdue Amount (IDR)</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Document Types Summary -->
            <div class="row">
                @foreach ($summary['by_type'] as $type => $data)
                    @if ($data['open_count'] > 0)
                        <div class="col-md-6 col-lg-4">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">{{ $data['label'] }}</h3>
                                    <div class="card-tools">
                                        <a href="{{ route('reports.open-items.show', $type) }}"
                                            class="btn btn-sm btn-primary">
                                            View Details
                                        </a>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="description-block border-right">
                                                <span class="description-percentage text-success">
                                                    <i class="fas fa-file-alt"></i>
                                                </span>
                                                <h5 class="description-header">{{ $data['open_count'] }}</h5>
                                                <span class="description-text">Open</span>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="description-block">
                                                <span class="description-percentage text-warning">
                                                    <i class="fas fa-exclamation-triangle"></i>
                                                </span>
                                                <h5 class="description-header">{{ $data['overdue_count'] }}</h5>
                                                <span class="description-text">Overdue</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mt-2">
                                        <div class="col-12">
                                            <small class="text-muted">
                                                Open Amount: {{ number_format($data['open_amount'], 0) }} IDR<br>
                                                Overdue Amount: {{ number_format($data['overdue_amount'], 0) }} IDR
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>

            <!-- Detailed Table -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Open Items Summary</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Document Type</th>
                                    <th>Open Count</th>
                                    <th>Overdue Count</th>
                                    <th>Open Amount (IDR)</th>
                                    <th>Overdue Amount (IDR)</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($summary['by_type'] as $type => $data)
                                    @if ($data['open_count'] > 0)
                                        <tr>
                                            <td>{{ $data['label'] }}</td>
                                            <td>
                                                <span class="badge badge-info">{{ $data['open_count'] }}</span>
                                            </td>
                                            <td>
                                                @if ($data['overdue_count'] > 0)
                                                    <span class="badge badge-warning">{{ $data['overdue_count'] }}</span>
                                                @else
                                                    <span class="badge badge-success">0</span>
                                                @endif
                                            </td>
                                            <td>{{ number_format($data['open_amount'], 0) }}</td>
                                            <td>
                                                @if ($data['overdue_amount'] > 0)
                                                    <span
                                                        class="text-warning">{{ number_format($data['overdue_amount'], 0) }}</span>
                                                @else
                                                    <span class="text-success">0</span>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('reports.open-items.show', $type) }}"
                                                    class="btn btn-sm btn-primary">
                                                    <i class="fas fa-eye"></i> View Details
                                                </a>
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function exportToExcel() {
            // Get current filter values
            const formData = new FormData(document.getElementById('filterForm'));
            const params = new URLSearchParams();

            for (let [key, value] of formData.entries()) {
                if (value) params.append(key, value);
            }

            // Redirect to export URL with filters
            window.location.href = '{{ route('reports.open-items.export') }}?' + params.toString();
        }

        // Auto-refresh every 5 minutes
        setInterval(function() {
            // Refresh summary data via AJAX
            fetch('{{ route('reports.open-items.summary') }}?' + new URLSearchParams(new FormData(document
                    .getElementById('filterForm'))))
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update summary cards
                        document.querySelector('.small-box.bg-info h3').textContent = data.summary
                            .total_open_documents;
                        document.querySelector('.small-box.bg-warning h3').textContent = data.summary
                            .total_overdue_documents;
                        document.querySelector('.small-box.bg-success h3').textContent = new Intl.NumberFormat()
                            .format(data.summary.total_open_amount);
                        document.querySelector('.small-box.bg-danger h3').textContent = new Intl.NumberFormat()
                            .format(data.summary.total_overdue_amount);
                    }
                })
                .catch(error => console.log('Auto-refresh failed:', error));
        }, 300000); // 5 minutes
    </script>
@endpush
