@extends('layouts.main')

@section('title', 'Copy to GRPO')

@section('title_page')
    Copy Purchase Order to GRPO
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('purchase-orders.index') }}">Purchase Orders</a></li>
    <li class="breadcrumb-item"><a href="{{ route('purchase-orders.show', $po->id) }}">{{ $po->order_no ?? '#' . $po->id }}</a></li>
    <li class="breadcrumb-item active">Copy to GRPO</li>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-copy mr-1"></i>
                        Copy to Goods Receipt PO
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-3"><strong>PO Number:</strong> {{ $po->order_no ?? '#' . $po->id }}</div>
                        <div class="col-md-3"><strong>Date:</strong> {{ $po->date }}</div>
                        <div class="col-md-3"><strong>Vendor:</strong> {{ $po->businessPartner->name ?? '—' }}</div>
                        <div class="col-md-3"><strong>Status:</strong> {{ strtoupper($po->status) }}</div>
                    </div>

                    <form method="post" action="{{ route('purchase-orders.copy-to-grpo', $po->id) }}">
                        @csrf
                        <p class="text-muted">Select item lines to copy into a draft GRPO. Leave all checked to copy every available line.</p>

                        @if (count($availableLines) === 0)
                            <div class="alert alert-warning">No item lines available to copy.</div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm">
                                    <thead>
                                        <tr>
                                            <th style="width: 40px;">
                                                <input type="checkbox" id="select-all-lines" checked>
                                            </th>
                                            <th>Item Code</th>
                                            <th>Item Name</th>
                                            <th class="text-right">Qty</th>
                                            <th class="text-right">Unit Price</th>
                                            <th class="text-right">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($availableLines as $line)
                                            <tr>
                                                <td>
                                                    <input type="checkbox" name="selected_lines[]" value="{{ $line['id'] }}" class="line-checkbox" checked>
                                                </td>
                                                <td>{{ $line['item_code'] }}</td>
                                                <td>{{ $line['item_name'] }}</td>
                                                <td class="text-right">{{ number_format($line['qty'], 2) }}</td>
                                                <td class="text-right">{{ number_format($line['unit_price'], 2) }}</td>
                                                <td class="text-right">{{ number_format($line['amount'], 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success" @if (count($availableLines) === 0) disabled @endif>
                                <i class="fas fa-copy"></i> Create GRPO
                            </button>
                            <a href="{{ route('purchase-orders.show', $po->id) }}" class="btn btn-secondary ml-2">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        $('#select-all-lines').on('change', function() {
            $('.line-checkbox').prop('checked', $(this).is(':checked'));
        });
    </script>
@endpush
