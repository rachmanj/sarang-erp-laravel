@extends('layouts.main')

@section('title_page')
    Convert Quotation to Sales Order
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('sales-quotations.index') }}">Sales Quotations</a></li>
    <li class="breadcrumb-item"><a href="{{ route('sales-quotations.show', $quotation->id) }}">{{ $quotation->quotation_no ?? '#' . $quotation->id }}</a></li>
    <li class="breadcrumb-item active">Convert to Sales Order</li>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-exchange-alt mr-1"></i>
                        Convert Quotation to Sales Order
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-3"><strong>Quotation:</strong> {{ $quotation->quotation_no ?? '#' . $quotation->id }}</div>
                        <div class="col-md-3"><strong>Date:</strong> {{ $quotation->date }}</div>
                        <div class="col-md-3"><strong>Customer:</strong> {{ $quotation->businessPartner->name ?? '—' }}</div>
                        <div class="col-md-3"><strong>Status:</strong> {{ strtoupper($quotation->status) }}</div>
                    </div>

                    <div class="alert alert-info">
                        Confirm to create a new <strong>Sales Order</strong> with all quotation lines copied from this quotation.
                    </div>

                    <form method="post" action="{{ route('sales-quotations.convert-to-sales-order', $quotation->id) }}">
                        @csrf
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label for="date">Sales Order Date</label>
                                <input type="date" name="date" id="date" class="form-control" value="{{ old('date', now()->toDateString()) }}">
                            </div>
                            <div class="form-group col-md-4">
                                <label for="expected_delivery_date">Expected Delivery Date</label>
                                <input type="date" name="expected_delivery_date" id="expected_delivery_date" class="form-control" value="{{ old('expected_delivery_date', $quotation->valid_until) }}">
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-exchange-alt"></i> Convert to Sales Order
                            </button>
                            <a href="{{ route('sales-quotations.show', $quotation->id) }}" class="btn btn-secondary ml-2">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection
