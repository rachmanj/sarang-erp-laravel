@extends('layouts.main')

@section('title', 'Create Sales Credit Memo')

@section('title_page')
    Create Sales Credit Memo
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('sales-credit-memos.index') }}">Sales Credit Memos</a></li>
    <li class="breadcrumb-item"><a href="{{ route('sales-invoices.show', $invoice->id) }}">{{ $invoice->invoice_no ?? 'SI #'.$invoice->id }}</a></li>
    <li class="breadcrumb-item active">Create</li>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $e)
                                    <li>{{ $e }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <div class="card card-warning card-outline">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h3 class="card-title mb-0">
                                <i class="fas fa-file-invoice mr-1"></i>
                                New Credit Memo (from Sales Invoice)
                            </h3>
                            <a href="{{ route('sales-invoices.show', $invoice->id) }}" class="btn btn-sm btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Invoice
                            </a>
                        </div>
                        <form method="post" action="{{ route('sales-credit-memos.store') }}">
                            @csrf
                            <input type="hidden" name="sales_invoice_id" value="{{ $invoice->id }}">
                            <div class="card-body">
                                <p class="text-muted mb-3">
                                    Lines will match the sales invoice in full. Posting reverses the AR / AR UnInvoice / PPN recognition from that invoice.
                                </p>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Credit memo date <span class="text-danger">*</span></label>
                                            <input type="date" name="date" class="form-control" value="{{ old('date', now()->toDateString()) }}" required>
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="form-group">
                                            <label>Description</label>
                                            <input type="text" name="description" class="form-control" value="{{ old('description') }}" maxlength="2000" placeholder="Optional reason for credit memo">
                                        </div>
                                    </div>
                                </div>
                                <div class="card bg-light mb-0">
                                    <div class="card-body py-2">
                                        <strong>Invoice:</strong> {{ $invoice->invoice_no ?? '#'.$invoice->id }}
                                        &nbsp;|&nbsp;
                                        <strong>Customer:</strong> {{ optional($invoice->businessPartner)->name ?? '—' }}
                                        &nbsp;|&nbsp;
                                        <strong>Entity:</strong> {{ optional($invoice->companyEntity)->name ?? '—' }}
                                        &nbsp;|&nbsp;
                                        <strong>Total:</strong> {{ number_format((float) $invoice->total_amount, 2) }}
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-warning">
                                    <i class="fas fa-save"></i> Create draft credit memo
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
