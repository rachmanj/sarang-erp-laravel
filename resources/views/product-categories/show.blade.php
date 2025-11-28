@extends('layouts.main')

@section('title_page')
    Category Details
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('product-categories.index') }}">Product Categories</a></li>
    <li class="breadcrumb-item active">{{ $category->name }}</li>
@endsection

@section('content')

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <!-- Header Actions -->
            <div class="row mb-3">
                <div class="col-12">
                    <div class="card card-outline">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-tag mr-2"></i>
                                {{ $category->name }}
                            </h3>
                            <div class="card-tools">
                                @can('inventory.update')
                                    <a href="{{ route('product-categories.edit', $category->id) }}"
                                        class="btn btn-warning btn-sm">
                                        <i class="fas fa-edit mr-1"></i>
                                        Edit Category
                                    </a>
                                @endcan
                                @can('admin.view')
                                    <a href="{{ route('audit-logs.show', ['product_category', $category->id]) }}" class="btn btn-info btn-sm">
                                        <i class="fas fa-history mr-1"></i>
                                        Audit Trail
                                    </a>
                                @endcan
                                <a href="{{ route('product-categories.index') }}" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-arrow-left mr-1"></i>
                                    Back to Categories
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Category Information -->
                <div class="col-md-4">
                    <div class="card card-outline">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-info-circle mr-2"></i>
                                Category Information
                            </h3>
                        </div>
                        <div class="card-body">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Code:</strong></td>
                                    <td><span class="badge badge-info">{{ $category->code }}</span></td>
                                </tr>
                                <tr>
                                    <td><strong>Name:</strong></td>
                                    <td>{{ $category->name }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Description:</strong></td>
                                    <td>{{ $category->description ?? 'No description' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Parent Category:</strong></td>
                                    <td>
                                        @if ($category->parent)
                                            <span class="badge badge-secondary">{{ $category->parent->name }}</span>
                                        @else
                                            <span class="text-muted">Root Category</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Status:</strong></td>
                                    <td>
                                        @if ($category->is_active)
                                            <span class="badge badge-success">Active</span>
                                        @else
                                            <span class="badge badge-secondary">Inactive</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Items Count:</strong></td>
                                    <td><span class="badge badge-primary">{{ $category->items()->count() }}</span></td>
                                </tr>
                                <tr>
                                    <td><strong>Created:</strong></td>
                                    <td>{{ $category->created_at->format('d M Y H:i') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Updated:</strong></td>
                                    <td>{{ $category->updated_at->format('d M Y H:i') }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Account Mapping -->
                <div class="col-md-8">
                    <div class="card card-outline">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-chart-line mr-2"></i>
                                Account Mapping
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-info">
                                            <i class="fas fa-boxes"></i>
                                        </span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">
                                                Inventory Account
                                                @if ($inventorySource['is_inherited'] && $inventorySource['source_category'])
                                                    <small class="badge badge-info" title="Inherited from {{ $inventorySource['source_category']->name }}">
                                                        <i class="fas fa-arrow-down"></i> Inherited
                                                    </small>
                                                @endif
                                            </span>
                                            @if ($inventorySource['account'])
                                                <span class="info-box-number">
                                                    <small class="text-muted">{{ $inventorySource['account']->code }}</small><br>
                                                    {{ $inventorySource['account']->name }}
                                                    @if ($inventorySource['is_inherited'] && $inventorySource['source_category'])
                                                        <br><small class="text-muted">
                                                            <i class="fas fa-info-circle"></i> From: {{ $inventorySource['source_category']->name }}
                                                        </small>
                                                    @endif
                                                </span>
                                            @else
                                                <span class="info-box-number text-muted">Not Set</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-warning">
                                            <i class="fas fa-dollar-sign"></i>
                                        </span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">
                                                COGS Account
                                                @if ($cogsSource['is_inherited'] && $cogsSource['source_category'])
                                                    <small class="badge badge-info" title="Inherited from {{ $cogsSource['source_category']->name }}">
                                                        <i class="fas fa-arrow-down"></i> Inherited
                                                    </small>
                                                @endif
                                            </span>
                                            @if ($cogsSource['account'])
                                                <span class="info-box-number">
                                                    <small class="text-muted">{{ $cogsSource['account']->code }}</small><br>
                                                    {{ $cogsSource['account']->name }}
                                                    @if ($cogsSource['is_inherited'] && $cogsSource['source_category'])
                                                        <br><small class="text-muted">
                                                            <i class="fas fa-info-circle"></i> From: {{ $cogsSource['source_category']->name }}
                                                        </small>
                                                    @endif
                                                </span>
                                            @else
                                                <span class="info-box-number text-muted">Not Set</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-success">
                                            <i class="fas fa-chart-line"></i>
                                        </span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">
                                                Sales Account
                                                @if ($salesSource['is_inherited'] && $salesSource['source_category'])
                                                    <small class="badge badge-info" title="Inherited from {{ $salesSource['source_category']->name }}">
                                                        <i class="fas fa-arrow-down"></i> Inherited
                                                    </small>
                                                @endif
                                            </span>
                                            @if ($salesSource['account'])
                                                <span class="info-box-number">
                                                    <small class="text-muted">{{ $salesSource['account']->code }}</small><br>
                                                    {{ $salesSource['account']->name }}
                                                    @if ($salesSource['is_inherited'] && $salesSource['source_category'])
                                                        <br><small class="text-muted">
                                                            <i class="fas fa-info-circle"></i> From: {{ $salesSource['source_category']->name }}
                                                        </small>
                                                    @endif
                                                </span>
                                            @else
                                                <span class="info-box-number text-muted">Not Set</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Child Categories -->
            @if ($childCategories->count() > 0)
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="card card-outline">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-sitemap mr-2"></i>
                                    Subcategories ({{ $childCategories->count() }})
                                </h3>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead class="thead-dark">
                                            <tr>
                                                <th>Code</th>
                                                <th>Name</th>
                                                <th>Description</th>
                                                <th>Status</th>
                                                <th>Items Count</th>
                                                <th width="100">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($childCategories as $childCategory)
                                                <tr>
                                                    <td><span class="badge badge-info">{{ $childCategory->code }}</span>
                                                    </td>
                                                    <td><strong>{{ $childCategory->name }}</strong></td>
                                                    <td>{{ $childCategory->description ?? '-' }}</td>
                                                    <td>
                                                        @if ($childCategory->is_active)
                                                            <span class="badge badge-success">Active</span>
                                                        @else
                                                            <span class="badge badge-secondary">Inactive</span>
                                                        @endif
                                                    </td>
                                                    <td><span
                                                            class="badge badge-primary">{{ $childCategory->items()->count() }}</span>
                                                    </td>
                                                    <td>
                                                        <a href="{{ route('product-categories.show', $childCategory->id) }}"
                                                            class="btn btn-info btn-sm" title="View Details">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
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
            @endif

            <!-- Items in Category -->
            @if ($items->count() > 0)
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="card card-outline">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-boxes mr-2"></i>
                                    Items in Category ({{ $items->total() }})
                                </h3>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead class="thead-dark">
                                            <tr>
                                                <th>Code</th>
                                                <th>Name</th>
                                                <th>Type</th>
                                                <th>Default Warehouse</th>
                                                <th>Purchase Price</th>
                                                <th>Selling Price</th>
                                                <th>Status</th>
                                                <th width="100">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($items as $item)
                                                <tr>
                                                    <td><span class="badge badge-info">{{ $item->code }}</span></td>
                                                    <td><strong>{{ $item->name }}</strong></td>
                                                    <td>
                                                        @if ($item->item_type == 'item')
                                                            <span class="badge badge-primary">Physical Item</span>
                                                        @else
                                                            <span class="badge badge-success">Service</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if ($item->defaultWarehouse)
                                                            {{ $item->defaultWarehouse->name }}
                                                        @else
                                                            <span class="text-muted">Not Set</span>
                                                        @endif
                                                    </td>
                                                    <td>Rp {{ number_format($item->purchase_price, 0, ',', '.') }}</td>
                                                    <td>Rp {{ number_format($item->selling_price, 0, ',', '.') }}</td>
                                                    <td>
                                                        @if ($item->is_active)
                                                            <span class="badge badge-success">Active</span>
                                                        @else
                                                            <span class="badge badge-secondary">Inactive</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <a href="{{ route('inventory.show', $item->id) }}"
                                                            class="btn btn-info btn-sm" title="View Item">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Pagination -->
                                <div class="d-flex justify-content-center">
                                    {{ $items->links() }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Audit Trail -->
            @if ($auditTrail->count() > 0)
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="card card-outline">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-history mr-2"></i>
                                    Audit Trail
                                </h3>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead class="thead-dark">
                                            <tr>
                                                <th>Date</th>
                                                <th>Action</th>
                                                <th>User</th>
                                                <th>Description</th>
                                                <th>IP Address</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($auditTrail as $log)
                                                <tr>
                                                    <td>{{ $log->created_at->format('d M Y H:i:s') }}</td>
                                                    <td>
                                                        @switch($log->action)
                                                            @case('created')
                                                                <span class="badge badge-success">Created</span>
                                                            @break

                                                            @case('updated')
                                                                <span class="badge badge-warning">Updated</span>
                                                            @break

                                                            @case('deleted')
                                                                <span class="badge badge-danger">Deleted</span>
                                                            @break

                                                            @default
                                                                <span class="badge badge-info">{{ ucfirst($log->action) }}</span>
                                                        @endswitch
                                                    </td>
                                                    <td>{{ $log->user ? $log->user->name : 'System' }}</td>
                                                    <td>{{ $log->description }}</td>
                                                    <td>{{ $log->ip_address }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </section>
    </div>
@endsection
