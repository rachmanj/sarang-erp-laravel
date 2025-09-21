@extends('layouts.main')

@section('title', 'Low Stock Items')

@section('title_page')
    Low Stock Items
    @if ($warehouse)
        - {{ $warehouse->name }}
    @endif
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('warehouses.index') }}">Warehouses</a></li>
    @if ($warehouse)
        <li class="breadcrumb-item"><a href="{{ route('warehouses.show', $warehouse->id) }}">{{ $warehouse->name }}</a></li>
    @endif
    <li class="breadcrumb-item active">Low Stock</li>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card card-warning card-outline">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                Low Stock Items
                                @if ($warehouse)
                                    - {{ $warehouse->name }}
                                @endif
                            </h3>
                            <div class="card-tools">
                                @if ($warehouse)
                                    <a href="{{ route('warehouses.show', $warehouse->id) }}"
                                        class="btn btn-secondary btn-sm">
                                        <i class="fas fa-arrow-left"></i> Back to Warehouse
                                    </a>
                                @else
                                    <a href="{{ route('warehouses.index') }}" class="btn btn-secondary btn-sm">
                                        <i class="fas fa-arrow-left"></i> Back to Warehouses
                                    </a>
                                @endif
                            </div>
                        </div>
                        <div class="card-body">
                            @if ($lowStockItems->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Item Code</th>
                                                <th>Item Name</th>
                                                <th>Category</th>
                                                @if (!$warehouse)
                                                    <th>Warehouse</th>
                                                @endif
                                                <th>Quantity on Hand</th>
                                                <th>Reserved</th>
                                                <th>Available</th>
                                                <th>Reorder Point</th>
                                                <th>Max Stock</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($lowStockItems as $stock)
                                                <tr>
                                                    <td>{{ $loop->iteration }}</td>
                                                    <td><strong>{{ $stock->inventoryItem->code }}</strong></td>
                                                    <td>{{ $stock->inventoryItem->name }}</td>
                                                    <td>{{ $stock->inventoryItem->category->name ?? '-' }}</td>
                                                    @if (!$warehouse)
                                                        <td>{{ $stock->warehouse->name }}</td>
                                                    @endif
                                                    <td class="text-right">{{ number_format($stock->quantity_on_hand, 0) }}
                                                    </td>
                                                    <td class="text-right">
                                                        {{ number_format($stock->reserved_quantity, 0) }}</td>
                                                    <td class="text-right">
                                                        {{ number_format($stock->available_quantity, 0) }}</td>
                                                    <td class="text-right">{{ number_format($stock->reorder_point, 0) }}
                                                    </td>
                                                    <td class="text-right">{{ number_format($stock->max_stock_level, 0) }}
                                                    </td>
                                                    <td>
                                                        @if ($stock->quantity_on_hand <= 0)
                                                            <span class="badge badge-danger">Out of Stock</span>
                                                        @elseif($stock->quantity_on_hand <= $stock->reorder_point)
                                                            <span class="badge badge-warning">Low Stock</span>
                                                        @else
                                                            <span class="badge badge-success">In Stock</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <a href="{{ route('inventory-items.show', $stock->inventoryItem->id) }}"
                                                                class="btn btn-info btn-sm">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            @can('inventory.update')
                                                                <a href="{{ route('inventory-items.edit', $stock->inventoryItem->id) }}"
                                                                    class="btn btn-warning btn-sm">
                                                                    <i class="fas fa-edit"></i>
                                                                </a>
                                                            @endcan
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Summary Cards -->
                                <div class="row mt-4">
                                    <div class="col-md-3">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-warning"><i
                                                    class="fas fa-exclamation-triangle"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Low Stock Items</span>
                                                <span
                                                    class="info-box-number">{{ $lowStockItems->where('quantity_on_hand', '>', 0)->where('quantity_on_hand', '<=', 'reorder_point')->count() }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-danger"><i class="fas fa-times-circle"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Out of Stock</span>
                                                <span
                                                    class="info-box-number">{{ $lowStockItems->where('quantity_on_hand', '=', 0)->count() }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-info"><i class="fas fa-boxes"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Total Items</span>
                                                <span class="info-box-number">{{ $lowStockItems->count() }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-success"><i
                                                    class="fas fa-check-circle"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">In Stock</span>
                                                <span
                                                    class="info-box-number">{{ $lowStockItems->where('quantity_on_hand', '>', 'reorder_point')->count() }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="text-center py-4">
                                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                    <h4 class="text-success">No Low Stock Items</h4>
                                    <p class="text-muted">All items are well stocked!</p>
                                    @if ($warehouse)
                                        <a href="{{ route('warehouses.show', $warehouse->id) }}" class="btn btn-primary">
                                            <i class="fas fa-arrow-left"></i> Back to Warehouse
                                        </a>
                                    @else
                                        <a href="{{ route('warehouses.index') }}" class="btn btn-primary">
                                            <i class="fas fa-arrow-left"></i> Back to Warehouses
                                        </a>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
