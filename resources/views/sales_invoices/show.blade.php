@extends('layouts.main')

@section('title', 'Sales Invoice ' . ($invoice->invoice_no ?? '#' . $invoice->id))

@section('title_page')
    Sales Invoice {{ $invoice->invoice_no ?? '#' . $invoice->id }}
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('sales-invoices.index') }}">Sales Invoices</a></li>
    <li class="breadcrumb-item active">{{ $invoice->invoice_no ?? '#' . $invoice->id }}</li>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            @if (session('success'))
                <script>
                    toastr.success(@json(session('success')));
                </script>
            @endif
            @if (session('error'))
                <script>
                    toastr.error(@json(session('error')));
                </script>
            @endif
            @if (session('pdf_url'))
                <div class="alert alert-info">PDF ready: <a href="{{ session('pdf_url') }}"
                        target="_blank">Download</a></div>
            @endif

            <div class="card card-primary card-outline">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-file-invoice-dollar mr-1"></i>
                        Invoice {{ $invoice->invoice_no ?? '#' . $invoice->id }}
                        <span class="badge badge-{{ $invoice->status === 'posted' ? 'success' : 'secondary' }} ml-2">
                            {{ strtoupper($invoice->status) }}
                        </span>
                        @if ($invoice->is_opening_balance)
                            <span class="badge badge-warning ml-1">Opening Balance</span>
                        @endif
                    </h3>
                    <div class="d-flex flex-wrap gap-1">
                        <button type="button" class="btn btn-sm btn-info"
                            onclick="showRelationshipMap('sales-invoices', {{ $invoice->id }})">
                            <i class="fas fa-sitemap"></i> Relationship Map
                        </button>
                        @can('ar.invoices.post')
                            @if ($invoice->status !== 'posted')
                                <form method="post" action="{{ route('sales-invoices.post', $invoice->id) }}" class="d-inline">
                                    @csrf
                                    <button class="btn btn-sm btn-success" type="submit">Post</button>
                                </form>
                            @endif
                        @endcan
                        @can('ar.invoices.create')
                            @if ($invoice->status === 'draft')
                                <a href="{{ route('sales-invoices.edit', $invoice->id) }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-edit mr-1"></i> Edit
                                </a>
                                <form method="post" action="{{ route('sales-invoices.destroy', $invoice->id) }}" class="d-inline" onsubmit="return confirm('Delete this draft invoice?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-danger" type="submit"><i class="fas fa-trash mr-1"></i>Delete</button>
                                </form>
                            @endif
                        @endcan
                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('sales-invoices.print', $invoice->id) }}" target="_blank">
                            <i class="fas fa-print"></i> Print
                        </a>
                        <a class="btn btn-sm btn-outline-primary" href="{{ route('sales-invoices.pdf', $invoice->id) }}" target="_blank">PDF</a>
                        <form method="post" action="{{ route('sales-invoices.queuePdf', $invoice->id) }}" class="d-inline">
                            @csrf
                            <button class="btn btn-sm btn-outline-info" type="submit">Queue PDF</button>
                        </form>
                    </div>
                </div>

                <div class="card-body border-bottom">
                    @include('components.document-navigation', [
                        'documentType' => 'sales-invoice',
                        'documentId' => $invoice->id,
                    ])
                </div>

                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-header py-2">
                                    <h5 class="card-title mb-0"><i class="fas fa-building mr-1"></i> Bill To</h5>
                                </div>
                                <div class="card-body py-2">
                                    <strong>{{ $invoice->businessPartner->name ?? '—' }}</strong>
                                    @if ($invoice->businessPartner && $invoice->businessPartner->code)
                                        <br><span class="text-muted small">Code: {{ $invoice->businessPartner->code }}</span>
                                    @endif
                                    @if ($invoice->businessPartner && $invoice->businessPartner->tax_id)
                                        <br><span class="text-muted small">Tax ID: {{ $invoice->businessPartner->tax_id }}</span>
                                    @endif
                                    @if ($invoice->businessPartner && $invoice->businessPartner->primaryAddress && $invoice->businessPartner->primaryAddress->full_address)
                                        <br><span class="text-muted small">{{ $invoice->businessPartner->primaryAddress->full_address }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless mb-0">
                                <tr>
                                    <th class="text-nowrap text-muted" style="width: 120px;">Invoice Date</th>
                                    <td>{{ $invoice->date ? $invoice->date->format('d M Y') : '—' }}</td>
                                </tr>
                                @if ($invoice->due_date)
                                    <tr>
                                        <th class="text-nowrap text-muted">Due Date</th>
                                        <td>{{ $invoice->due_date->format('d M Y') }}</td>
                                    </tr>
                                @endif
                                @if ($invoice->terms_days !== null)
                                    <tr>
                                        <th class="text-nowrap text-muted">Terms</th>
                                        <td>{{ $invoice->terms_days }} days</td>
                                    </tr>
                                @endif
                                @if ($invoice->companyEntity)
                                    <tr>
                                        <th class="text-nowrap text-muted">Company</th>
                                        <td>{{ $invoice->companyEntity->name }} ({{ $invoice->companyEntity->code }})</td>
                                    </tr>
                                @endif
                                @if ($invoice->reference_no)
                                    <tr>
                                        <th class="text-nowrap text-muted">Reference</th>
                                        <td>{{ $invoice->reference_no }}</td>
                                    </tr>
                                @endif
                                @if ($invoice->description)
                                    <tr>
                                        <th class="text-nowrap text-muted align-top">Description</th>
                                        <td>{{ $invoice->description }}</td>
                                    </tr>
                                @endif
                                @if ($invoice->sales_order_id && $invoice->salesOrder)
                                    <tr>
                                        <th class="text-nowrap text-muted">Sales Order</th>
                                        <td>
                                            <a href="{{ route('sales-orders.show', $invoice->sales_order_id) }}" class="badge badge-info">
                                                {{ $invoice->salesOrder->order_no ?? '#' . $invoice->sales_order_id }}
                                            </a>
                                            @if($invoice->salesOrder->reference_no)
                                                <br><small class="text-muted">Ref: {{ $invoice->salesOrder->reference_no }}</small>
                                            @endif
                                        </td>
                                    </tr>
                                @endif
                                @if ($invoice->delivery_order_id && $invoice->deliveryOrder)
                                    <tr>
                                        <th class="text-nowrap text-muted">Delivery Order</th>
                                        <td>
                                            <a href="{{ route('delivery-orders.show', $invoice->delivery_order_id) }}" class="badge badge-info">
                                                {{ $invoice->deliveryOrder->do_number ?? '#' . $invoice->delivery_order_id }}
                                            </a>
                                        </td>
                                    </tr>
                                @endif
                            </table>
                        </div>
                    </div>

                    <h5 class="mb-2"><i class="fas fa-list mr-1"></i> Invoice Lines</h5>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered table-hover mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th style="width: 40px;">#</th>
                                    <th>Item Code</th>
                                    <th>Item Name</th>
                                    <th class="text-right">Qty</th>
                                    <th class="text-right">Unit Price</th>
                                    <th>Tax</th>
                                    <th class="text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($invoice->lines as $idx => $line)
                                    <tr>
                                        <td>{{ $idx + 1 }}</td>
                                        <td>
                                            @if($line->inventoryItem && $line->inventoryItem->code)
                                                <span class="badge badge-secondary">{{ $line->inventoryItem->code }}</span>
                                            @elseif($line->item_code)
                                                <span class="badge badge-secondary">{{ $line->item_code }}</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($line->inventoryItem && $line->inventoryItem->name)
                                                <strong>{{ $line->inventoryItem->name }}</strong>
                                            @elseif($line->item_name)
                                                <strong>{{ $line->item_name }}</strong>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="text-right">{{ number_format($line->qty, 2) }}</td>
                                        <td class="text-right">{{ number_format($line->unit_price, 2) }}</td>
                                        <td>{{ $line->taxCode->code ?? '—' }}</td>
                                        <td class="text-right">{{ number_format($line->amount, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="thead-light">
                                <tr>
                                    <th colspan="6" class="text-right">Total</th>
                                    <th class="text-right">{{ number_format($invoice->total_amount, 2) }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    @php
                        $alloc = \Illuminate\Support\Facades\DB::table('sales_receipt_allocations')
                            ->where('invoice_id', $invoice->id)
                            ->sum('amount');
                        $remaining = max(0, (float) $invoice->total_amount - (float) $alloc);
                    @endphp
                    <div class="row mt-3">
                        <div class="col-md-6">
                            @if ($invoice->status !== 'posted')
                                <div class="small">
                                    <a href="#" class="text-muted" data-toggle="collapse" data-target="#journal-info">
                                        <i class="fas fa-book"></i> What journal will be created when posted?
                                    </a>
                                    <div id="journal-info" class="collapse mt-1">
                                        @if ($invoice->is_opening_balance)
                                            <div class="alert alert-light py-2 small mb-0">
                                                <strong>Opening Balance Invoice:</strong><br>
                                                D: Piutang Dagang (1.1.2.01) — total invoice + VAT<br>
                                                C: Saldo Awal Laba Ditahan (3.3.1) — revenue amount<br>
                                                C: PPN Keluaran (2.1.2) — VAT amount (if applicable)
                                            </div>
                                        @else
                                            <div class="alert alert-light py-2 small mb-0">
                                                <strong>Regular Invoice (from DO):</strong><br>
                                                D: AR UnInvoice (1.1.2.04) — reduce un-invoiced receivable<br>
                                                C: Piutang Dagang (1.1.2.01) — create accounts receivable<br>
                                                C: PPN Keluaran (2.1.2) — VAT liability (if applicable)<br>
                                                <em class="text-muted">Revenue recognition is handled by Delivery Order (Complete Delivery)</em>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>
                        <div class="col-md-6 text-md-right">
                            <p class="mb-0">
                                <strong>Allocated:</strong> {{ number_format($alloc, 2) }}
                                &nbsp;|&nbsp;
                                <strong>Remaining:</strong> {{ number_format($remaining, 2) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @include('components.relationship-map-modal')
@endsection
