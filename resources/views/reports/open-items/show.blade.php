@extends('layouts.app')

@section('title', 'Open Items - ' . ($documentTypeLabels[$documentType] ?? ucwords(str_replace('_', ' ',
    $documentType))))

@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Open Items -
                        {{ $documentTypeLabels[$documentType] ?? ucwords(str_replace('_', ' ', $documentType)) }}</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('reports.trial-balance') }}">Reports</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('reports.open-items.index') }}">Open Items</a></li>
                        <li class="breadcrumb-item active">
                            {{ $documentTypeLabels[$documentType] ?? ucwords(str_replace('_', ' ', $documentType)) }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
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
                    <form method="GET" action="{{ route('reports.open-items.show', $documentType) }}" id="filterForm">
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
                            @if (in_array($documentType, ['purchase_orders', 'goods_receipts', 'purchase_invoices']))
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
                            @else
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
                            @endif
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <div>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-filter"></i> Apply Filters
                                        </button>
                                        <a href="{{ route('reports.open-items.show', $documentType) }}"
                                            class="btn btn-secondary">
                                            <i class="fas fa-times"></i> Clear
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Summary Cards -->
            @if ($typeSummary)
                <div class="row">
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-info">
                            <div class="inner">
                                <h3>{{ $typeSummary['open_count'] }}</h3>
                                <p>Open {{ $documentTypeLabels[$documentType] }}</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-file-alt"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-warning">
                            <div class="inner">
                                <h3>{{ $typeSummary['overdue_count'] }}</h3>
                                <p>Overdue {{ $documentTypeLabels[$documentType] }}</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-success">
                            <div class="inner">
                                <h3>{{ number_format($typeSummary['open_amount'], 0) }}</h3>
                                <p>Open Amount (IDR)</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-money-bill-wave"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-danger">
                            <div class="inner">
                                <h3>{{ number_format($typeSummary['overdue_amount'], 0) }}</h3>
                                <p>Overdue Amount (IDR)</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-clock"></i>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Documents Table -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        {{ $documentTypeLabels[$documentType] ?? ucwords(str_replace('_', ' ', $documentType)) }} Details
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-success btn-sm" onclick="exportToExcel()">
                            <i class="fas fa-file-excel"></i> Export to Excel
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="documentsTable">
                            <thead>
                                <tr>
                                    <th>Document No</th>
                                    <th>Date</th>
                                    @if (in_array($documentType, ['purchase_orders', 'goods_receipts', 'purchase_invoices']))
                                        <th>Supplier</th>
                                    @else
                                        <th>Customer</th>
                                    @endif
                                    <th>Amount</th>
                                    <th>Days Open</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($documents as $document)
                                    <tr class="{{ $document->is_overdue ? 'table-warning' : '' }}">
                                        <td>
                                            @if ($documentType === 'purchase_orders')
                                                {{ $document->po_no }}
                                            @elseif($documentType === 'goods_receipts')
                                                {{ $document->grpo_no }}
                                            @elseif($documentType === 'purchase_invoices')
                                                {{ $document->invoice_no }}
                                            @elseif($documentType === 'sales_orders')
                                                {{ $document->so_no }}
                                            @elseif($documentType === 'delivery_orders')
                                                {{ $document->delivery_no }}
                                            @elseif($documentType === 'sales_invoices')
                                                {{ $document->invoice_no }}
                                            @endif
                                        </td>
                                        <td>{{ $document->date ? \Carbon\Carbon::parse($document->date)->format('d/m/Y') : '-' }}
                                        </td>
                                        <td>
                                            @if (in_array($documentType, ['purchase_orders', 'goods_receipts', 'purchase_invoices']))
                                                {{ $document->supplier->name ?? '-' }}
                                            @else
                                                {{ $document->customer->name ?? '-' }}
                                            @endif
                                        </td>
                                        <td>{{ number_format($document->total_amount ?? 0, 0) }}</td>
                                        <td>
                                            <span
                                                class="badge {{ $document->is_overdue ? 'badge-warning' : 'badge-info' }}">
                                                {{ $document->days_open }} days
                                            </span>
                                        </td>
                                        <td>
                                            @if ($document->is_overdue)
                                                <span class="badge badge-warning">
                                                    <i class="fas fa-exclamation-triangle"></i> Overdue
                                                    ({{ $document->overdue_days }} days)
                                                </span>
                                            @else
                                                <span class="badge badge-success">
                                                    <i class="fas fa-check"></i> Open
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($documentType === 'purchase_orders')
                                                <a href="{{ route('purchase-orders.show', $document->id) }}"
                                                    class="btn btn-sm btn-primary">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                            @elseif($documentType === 'goods_receipts')
                                                <a href="{{ route('goods-receipts.show', $document->id) }}"
                                                    class="btn btn-sm btn-primary">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                            @elseif($documentType === 'purchase_invoices')
                                                <a href="{{ route('purchase-invoices.show', $document->id) }}"
                                                    class="btn btn-sm btn-primary">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                            @elseif($documentType === 'sales_orders')
                                                <a href="{{ route('sales-orders.show', $document->id) }}"
                                                    class="btn btn-sm btn-primary">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                            @elseif($documentType === 'delivery_orders')
                                                <a href="{{ route('delivery-orders.show', $document->id) }}"
                                                    class="btn btn-sm btn-primary">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                            @elseif($documentType === 'sales_invoices')
                                                <a href="{{ route('sales-invoices.show', $document->id) }}"
                                                    class="btn btn-sm btn-primary">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if ($documents->isEmpty())
                        <div class="text-center py-4">
                            <i class="fas fa-info-circle fa-3x text-muted mb-3"></i>
                            <h4 class="text-muted">No open documents found</h4>
                            <p class="text-muted">All {{ strtolower($documentTypeLabels[$documentType] ?? 'documents') }}
                                are closed or no documents match your filters.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#documentsTable').DataTable({
                "responsive": true,
                "lengthChange": false,
                "autoWidth": false,
                "pageLength": 25,
                "order": [
                    [5, "desc"]
                ], // Sort by days open descending
                "columnDefs": [{
                        "orderable": false,
                        "targets": 6
                    } // Actions column not sortable
                ]
            });
        });

        function exportToExcel() {
            // Get current filter values
            const formData = new FormData(document.getElementById('filterForm'));
            const params = new URLSearchParams();

            for (let [key, value] of formData.entries()) {
                if (value) params.append(key, value);
            }

            // Add document type to export
            params.append('document_type', '{{ $documentType }}');

            // Redirect to export URL with filters
            window.location.href = '{{ route('reports.open-items.export') }}?' + params.toString();
        }

        // Auto-refresh every 5 minutes
        setInterval(function() {
            // Refresh data via AJAX
            fetch('{{ route('reports.open-items.data') }}?' + new URLSearchParams(new FormData(document
                    .getElementById('filterForm'))) + '&document_type={{ $documentType }}')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Reload the page to get updated data
                        location.reload();
                    }
                })
                .catch(error => console.log('Auto-refresh failed:', error));
        }, 300000); // 5 minutes
    </script>
@endpush
