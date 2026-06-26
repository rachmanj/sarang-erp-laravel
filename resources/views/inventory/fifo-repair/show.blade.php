@extends('layouts.main')

@section('title_page')
    FIFO Repair — {{ $item->code }}
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('inventory.index') }}">Inventory</a></li>
    <li class="breadcrumb-item"><a href="{{ route('inventory.fifo-repair.index') }}">FIFO Layer Repair</a></li>
    <li class="breadcrumb-item active">{{ $item->code }}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">{{ $item->code }} — {{ $item->name }}</h3>
                    <a href="{{ route('inventory.show', $item->id) }}" class="btn btn-sm btn-secondary">Item Detail</a>
                </div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    @if (session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <table class="table table-borderless">
                        <tr>
                            <th>Status</th>
                            <td>
                                @if ($diagnosis['status'] === 'ok')
                                    <span class="badge badge-success">OK</span>
                                @else
                                    <span class="badge badge-warning">{{ str_replace('_', ' ', $diagnosis['status']) }}</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Current on-hand</th>
                            <td>{{ number_format($diagnosis['current_stock'], 0) }} {{ $item->unit_of_measure }}</td>
                        </tr>
                        <tr>
                            <th>Transaction net</th>
                            <td>{{ number_format($diagnosis['transaction_net'] ?? 0, 0) }}</td>
                        </tr>
                        @if (isset($diagnosis['tolerant_fifo_qty']))
                            <tr>
                                <th>Tolerant FIFO layers</th>
                                <td>{{ number_format($diagnosis['tolerant_fifo_qty'], 0) }}</td>
                            </tr>
                        @endif
                        @if (! empty($diagnosis['error']))
                            <tr>
                                <th>Error</th>
                                <td class="text-danger">{{ $diagnosis['error'] }}</td>
                            </tr>
                        @endif
                        @if (($diagnosis['total_shortfall'] ?? 0) > 0)
                            <tr>
                                <th>Repair quantity</th>
                                <td class="text-warning">+{{ number_format($diagnosis['total_shortfall'], 0) }}</td>
                            </tr>
                            <tr>
                                <th>Stock after repair</th>
                                <td><strong>{{ number_format($diagnosis['stock_after_repair'], 0) }}</strong></td>
                            </tr>
                        @endif
                    </table>

                    @if (! empty($diagnosis['deficits']))
                        <h5 class="mt-4">Planned adjustments</h5>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead>
                                    <tr>
                                        <th>Before Txn</th>
                                        <th>Date</th>
                                        <th class="text-right">Qty</th>
                                        <th class="text-right">Unit Cost</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($diagnosis['deficits'] as $deficit)
                                        <tr>
                                            <td>#{{ $deficit['before_transaction_id'] }}</td>
                                            <td>{{ $deficit['transaction_date'] }}</td>
                                            <td class="text-right">+{{ number_format($deficit['shortfall'], 0) }}</td>
                                            <td class="text-right">{{ number_format($deficit['unit_cost'], 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    @if (session('repair_messages'))
                        <ul class="mt-3 mb-0">
                            @foreach (session('repair_messages') as $message)
                                <li>{{ $message }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
                @if (! in_array($diagnosis['status'], ['ok', 'not_applicable'], true))
                    <div class="card-footer">
                        <form method="POST" action="{{ route('inventory.fifo-repair.repair', $item->id) }}"
                            onsubmit="return confirm('Apply FIFO repair? This will increase stock by {{ number_format($diagnosis['total_shortfall'] ?? 0, 0) }} {{ $item->unit_of_measure }} and create adjustment transactions.');">
                            @csrf
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-wrench"></i> Apply FIFO Repair
                            </button>
                        </form>
                        <p class="text-muted small mt-2 mb-0">
                            After repair, review pending GR/GI documents — you may need to reduce quantities if stock now
                            matches physical count.
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
