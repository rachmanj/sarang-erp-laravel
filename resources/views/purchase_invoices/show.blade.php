@extends('layouts.main')

@section('title', 'Purchase Invoice #' . $invoice->id)

@section('title_page')
    Purchase Invoice #{{ $invoice->id }}
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('purchase-invoices.index') }}">Purchase Invoices</a></li>
    <li class="breadcrumb-item active">#{{ $invoice->id }}</li>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    @if (session('success'))
                        <script>
                            toastr.success(@json(session('success')));
                        </script>
                    @endif
                    @if (session('pdf_url'))
                        <div class="alert alert-info">PDF ready: <a href="{{ session('pdf_url') }}"
                                target="_blank">Download</a></div>
                    @endif
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h3 class="card-title">Purchase Invoice #{{ $invoice->id }}
                                ({{ strtoupper($invoice->status) }})
                            </h3>
                            <div>
                                <button type="button" class="btn btn-sm btn-info mr-1"
                                    onclick="showRelationshipMap('purchase-invoices', {{ $invoice->id }})">
                                    <i class="fas fa-sitemap"></i> Relationship Map
                                </button>
                                @can('ap.invoices.create')
                                    @if ($invoice->status === 'draft')
                                        <a href="{{ route('purchase-invoices.edit', $invoice->id) }}" class="btn btn-sm btn-primary mr-1">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                    @endif
                                @endcan
                                @can('ap.invoices.post')
                                    @if ($invoice->status !== 'posted')
                                        <form method="post" action="{{ route('purchase-invoices.post', $invoice->id) }}"
                                            class="d-inline">
                                            @csrf
                                            <button class="btn btn-sm btn-success" type="submit">Post</button>
                                        </form>
                                    @elseif ($invoice->canBeUnposted())
                                        <form method="post" action="{{ route('purchase-invoices.unpost', $invoice->id) }}"
                                            class="d-inline unpost-form" data-confirm="Are you sure you want to unpost this invoice? This will reverse all journal entries and inventory transactions.">
                                            @csrf
                                            <button class="btn btn-sm btn-warning" type="submit">
                                                <i class="fas fa-undo"></i> Unpost
                                            </button>
                                        </form>
                                    @endif
                                @endcan
                                <a class="btn btn-sm btn-outline-secondary"
                                    href="{{ route('purchase-invoices.print', $invoice->id) }}" target="_blank">Print</a>
                                <a class="btn btn-sm btn-outline-primary"
                                    href="{{ route('purchase-invoices.pdf', $invoice->id) }}" target="_blank">PDF</a>
                                <form method="post" action="{{ route('purchase-invoices.queuePdf', $invoice->id) }}"
                                    class="d-inline">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-info" type="submit">Queue PDF</button>
                                </form>
                            </div>
                        </div>

                        {{-- Document Navigation Components --}}
                        <div class="card-body border-bottom">
                            @include('components.document-navigation', [
                                'documentType' => 'purchase-invoice',
                                'documentId' => $invoice->id,
                            ])
                        </div>

                        <div class="card-body">
                            <p>Date: {{ $invoice->date }}</p>
                            <p>Vendor:
                                {{ optional(DB::table('business_partners')->find($invoice->business_partner_id))->name }}
                            </p>
                            <p>Description: {{ $invoice->description }}</p>
                            @if (!empty($invoice->purchase_order_id) || !empty($invoice->goods_receipt_id))
                                <p>
                                    <strong>Related:</strong>
                                    @if (!empty($invoice->purchase_order_id))
                                        <a href="{{ route('purchase-orders.show', $invoice->purchase_order_id) }}"
                                            class="badge badge-info">PO #{{ $invoice->purchase_order_id }}</a>
                                    @endif
                                    @if (!empty($invoice->goods_receipt_id))
                                        <a href="{{ route('goods-receipts.show', $invoice->goods_receipt_id) }}"
                                            class="badge badge-info">GRN #{{ $invoice->goods_receipt_id }}</a>
                                    @endif
                                </p>
                            @endif
                            <hr>
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Account</th>
                                        <th>Description</th>
                                        <th>Qty</th>
                                        <th>Unit Price</th>
                                        <th>Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($invoice->lines as $l)
                                        <tr>
                                            <td>{{ optional(DB::table('accounts')->find($l->account_id))->code }}</td>
                                            <td>{{ $l->description }}</td>
                                            <td>{{ number_format($l->qty, 2) }}</td>
                                            <td>{{ number_format($l->unit_price, 2) }}</td>
                                            <td>{{ number_format($l->amount, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <p class="text-right"><strong>Total: {{ number_format($invoice->total_amount, 2) }}</strong>
                            </p>
                            @php
                                $alloc = DB::table('purchase_payment_allocations')
                                    ->where('invoice_id', $invoice->id)
                                    ->sum('amount');
                                $remaining = max(0, (float) $invoice->total_amount - (float) $alloc);
                            @endphp
                            <p>Allocated: {{ number_format($alloc, 2) }} | Remaining: {{ number_format($remaining, 2) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Include Relationship Map Modal --}}
    @include('components.relationship-map-modal')
@endsection
