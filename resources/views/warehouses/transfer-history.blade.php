@extends('layouts.main')

@section('title', 'Warehouse Transfer History')

@section('title_page')
    Warehouse Transfer History
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('warehouses.index') }}">Warehouses</a></li>
    <li class="breadcrumb-item active">Transfer History</li>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card card-info card-outline">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-history mr-1"></i>
                                Warehouse Transfer History
                            </h3>
                            <div class="card-tools">
                                <a href="{{ route('warehouses.index') }}" class="btn btn-tool btn-sm">
                                    <i class="fas fa-arrow-left"></i> Back to Warehouses
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Filters -->
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <form method="GET" action="{{ route('warehouses.transfer-history') }}"
                                        class="form-inline">
                                        <div class="form-group mr-2">
                                            <label for="warehouse_id" class="mr-1">Warehouse:</label>
                                            <select name="warehouse_id" id="warehouse_id"
                                                class="form-control form-control-sm">
                                                <option value="">All Warehouses</option>
                                                @foreach (\App\Models\Warehouse::active()->get() as $warehouse)
                                                    <option value="{{ $warehouse->id }}"
                                                        {{ request('warehouse_id') == $warehouse->id ? 'selected' : '' }}>
                                                        {{ $warehouse->code }} - {{ $warehouse->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group mr-2">
                                            <label for="date_from" class="mr-1">From:</label>
                                            <input type="date" name="date_from" id="date_from"
                                                class="form-control form-control-sm" value="{{ request('date_from') }}">
                                        </div>
                                        <div class="form-group mr-2">
                                            <label for="date_to" class="mr-1">To:</label>
                                            <input type="date" name="date_to" id="date_to"
                                                class="form-control form-control-sm" value="{{ request('date_to') }}">
                                        </div>
                                        <button type="submit" class="btn btn-primary btn-sm">
                                            <i class="fas fa-filter"></i> Filter
                                        </button>
                                        <a href="{{ route('warehouses.transfer-history') }}"
                                            class="btn btn-secondary btn-sm ml-1">
                                            <i class="fas fa-times"></i> Clear
                                        </a>
                                    </form>
                                </div>
                            </div>

                            @if ($transfers->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Item</th>
                                                <th>From Warehouse</th>
                                                <th>To Warehouse</th>
                                                <th>Quantity</th>
                                                <th>Notes</th>
                                                <th>Created By</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($transfers as $transfer)
                                                <tr>
                                                    <td>
                                                        <span
                                                            class="badge badge-info">{{ $transfer->transaction_date }}</span>
                                                        <br>
                                                        <small
                                                            class="text-muted">{{ $transfer->created_at->format('H:i:s') }}</small>
                                                    </td>
                                                    <td>
                                                        <strong>{{ $transfer->item->code }}</strong>
                                                        <br>
                                                        <small>{{ $transfer->item->name }}</small>
                                                    </td>
                                                    <td>
                                                        @if ($transfer->quantity < 0)
                                                            <span
                                                                class="badge badge-danger">{{ $transfer->warehouse->code }}</span>
                                                            <br>
                                                            <small>{{ $transfer->warehouse->name }}</small>
                                                        @else
                                                            <span class="text-muted">-</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if ($transfer->quantity > 0)
                                                            <span
                                                                class="badge badge-success">{{ $transfer->warehouse->code }}</span>
                                                            <br>
                                                            <small>{{ $transfer->warehouse->name }}</small>
                                                        @else
                                                            <span class="text-muted">-</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if ($transfer->quantity > 0)
                                                            <span
                                                                class="badge badge-success">+{{ abs($transfer->quantity) }}</span>
                                                        @else
                                                            <span
                                                                class="badge badge-danger">{{ $transfer->quantity }}</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if ($transfer->notes)
                                                            <small>{{ Str::limit($transfer->notes, 50) }}</small>
                                                        @else
                                                            <span class="text-muted">-</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        {{ $transfer->creator->name ?? 'System' }}
                                                        <br>
                                                        <small
                                                            class="text-muted">{{ $transfer->created_at->diffForHumans() }}</small>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Pagination -->
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <div>
                                        Showing {{ $transfers->firstItem() }} to {{ $transfers->lastItem() }} of
                                        {{ $transfers->total() }} transfers
                                    </div>
                                    <div>
                                        {{ $transfers->appends(request()->query())->links() }}
                                    </div>
                                </div>
                            @else
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle mr-2"></i>
                                    No transfer history found for the selected criteria.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
