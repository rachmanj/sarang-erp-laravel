@extends('layouts.main')

@section('title', 'Warehouse Details')

@section('title_page')
    Warehouse Details
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('warehouses.index') }}">Warehouses</a></li>
    <li class="breadcrumb-item active">{{ $warehouse->name }}</li>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-warehouse mr-1"></i>
                                {{ $warehouse->name }}
                            </h3>
                            <div class="card-tools">
                                @can('warehouse.transfer')
                                    <button type="button" class="btn btn-success btn-sm mr-1" data-toggle="modal"
                                        data-target="#warehouseTransferModal">
                                        <i class="fas fa-exchange-alt"></i> Transfer Stock
                                    </button>
                                @endcan
                                @can('warehouse.update')
                                    <a href="{{ route('warehouses.edit', $warehouse->id) }}" class="btn btn-warning btn-sm">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                @endcan
                                <a href="{{ route('warehouses.index') }}" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-arrow-left"></i> Back to Warehouses
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-bordered">
                                        <tr>
                                            <th width="30%">Warehouse Code</th>
                                            <td><strong>{{ $warehouse->code }}</strong></td>
                                        </tr>
                                        <tr>
                                            <th>Warehouse Name</th>
                                            <td>{{ $warehouse->name }}</td>
                                        </tr>
                                        <tr>
                                            <th>Status</th>
                                            <td>
                                                @if ($warehouse->is_active)
                                                    <span class="badge badge-success">Active</span>
                                                @else
                                                    <span class="badge badge-danger">Inactive</span>
                                                @endif
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-bordered">
                                        <tr>
                                            <th width="30%">Contact Person</th>
                                            <td>{{ $warehouse->contact_person ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Phone</th>
                                            <td>{{ $warehouse->phone ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Email</th>
                                            <td>{{ $warehouse->email ?? '-' }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            @if ($warehouse->address)
                                <div class="row">
                                    <div class="col-md-12">
                                        <table class="table table-bordered">
                                            <tr>
                                                <th width="15%">Address</th>
                                                <td>{{ $warehouse->address }}</td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            @endif

                            <!-- Warehouse Stock Summary -->
                            <div class="row mt-4">
                                <div class="col-md-12">
                                    <h5><i class="fas fa-boxes mr-1"></i> Stock Summary</h5>
                                    <div class="card card-info card-outline">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <div class="info-box">
                                                        <span class="info-box-icon bg-info"><i
                                                                class="fas fa-boxes"></i></span>
                                                        <div class="info-box-content">
                                                            <span class="info-box-text">Total Items</span>
                                                            <span
                                                                class="info-box-number">{{ $warehouse->warehouseStock->count() }}</span>
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
                                                                class="info-box-number">{{ $warehouse->warehouseStock->where('quantity_on_hand', '>', 0)->count() }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="info-box">
                                                        <span class="info-box-icon bg-warning"><i
                                                                class="fas fa-exclamation-triangle"></i></span>
                                                        <div class="info-box-content">
                                                            <span class="info-box-text">Low Stock</span>
                                                            <span
                                                                class="info-box-number">{{ $warehouse->warehouseStock->where('quantity_on_hand', '<=', 'reorder_point')->count() }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="info-box">
                                                        <span class="info-box-icon bg-danger"><i
                                                                class="fas fa-times-circle"></i></span>
                                                        <div class="info-box-content">
                                                            <span class="info-box-text">Out of Stock</span>
                                                            <span
                                                                class="info-box-number">{{ $warehouse->warehouseStock->where('quantity_on_hand', '=', 0)->count() }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Recent Stock Movements -->
                            @if ($warehouse->warehouseStock->count() > 0)
                                <div class="row mt-4">
                                    <div class="col-md-12">
                                        <h5><i class="fas fa-exchange-alt mr-1"></i> Recent Stock Items</h5>
                                        <div class="card card-secondary card-outline">
                                            <div class="card-body">
                                                <div class="table-responsive">
                                                    <table class="table table-bordered table-striped">
                                                        <thead>
                                                            <tr>
                                                                <th>Item Code</th>
                                                                <th>Item Name</th>
                                                                <th>Quantity on Hand</th>
                                                                <th>Reserved</th>
                                                                <th>Available</th>
                                                                <th>Reorder Point</th>
                                                                <th>Status</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($warehouse->warehouseStock->take(10) as $stock)
                                                                <tr>
                                                                    <td><strong>{{ $stock->inventoryItem->code }}</strong>
                                                                    </td>
                                                                    <td>{{ $stock->inventoryItem->name }}</td>
                                                                    <td class="text-right">
                                                                        {{ number_format($stock->quantity_on_hand, 0) }}
                                                                    </td>
                                                                    <td class="text-right">
                                                                        {{ number_format($stock->reserved_quantity, 0) }}
                                                                    </td>
                                                                    <td class="text-right">
                                                                        {{ number_format($stock->available_quantity, 0) }}
                                                                    </td>
                                                                    <td class="text-right">
                                                                        {{ number_format($stock->reorder_point, 0) }}</td>
                                                                    <td>
                                                                        @if ($stock->quantity_on_hand <= 0)
                                                                            <span class="badge badge-danger">Out of
                                                                                Stock</span>
                                                                        @elseif($stock->quantity_on_hand <= $stock->reorder_point)
                                                                            <span class="badge badge-warning">Low
                                                                                Stock</span>
                                                                        @else
                                                                            <span class="badge badge-success">In
                                                                                Stock</span>
                                                                        @endif
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                                @if ($warehouse->warehouseStock->count() > 10)
                                                    <div class="text-center mt-3">
                                                        <a href="{{ route('warehouses.low-stock', $warehouse->id) }}"
                                                            class="btn btn-info btn-sm">
                                                            <i class="fas fa-eye"></i> View All Stock Items
                                                        </a>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @include('components.warehouse-transfer-modal')
@endsection
