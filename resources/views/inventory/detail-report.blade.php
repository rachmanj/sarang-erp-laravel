@extends('layouts.main')

@section('title_page')
    Inventory Detail Report
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('inventory.index') }}">Inventory</a></li>
    <li class="breadcrumb-item active">Detail Report</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">
                        <i class="fas fa-file-alt text-info"></i>
                        Inventory Detail Report
                    </h3>
                    <div>
                        <a href="{{ route('inventory.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to Inventory
                        </a>
                        <a href="{{ route('inventory.export-detail-report', ['date' => $reportDate]) }}"
                            class="btn btn-success btn-sm">
                            <i class="fas fa-file-excel"></i> Export to Excel
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('inventory.detail-report') }}" class="row mb-4">
                        <div class="col-md-4">
                            <label for="date">Report Date</label>
                            <div class="input-group">
                                <input type="date" name="date" id="date" class="form-control"
                                    value="{{ $reportDate }}" required>
                                <div class="input-group-append">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i> Generate Report
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="info-box">
                                <span class="info-box-icon bg-info">
                                    <i class="fas fa-calendar-alt"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Report Date</span>
                                    <span class="info-box-number">{{ \Carbon\Carbon::parse($reportDate)->format('d M Y') }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-box">
                                <span class="info-box-icon bg-success">
                                    <i class="fas fa-boxes"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Total Quantity</span>
                                    <span class="info-box-number">{{ number_format($totalQty, 0) }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-box">
                                <span class="info-box-icon bg-primary">
                                    <i class="fas fa-dollar-sign"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Total Value</span>
                                    <span class="info-box-number">Rp {{ number_format($totalValue, 0, ',', '.') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-sm">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Unit</th>
                                    <th class="text-right">Qty on Hand</th>
                                    <th class="text-right">Unit Cost</th>
                                    <th class="text-right">Total Value</th>
                                    <th>Valuation Method</th>
                                    <th>Valuation Date</th>
                                    <th class="text-right">Reorder Point</th>
                                    <th>Stock Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($reportData as $row)
                                    @php
                                        $item = $row->item;
                                        $qty = $row->quantity_on_hand;
                                        $status = 'OK';
                                        $statusClass = 'success';
                                        if ($qty <= 0) {
                                            $status = 'Out of Stock';
                                            $statusClass = 'danger';
                                        } elseif ($item->reorder_point > 0 && $qty <= $item->reorder_point) {
                                            $status = 'Low Stock';
                                            $statusClass = 'warning';
                                        }
                                    @endphp
                                    <tr>
                                        <td>{{ $item->code }}</td>
                                        <td>
                                            <a href="{{ route('inventory.show', $item->id) }}">{{ $item->name }}</a>
                                        </td>
                                        <td>{{ $item->category->name ?? 'N/A' }}</td>
                                        <td>{{ $item->unit_of_measure }}</td>
                                        <td class="text-right">{{ number_format($qty, 0) }}</td>
                                        <td class="text-right">Rp {{ number_format($row->unit_cost, 2, ',', '.') }}</td>
                                        <td class="text-right"><strong>Rp {{ number_format($row->total_value, 2, ',', '.') }}</strong></td>
                                        <td>
                                            <span class="badge badge-info">{{ strtoupper($row->valuation_method) }}</span>
                                        </td>
                                        <td>
                                            @if ($row->valuation_date)
                                                {{ $row->valuation_date->format('d/m/Y') }}
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="text-right">{{ number_format($item->reorder_point, 0) }}</td>
                                        <td>
                                            <span class="badge badge-{{ $statusClass }}">{{ $status }}</span>
                                        </td>
                                        <td>
                                            <a href="{{ route('inventory.show', $item->id) }}" class="btn btn-xs btn-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="bg-light font-weight-bold">
                                    <td colspan="4" class="text-right">TOTAL</td>
                                    <td class="text-right">{{ number_format($totalQty, 0) }}</td>
                                    <td></td>
                                    <td class="text-right">Rp {{ number_format($totalValue, 2, ',', '.') }}</td>
                                    <td colspan="5"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
