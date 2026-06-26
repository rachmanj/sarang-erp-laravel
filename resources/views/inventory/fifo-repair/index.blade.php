@extends('layouts.main')

@section('title_page')
    FIFO Layer Repair
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('inventory.index') }}">Inventory</a></li>
    <li class="breadcrumb-item active">FIFO Layer Repair</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">FIFO Layer Repair</h3>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-3">
                        Use this tool when GR/GI approval, stock adjustment, or valuation fails with
                        <strong>Insufficient FIFO inventory layers</strong>. It backfills missing FIFO layers caused by
                        duplicate purchase removals or other historical data issues.
                    </p>

                    <form method="GET" action="{{ route('inventory.fifo-repair.index') }}" class="form-inline mb-4">
                        <label for="q" class="sr-only">Search</label>
                        <input type="text" name="q" id="q" value="{{ $search }}" class="form-control mr-2"
                            placeholder="Search code, name, or ID">
                        <button type="submit" class="btn btn-primary">Search</button>
                        @if ($search !== '')
                            <a href="{{ route('inventory.fifo-repair.index') }}" class="btn btn-secondary ml-2">Clear</a>
                        @endif
                    </form>

                    @if ($issues->isEmpty())
                        <div class="alert alert-success mb-0">
                            No FIFO issues found{{ $search !== '' ? ' for this search' : '' }}.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Code</th>
                                        <th>Name</th>
                                        <th class="text-right">On Hand</th>
                                        <th class="text-right">Repair Qty</th>
                                        <th class="text-right">Stock After Repair</th>
                                        <th>Issue</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($issues as $issue)
                                        <tr>
                                            <td>{{ $issue['code'] }}</td>
                                            <td>{{ $issue['name'] }}</td>
                                            <td class="text-right">{{ number_format($issue['current_stock'], 0) }}</td>
                                            <td class="text-right text-warning">+{{ number_format($issue['total_shortfall'], 0) }}</td>
                                            <td class="text-right">{{ number_format($issue['stock_after_repair'], 0) }}</td>
                                            <td>
                                                <small>{{ $issue['error'] ?? 'Layer/stock mismatch' }}</small>
                                            </td>
                                            <td class="text-nowrap">
                                                <a href="{{ route('inventory.fifo-repair.show', $issue['item_id']) }}"
                                                    class="btn btn-sm btn-warning">Review</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
