@extends('layouts.main')

@section('title', 'Purchase Invoice ' . ($invoice->invoice_no ?? '#' . $invoice->id))

@section('title_page')
    Purchase Invoice {{ $invoice->invoice_no ?? '#' . $invoice->id }}
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('purchase-invoices.index') }}">Purchase Invoices</a></li>
    <li class="breadcrumb-item active">{{ $invoice->invoice_no ?? '#' . $invoice->id }}</li>
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
                                Purchase Invoice {{ $invoice->invoice_no ?? '#' . $invoice->id }}
                                <span
                                    class="badge badge-{{ $invoice->status === 'posted' ? 'success' : 'secondary' }} ml-2">
                                    {{ strtoupper($invoice->status) }}
                                </span>
                                @if ($invoice->is_opening_balance)
                                    <span class="badge badge-warning ml-1">
                                        <i class="fas fa-info-circle"></i> Opening Balance
                                    </span>
                                @endif
                                @if ($invoice->is_direct_purchase)
                                    <span class="badge badge-info ml-1">
                                        <i class="fas fa-bolt"></i> Direct Purchase
                                    </span>
                                @endif
                            </h3>
                            <div class="d-flex flex-wrap gap-1">
                                <button type="button" class="btn btn-sm btn-info"
                                    onclick="showRelationshipMap('purchase-invoices', {{ $invoice->id }})">
                                    <i class="fas fa-sitemap"></i> Relationship Map
                                </button>
                                @can('ap.invoices.create')
                                    @if ($invoice->status === 'draft')
                                        <a href="{{ route('purchase-invoices.edit', $invoice->id) }}"
                                            class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit mr-1"></i> Edit
                                        </a>
                                    @endif
                                @endcan
                                @can('ap.invoices.post')
                                    @if ($invoice->status !== 'posted')
                                        <form method="post" action="{{ route('purchase-invoices.post', $invoice->id) }}"
                                            class="d-inline">
                                            @csrf
                                            <button class="btn btn-sm btn-success" type="submit">
                                                <i class="fas fa-check mr-1"></i> Post
                                            </button>
                                        </form>
                                    @elseif ($invoice->canBeUnposted())
                                        <form method="post" action="{{ route('purchase-invoices.unpost', $invoice->id) }}"
                                            class="d-inline unpost-form"
                                            data-confirm="Are you sure you want to unpost this invoice? This will reverse all journal entries and inventory transactions.">
                                            @csrf
                                            <button class="btn btn-sm btn-warning" type="submit">
                                                <i class="fas fa-undo mr-1"></i> Unpost
                                            </button>
                                        </form>
                                    @endif
                                @endcan
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle"
                                        data-toggle="dropdown">
                                        <i class="fas fa-print"></i> Print
                                    </button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item" href="{{ route('purchase-invoices.print', $invoice->id) }}"
                                            target="_blank">
                                            <i class="fas fa-file-alt mr-1"></i> Standard Print
                                        </a>
                                    </div>
                                </div>
                                <a class="btn btn-sm btn-outline-primary"
                                    href="{{ route('purchase-invoices.pdf', $invoice->id) }}" target="_blank">
                                    <i class="fas fa-file-pdf mr-1"></i> PDF
                                </a>
                                <form method="post" action="{{ route('purchase-invoices.queuePdf', $invoice->id) }}"
                                    class="d-inline">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-info" type="submit">
                                        <i class="fas fa-clock mr-1"></i> Queue PDF
                                    </button>
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
                            {{-- Invoice Information Section --}}
                            <div class="row mb-4">
                                {{-- Vendor Information --}}
                                <div class="col-md-6">
                                    <div class="card bg-light">
                                        <div class="card-header py-2">
                                            <h5 class="card-title mb-0">
                                                <i class="fas fa-truck mr-1"></i> Vendor Information
                                            </h5>
                                        </div>
                                        <div class="card-body py-2">
                                            <strong>{{ $invoice->businessPartner->name ?? 'N/A' }}</strong>
                                            @if ($invoice->businessPartner && $invoice->businessPartner->code)
                                                <br><span class="text-muted small">Code: {{ $invoice->businessPartner->code }}</span>
                                            @endif
                                            @if ($invoice->businessPartner && $invoice->businessPartner->tax_id)
                                                <br><span class="text-muted small">Tax ID: {{ $invoice->businessPartner->tax_id }}</span>
                                            @endif
                                            @if ($invoice->businessPartner && $invoice->businessPartner->primaryAddress)
                                                <br><span class="text-muted small">{{ $invoice->businessPartner->primaryAddress->full_address ?? '' }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                {{-- Invoice Details --}}
                                <div class="col-md-6">
                                    <table class="table table-sm table-borderless mb-0">
                                        <tr>
                                            <th class="text-nowrap text-muted" style="width: 140px;">
                                                <i class="fas fa-hashtag mr-1"></i> Invoice Number
                                            </th>
                                            <td><strong>{{ $invoice->invoice_no ?? 'N/A' }}</strong></td>
                                        </tr>
                                        <tr>
                                            <th class="text-nowrap text-muted">
                                                <i class="fas fa-calendar mr-1"></i> Invoice Date
                                            </th>
                                            <td>{{ $invoice->date ? $invoice->date->format('d M Y') : '—' }}</td>
                                        </tr>
                                        @if ($invoice->due_date)
                                            <tr>
                                                <th class="text-nowrap text-muted">
                                                    <i class="fas fa-calendar-check mr-1"></i> Due Date
                                                </th>
                                                <td>
                                                    {{ $invoice->due_date->format('d M Y') }}
                                                    @if ($invoice->due_date < now() && $invoice->status === 'posted')
                                                        <span class="badge badge-danger ml-2">Overdue</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endif
                                        @if ($invoice->terms_days !== null)
                                            <tr>
                                                <th class="text-nowrap text-muted">
                                                    <i class="fas fa-clock mr-1"></i> Payment Terms
                                                </th>
                                                <td>{{ $invoice->terms_days }} days</td>
                                            </tr>
                                        @endif
                                        @if ($invoice->companyEntity)
                                            <tr>
                                                <th class="text-nowrap text-muted">
                                                    <i class="fas fa-building mr-1"></i> Company Entity
                                                </th>
                                                <td>{{ $invoice->companyEntity->name }} ({{ $invoice->companyEntity->code }})</td>
                                            </tr>
                                        @endif
                                        @if ($invoice->payment_method)
                                            <tr>
                                                <th class="text-nowrap text-muted">
                                                    <i class="fas fa-money-bill-wave mr-1"></i> Payment Method
                                                </th>
                                                <td>
                                                    <span class="badge badge-{{ $invoice->payment_method === 'cash' ? 'success' : 'info' }}">
                                                        {{ strtoupper($invoice->payment_method) }}
                                                    </span>
                                                    @if ($invoice->payment_method === 'cash' && $cashAccount)
                                                        <br><small class="text-muted">{{ $cashAccount->code }} - {{ $cashAccount->name }}</small>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endif
                                        @if ($invoice->description)
                                            <tr>
                                                <th class="text-nowrap text-muted">
                                                    <i class="fas fa-align-left mr-1"></i> Description
                                                </th>
                                                <td>{{ $invoice->description }}</td>
                                            </tr>
                                        @endif
                                        @if ($invoice->posted_at)
                                            <tr>
                                                <th class="text-nowrap text-muted">
                                                    <i class="fas fa-check-circle mr-1"></i> Posted At
                                                </th>
                                                <td>{{ $invoice->posted_at->format('d M Y H:i') }}</td>
                                            </tr>
                                        @endif
                                    </table>
                                </div>
                            </div>

                            {{-- Related Documents Section --}}
                            @if ($purchaseOrder || $goodsReceipt)
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <div class="card bg-light">
                                            <div class="card-header py-2">
                                                <h5 class="card-title mb-0">
                                                    <i class="fas fa-link mr-1"></i> Related Documents
                                                </h5>
                                            </div>
                                            <div class="card-body py-2">
                                                @if ($purchaseOrder)
                                                    <a href="{{ route('purchase-orders.show', $invoice->purchase_order_id) }}"
                                                        class="badge badge-info mr-2">
                                                        <i class="fas fa-shopping-cart mr-1"></i> Purchase Order
                                                        #{{ $purchaseOrder->order_no ?? $invoice->purchase_order_id }}
                                                    </a>
                                                @endif
                                                @if ($goodsReceipt)
                                                    <a href="{{ route('goods-receipts.show', $invoice->goods_receipt_id) }}"
                                                        class="badge badge-success mr-2">
                                                        <i class="fas fa-box mr-1"></i> Goods Receipt
                                                        #{{ $goodsReceipt->receipt_no ?? $invoice->goods_receipt_id }}
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            {{-- Financial Summary --}}
                            <div class="row mb-4">
                                <div class="col-md-12">
                                    <div class="card bg-light">
                                        <div class="card-header py-2">
                                            <h5 class="card-title mb-0">
                                                <i class="fas fa-calculator mr-1"></i> Financial Summary
                                            </h5>
                                        </div>
                                        <div class="card-body py-2">
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <strong>Subtotal:</strong><br>
                                                    <span class="text-muted">Rp {{ number_format($totalAmountAfterVat, 2) }}</span>
                                                </div>
                                                @if (($invoice->discount_amount ?? 0) > 0)
                                                <div class="col-md-3">
                                                    <strong>Discount
                                                        @if (($invoice->discount_percentage ?? 0) > 0)
                                                            ({{ number_format($invoice->discount_percentage, 2) }}%)
                                                        @endif
                                                    </strong><br>
                                                    <span class="text-danger">- Rp {{ number_format($invoice->discount_amount, 2) }}</span>
                                                </div>
                                                @endif
                                                <div class="col-md-3">
                                                    <strong>Total VAT:</strong><br>
                                                    <span class="text-muted">Rp {{ number_format($totalVat, 2) }}</span>
                                                </div>
                                                <div class="col-md-3">
                                                    <strong>Total Amount:</strong><br>
                                                    <span class="text-primary font-weight-bold">Rp {{ number_format($invoice->total_amount, 2) }}</span>
                                                </div>
                                                <div class="col-md-3">
                                                    <strong>Payment Status:</strong><br>
                                                    @if ($totalAllocated > 0)
                                                        <span class="badge badge-success">Rp {{ number_format($totalAllocated, 2) }} Allocated</span><br>
                                                        <small class="text-muted">Remaining: Rp {{ number_format($remainingBalance, 2) }}</small>
                                                    @else
                                                        <span class="badge badge-warning">Unpaid</span><br>
                                                        <small class="text-muted">Balance: Rp {{ number_format($invoice->total_amount, 2) }}</small>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Line Items Table --}}
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h5 class="mb-3">
                                        <i class="fas fa-list mr-1"></i> Line Items
                                    </h5>
                                    <div class="table-responsive">
                                        <table class="table table-striped table-bordered table-hover">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th style="width: 5%;">#</th>
                                                    <th style="width: 10%;">Account</th>
                                                    <th style="width: 10%;">Item Code</th>
                                                    <th style="width: 20%;">Item Name</th>
                                                    <th style="width: 15%;">Description</th>
                                                    <th style="width: 8%;" class="text-right">Qty</th>
                                                    <th style="width: 10%;" class="text-right">Unit Price</th>
                                                    <th style="width: 10%;" class="text-right">Amount</th>
                                                    @if ($invoice->lines->sum('discount_amount') > 0)
                                                    <th style="width: 8%;" class="text-right">Discount</th>
                                                    <th style="width: 8%;" class="text-right">Net Amount</th>
                                                    @endif
                                                    <th style="width: 7%;" class="text-right">VAT</th>
                                                    <th style="width: 10%;" class="text-right">Amount After VAT</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($invoice->lines as $idx => $l)
                                                    <tr>
                                                        <td>{{ $idx + 1 }}</td>
                                                        <td>
                                                            <span class="badge badge-secondary">
                                                                {{ optional(DB::table('accounts')->find($l->account_id))->code ?? 'N/A' }}
                                                            </span>
                                                        </td>
                                                        <td>
                                                            @if ($l->inventoryItem)
                                                                <span class="badge badge-info">{{ $l->inventoryItem->item_code }}</span>
                                                            @else
                                                                <span class="text-muted">—</span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if ($l->inventoryItem)
                                                                <strong>{{ $l->inventoryItem->name }}</strong>
                                                                @if ($l->warehouse)
                                                                    <br><small class="text-muted">
                                                                        <i class="fas fa-warehouse"></i> {{ $l->warehouse->name }}
                                                                    </small>
                                                                @endif
                                                            @else
                                                                <span class="text-muted">—</span>
                                                            @endif
                                                        </td>
                                                        <td>{{ $l->description ?? '—' }}</td>
                                                        <td class="text-right">{{ number_format($l->qty, 2) }}</td>
                                                        <td class="text-right">Rp {{ number_format($l->unit_price, 2) }}</td>
                                                        <td class="text-right">Rp {{ number_format($l->amount, 2) }}</td>
                                                        @if ($invoice->lines->sum('discount_amount') > 0)
                                                        <td class="text-right">
                                                            @if (($l->discount_amount ?? 0) > 0)
                                                                <span class="text-danger">- Rp {{ number_format($l->discount_amount, 2) }}</span>
                                                                @if (($l->discount_percentage ?? 0) > 0)
                                                                    <br><small class="text-muted">({{ number_format($l->discount_percentage, 2) }}%)</small>
                                                                @endif
                                                            @else
                                                                <span class="text-muted">Rp 0.00</span>
                                                            @endif
                                                        </td>
                                                        <td class="text-right">Rp {{ number_format(($l->net_amount ?? $l->amount), 2) }}</td>
                                                        @endif
                                                        <td class="text-right">
                                                            @if ($l->vat_amount > 0)
                                                                <span class="text-success">Rp {{ number_format($l->vat_amount, 2) }}</span>
                                                            @else
                                                                <span class="text-muted">Rp {{ number_format(0, 2) }}</span>
                                                            @endif
                                                        </td>
                                                        <td class="text-right">
                                                            <strong>Rp {{ number_format(($l->amount_after_vat && $l->amount_after_vat > 0) ? $l->amount_after_vat : $l->amount, 2) }}</strong>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                            <tfoot class="thead-light">
                                                @php $colspan = $invoice->lines->sum('discount_amount') > 0 ? 11 : 9; @endphp
                                                <tr>
                                                    <th colspan="{{ $colspan }}" class="text-right">Subtotal:</th>
                                                    <th class="text-right"><strong>Rp {{ number_format($totalAmountAfterVat, 2) }}</strong></th>
                                                </tr>
                                                @if (($invoice->discount_amount ?? 0) > 0)
                                                <tr>
                                                    <th colspan="{{ $colspan }}" class="text-right">Discount
                                                        @if (($invoice->discount_percentage ?? 0) > 0)
                                                            ({{ number_format($invoice->discount_percentage, 2) }}%)
                                                        @endif
                                                    </th>
                                                    <th class="text-right text-danger">- Rp {{ number_format($invoice->discount_amount, 2) }}</th>
                                                </tr>
                                                @endif
                                                <tr>
                                                    <th colspan="{{ $colspan }}" class="text-right">Total:</th>
                                                    <th class="text-right"><strong>Rp {{ number_format($invoice->total_amount, 2) }}</strong></th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            {{-- Payment Allocations --}}
                            @if ($invoice->paymentAllocations && $invoice->paymentAllocations->count() > 0)
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <h5 class="mb-3">
                                            <i class="fas fa-money-check-alt mr-1"></i> Payment Allocations
                                        </h5>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-striped">
                                                <thead class="thead-light">
                                                    <tr>
                                                        <th>Payment Date</th>
                                                        <th>Payment Number</th>
                                                        <th class="text-right">Amount</th>
                                                        <th>Status</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($invoice->paymentAllocations as $allocation)
                                                        <tr>
                                                            <td>
                                                                {{ $allocation->payment && $allocation->payment->date ? $allocation->payment->date->format('d M Y') : '—' }}
                                                            </td>
                                                            <td>
                                                                @if ($allocation->payment)
                                                                    <a href="{{ route('purchase-payments.show', $allocation->payment->id) }}"
                                                                        class="badge badge-info">
                                                                        {{ $allocation->payment->payment_no ?? '#' . $allocation->payment->id }}
                                                                    </a>
                                                                @else
                                                                    —
                                                                @endif
                                                            </td>
                                                            <td class="text-right">Rp {{ number_format($allocation->amount, 2) }}</td>
                                                            <td>
                                                                @if ($allocation->payment)
                                                                    <span class="badge badge-{{ $allocation->payment->status === 'posted' ? 'success' : 'secondary' }}">
                                                                        {{ strtoupper($allocation->payment->status) }}
                                                                    </span>
                                                                @endif
                                                            </td>
                                                            <td>
                                                                @if ($allocation->payment)
                                                                    <a href="{{ route('purchase-payments.show', $allocation->payment->id) }}"
                                                                        class="btn btn-xs btn-info">View</a>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                                <tfoot class="thead-light">
                                                    <tr>
                                                        <th colspan="2" class="text-right">Total Allocated:</th>
                                                        <th class="text-right">Rp {{ number_format($totalAllocated, 2) }}</th>
                                                        <th colspan="2"></th>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            {{-- Journal Entry Link --}}
                            @php
                                $journal = DB::table('journals')
                                    ->where('source_type', 'purchase_invoice')
                                    ->where('source_id', $invoice->id)
                                    ->first();
                            @endphp
                            @if ($journal)
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <div class="alert alert-info">
                                            <i class="fas fa-book mr-1"></i>
                                            <strong>Journal Entry:</strong>
                                            <span class="ml-2">Journal #{{ $journal->journal_no ?? $journal->id }}</span>
                                            <span class="text-muted ml-2">(Posted: {{ \Carbon\Carbon::parse($journal->date)->format('d M Y') }})</span>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            {{-- Inventory Transactions --}}
                            @if ($invoice->is_direct_purchase && $invoice->inventoryTransactions && $invoice->inventoryTransactions->count() > 0)
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <h5 class="mb-3">
                                            <i class="fas fa-boxes mr-1"></i> Inventory Transactions
                                        </h5>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-striped">
                                                <thead class="thead-light">
                                                    <tr>
                                                        <th>Item</th>
                                                        <th>Warehouse</th>
                                                        <th class="text-right">Quantity</th>
                                                        <th class="text-right">Unit Cost</th>
                                                        <th class="text-right">Total Cost</th>
                                                        <th>Transaction Date</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($invoice->inventoryTransactions as $transaction)
                                                        <tr>
                                                            <td>
                                                                @if ($transaction->item)
                                                                    <strong>{{ $transaction->item->name }}</strong>
                                                                    <br><small class="text-muted">{{ $transaction->item->item_code }}</small>
                                                                @else
                                                                    —
                                                                @endif
                                                            </td>
                                                            <td>{{ $transaction->warehouse->name ?? '—' }}</td>
                                                            <td class="text-right">
                                                                <span class="badge badge-success">+{{ number_format($transaction->quantity, 2) }}</span>
                                                            </td>
                                                            <td class="text-right">Rp {{ number_format($transaction->unit_cost, 2) }}</td>
                                                            <td class="text-right">Rp {{ number_format($transaction->total_cost, 2) }}</td>
                                                            <td>{{ $transaction->transaction_date->format('d M Y') }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Include Relationship Map Modal --}}
    @include('components.relationship-map-modal')
@endsection
