@extends('layouts.app')

@section('title', 'Vendor Details')

@section('content')
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">Vendor Details: {{ $vendor->name }}</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('vendors.index') }}">Vendors</a></li>
                            <li class="breadcrumb-item active">{{ $vendor->name }}</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <section class="content">
            <div class="container-fluid">
                <!-- Vendor Information -->
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-building"></i> Vendor Information
                                </h3>
                                <div class="card-tools">
                                    <a href="{{ route('vendors.edit', $vendor->id) }}" class="btn btn-primary btn-sm">
                                        <i class="fas fa-edit"></i> Edit Vendor
                                    </a>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <strong>Code:</strong> {{ $vendor->code }}
                                    </div>
                                    <div class="col-md-3">
                                        <strong>Name:</strong> {{ $vendor->name }}
                                    </div>
                                    <div class="col-md-3">
                                        <strong>Email:</strong> {{ $vendor->email ?: '-' }}
                                    </div>
                                    <div class="col-md-3">
                                        <strong>Phone:</strong> {{ $vendor->phone ?: '-' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistics -->
                <div class="row mb-3">
                    <div class="col-lg-3 col-6">
                        <div class="info-box">
                            <span class="info-box-icon bg-info">
                                <i class="fas fa-cube"></i>
                            </span>
                            <div class="info-box-content">
                                <span class="info-box-text">Total Assets</span>
                                <span class="info-box-number">{{ $totalAssetCount }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-6">
                        <div class="info-box">
                            <span class="info-box-icon bg-success">
                                <i class="fas fa-dollar-sign"></i>
                            </span>
                            <div class="info-box-content">
                                <span class="info-box-text">Total Asset Value</span>
                                <span class="info-box-number">Rp {{ number_format($totalAssetValue, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-6">
                        <div class="info-box">
                            <span class="info-box-icon bg-warning">
                                <i class="fas fa-file-invoice"></i>
                            </span>
                            <div class="info-box-content">
                                <span class="info-box-text">Purchase Orders</span>
                                <span class="info-box-number">{{ $totalPurchaseCount }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-6">
                        <div class="info-box">
                            <span class="info-box-icon bg-primary">
                                <i class="fas fa-money-bill-wave"></i>
                            </span>
                            <div class="info-box-content">
                                <span class="info-box-text">Total Purchase Value</span>
                                <span class="info-box-number">Rp
                                    {{ number_format($totalPurchaseValue, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header p-0">
                                <ul class="nav nav-tabs" id="vendorTabs" role="tablist">
                                    <li class="nav-item">
                                        <a class="nav-link active" id="assets-tab" data-toggle="tab" href="#assets"
                                            role="tab">
                                            <i class="fas fa-cube"></i> Assets ({{ $totalAssetCount }})
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" id="purchase-orders-tab" data-toggle="tab"
                                            href="#purchase-orders" role="tab">
                                            <i class="fas fa-file-invoice"></i> Purchase Orders ({{ $totalPurchaseCount }})
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" id="acquisition-history-tab" data-toggle="tab"
                                            href="#acquisition-history" role="tab">
                                            <i class="fas fa-history"></i> Acquisition History
                                        </a>
                                    </li>
                                </ul>
                            </div>
                            <div class="card-body">
                                <div class="tab-content" id="vendorTabsContent">
                                    <!-- Assets Tab -->
                                    <div class="tab-pane fade show active" id="assets" role="tabpanel">
                                        <div class="table-responsive">
                                            <table id="assetsTable" class="table table-bordered table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Code</th>
                                                        <th>Name</th>
                                                        <th>Category</th>
                                                        <th>Acquisition Cost</th>
                                                        <th>Book Value</th>
                                                        <th>Fund</th>
                                                        <th>Project</th>
                                                        <th>Department</th>
                                                        <th>Placed in Service</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                            </table>
                                        </div>
                                    </div>

                                    <!-- Purchase Orders Tab -->
                                    <div class="tab-pane fade" id="purchase-orders" role="tabpanel">
                                        <div class="table-responsive">
                                            <table id="purchaseOrdersTable" class="table table-bordered table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Order No</th>
                                                        <th>Date</th>
                                                        <th>Total Amount</th>
                                                        <th>Status</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                            </table>
                                        </div>
                                    </div>

                                    <!-- Acquisition History Tab -->
                                    <div class="tab-pane fade" id="acquisition-history" role="tabpanel">
                                        <div class="table-responsive">
                                            <table id="acquisitionHistoryTable"
                                                class="table table-bordered table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Code</th>
                                                        <th>Name</th>
                                                        <th>Category</th>
                                                        <th>Acquisition Cost</th>
                                                        <th>Fund</th>
                                                        <th>Project</th>
                                                        <th>Department</th>
                                                        <th>Placed in Service</th>
                                                        <th>Age (Months)</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // Initialize DataTables
            const assetsTable = $('#assetsTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('vendors.assets', $vendor->id) }}',
                    type: 'GET'
                },
                columns: [{
                        data: 'code',
                        name: 'code'
                    },
                    {
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'category_name',
                        name: 'category_name'
                    },
                    {
                        data: 'acquisition_cost',
                        name: 'acquisition_cost'
                    },
                    {
                        data: 'current_book_value',
                        name: 'current_book_value'
                    },
                    {
                        data: 'fund_name',
                        name: 'fund_name'
                    },
                    {
                        data: 'project_name',
                        name: 'project_name'
                    },
                    {
                        data: 'department_name',
                        name: 'department_name'
                    },
                    {
                        data: 'placed_in_service_date',
                        name: 'placed_in_service_date'
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false
                    }
                ],
                order: [
                    [8, 'desc']
                ],
                pageLength: 25
            });

            const purchaseOrdersTable = $('#purchaseOrdersTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('vendors.purchase-orders', $vendor->id) }}',
                    type: 'GET'
                },
                columns: [{
                        data: 'order_no',
                        name: 'order_no'
                    },
                    {
                        data: 'date',
                        name: 'date'
                    },
                    {
                        data: 'total_amount',
                        name: 'total_amount'
                    },
                    {
                        data: 'status',
                        name: 'status'
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false
                    }
                ],
                order: [
                    [1, 'desc']
                ],
                pageLength: 25
            });

            const acquisitionHistoryTable = $('#acquisitionHistoryTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('vendors.asset-acquisition-history', $vendor->id) }}',
                    type: 'GET'
                },
                columns: [{
                        data: 'code',
                        name: 'code'
                    },
                    {
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'category_name',
                        name: 'category_name'
                    },
                    {
                        data: 'acquisition_cost',
                        name: 'acquisition_cost'
                    },
                    {
                        data: 'fund_name',
                        name: 'fund_name'
                    },
                    {
                        data: 'project_name',
                        name: 'project_name'
                    },
                    {
                        data: 'department_name',
                        name: 'department_name'
                    },
                    {
                        data: 'placed_in_service_date',
                        name: 'placed_in_service_date'
                    },
                    {
                        data: 'age_months',
                        name: 'age_months'
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false
                    }
                ],
                order: [
                    [7, 'desc']
                ],
                pageLength: 25
            });

            // Refresh tables when switching tabs
            $('#vendorTabs a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
                const target = $(e.target).attr('href');

                if (target === '#assets') {
                    assetsTable.columns.adjust().draw();
                } else if (target === '#purchase-orders') {
                    purchaseOrdersTable.columns.adjust().draw();
                } else if (target === '#acquisition-history') {
                    acquisitionHistoryTable.columns.adjust().draw();
                }
            });
        });
    </script>
@endpush
