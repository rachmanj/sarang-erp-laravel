@extends('layouts.main')

@section('title', 'Copy to Purchase Invoice')

@section('title_page')
    Copy Service PO to Purchase Invoice
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('purchase-orders.index') }}">Purchase Orders</a></li>
    <li class="breadcrumb-item"><a href="{{ route('purchase-orders.show', $po->id) }}">{{ $po->order_no ?? '#' . $po->id }}</a></li>
    <li class="breadcrumb-item active">Copy to Invoice</li>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-file-invoice mr-1"></i>
                        Copy Service Purchase Order to Purchase Invoice
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-3"><strong>PO Number:</strong> {{ $poSummary['order_no'] ?? ($po->order_no ?? '#' . $po->id) }}</div>
                        <div class="col-md-3"><strong>Date:</strong> {{ $poSummary['date'] ?? $po->date }}</div>
                        <div class="col-md-3"><strong>Vendor:</strong> {{ $poSummary['vendor_name'] ?? ($po->businessPartner->name ?? '—') }}</div>
                        <div class="col-md-3"><strong>Lines:</strong> {{ $poSummary['lines_count'] ?? $po->lines->count() }}</div>
                    </div>

                    <div class="alert alert-info">
                        This will create a <strong>draft Purchase Invoice</strong> with all service lines copied from this purchase order.
                    </div>

                    <dl class="row">
                        <dt class="col-sm-3">Total Amount</dt>
                        <dd class="col-sm-9">{{ number_format($poSummary['total_amount'] ?? $po->total_amount, 2) }}</dd>
                        @if (!empty($poSummary['payment_terms']))
                            <dt class="col-sm-3">Payment Terms</dt>
                            <dd class="col-sm-9">{{ $poSummary['payment_terms'] }}</dd>
                        @endif
                    </dl>

                    <div class="d-flex gap-2">
                        <a href="{{ route('purchase-orders.copy-to-purchase-invoice', $po->id) }}" class="btn btn-success">
                            <i class="fas fa-file-invoice"></i> Create Purchase Invoice
                        </a>
                        <a href="{{ route('purchase-orders.show', $po->id) }}" class="btn btn-secondary ml-2">Cancel</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
