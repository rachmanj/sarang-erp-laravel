@extends('layouts.main')

@section('title', 'Sales Receipt ' . ($receipt->receipt_no ?? '#' . $receipt->id))

@section('title_page')
    Sales Receipt {{ $receipt->receipt_no ?? '#' . $receipt->id }}
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('sales-receipts.index') }}">Sales Receipts</a></li>
    <li class="breadcrumb-item active">{{ $receipt->receipt_no ?? '#' . $receipt->id }}</li>
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
                            <h3 class="card-title">
                                Sales Receipt {{ $receipt->receipt_no ?? '#' . $receipt->id }}
                                <span class="badge badge-{{ $receipt->status === 'posted' ? 'success' : 'warning' }} ml-2">
                                    {{ strtoupper($receipt->status) }}
                                </span>
                            </h3>
                            <div>
                                <button type="button" class="btn btn-sm btn-info mr-1"
                                    onclick="showRelationshipMap('sales-receipts', {{ $receipt->id }})">
                                    <i class="fas fa-sitemap"></i> Relationship Map
                                </button>
                                @can('ar.receipts.post')
                                    @if ($receipt->status !== 'posted')
                                        <form method="post" action="{{ route('sales-receipts.post', $receipt->id) }}"
                                            class="d-inline">
                                            @csrf
                                            <button class="btn btn-sm btn-success" type="submit">Post</button>
                                        </form>
                                    @endif
                                @endcan
                                <a class="btn btn-sm btn-outline-secondary"
                                    href="{{ route('sales-receipts.print', $receipt->id) }}" target="_blank">Print</a>
                                <a class="btn btn-sm btn-outline-primary"
                                    href="{{ route('sales-receipts.pdf', $receipt->id) }}" target="_blank">PDF</a>
                                <form method="post" action="{{ route('sales-receipts.queuePdf', $receipt->id) }}"
                                    class="d-inline">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-info" type="submit">Queue PDF</button>
                                </form>
                            </div>
                        </div>

                        <div class="card-body border-bottom">
                            @include('components.document-navigation', [
                                'documentType' => 'sales-receipt',
                                'documentId' => $receipt->id,
                            ])
                        </div>

                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h5 class="mb-3">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        Receipt Information
                                    </h5>
                                    <table class="table table-borderless table-sm">
                                        <tr>
                                            <th width="40%">Receipt Number:</th>
                                            <td><strong>{{ $receipt->receipt_no ?? 'N/A' }}</strong></td>
                                        </tr>
                                        <tr>
                                            <th>Receipt Date:</th>
                                            <td>{{ $receipt->date->format('d M Y') }}</td>
                                        </tr>
                                        <tr>
                                            <th>Customer:</th>
                                            <td>
                                                <a href="{{ route('business_partners.show', $receipt->business_partner_id) }}">
                                                    {{ optional(DB::table('business_partners')->find($receipt->business_partner_id))->name }}
                                                </a>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Company Entity:</th>
                                            <td>
                                                {{ optional(DB::table('company_entities')->find($receipt->company_entity_id))->name ?? 'N/A' }}
                                            </td>
                                        </tr>
                                        @if ($receipt->description)
                                            <tr>
                                                <th>Description:</th>
                                                <td>{{ $receipt->description }}</td>
                                            </tr>
                                        @endif
                                        <tr>
                                            <th>Total Amount:</th>
                                            <td><strong
                                                    class="text-primary">{{ number_format($receipt->total_amount, 2) }}</strong>
                                            </td>
                                        </tr>
                                        @if ($receipt->posted_at)
                                            <tr>
                                                <th>Posted At:</th>
                                                <td>{{ \Carbon\Carbon::parse($receipt->posted_at)->format('d M Y H:i') }}
                                                </td>
                                            </tr>
                                        @endif
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h5 class="mb-3">
                                        <i class="fas fa-user-clock mr-1"></i>
                                        System Information
                                    </h5>
                                    <table class="table table-borderless table-sm">
                                        <tr>
                                            <th width="40%">Created At:</th>
                                            <td>{{ $receipt->created_at->format('d M Y H:i') }}</td>
                                        </tr>
                                        @if ($creator)
                                            <tr>
                                                <th>Created By:</th>
                                                <td>{{ $creator->name }}</td>
                                            </tr>
                                        @endif
                                        <tr>
                                            <th>Last Updated:</th>
                                            <td>{{ $receipt->updated_at->format('d M Y H:i') }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            <hr>

                            @if ($allocations && $allocations->count() > 0)
                                <div class="mt-4">
                                    <h5 class="mb-3">
                                        <i class="fas fa-file-invoice-dollar mr-1"></i>
                                        Sales Invoices Being Paid
                                    </h5>
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Invoice #</th>
                                                    <th>Invoice Date</th>
                                                    <th>Due Date</th>
                                                    <th class="text-right">Invoice Total</th>
                                                    <th class="text-right">Allocation Amount</th>
                                                    <th>Status</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($allocations as $allocation)
                                                    @php
                                                        $invoice = DB::table('sales_invoices')->find($allocation->invoice_id);
                                                        $totalAllocated = DB::table('sales_receipt_allocations')
                                                            ->where('invoice_id', $allocation->invoice_id)
                                                            ->sum('amount');
                                                        $remaining = (float) $invoice->total_amount - (float) $totalAllocated;
                                                    @endphp
                                                    <tr>
                                                        <td>
                                                            <a href="{{ route('sales-invoices.show', $allocation->invoice_id) }}"
                                                                class="font-weight-bold">
                                                                {{ $allocation->invoice_no }}
                                                            </a>
                                                        </td>
                                                        <td>{{ \Carbon\Carbon::parse($allocation->invoice_date)->format('d M Y') }}
                                                        </td>
                                                        <td>
                                                            @if ($allocation->due_date)
                                                                {{ \Carbon\Carbon::parse($allocation->due_date)->format('d M Y') }}
                                                            @else
                                                                <span class="text-muted">-</span>
                                                            @endif
                                                        </td>
                                                        <td class="text-right">
                                                            {{ number_format($allocation->invoice_total, 2) }}</td>
                                                        <td class="text-right">
                                                            <strong
                                                                class="text-success">{{ number_format($allocation->allocation_amount, 2) }}</strong>
                                                        </td>
                                                        <td>
                                                            @if ($allocation->invoice_status === 'posted')
                                                                <span class="badge badge-success">Posted</span>
                                                            @else
                                                                <span
                                                                    class="badge badge-warning">{{ ucfirst($allocation->invoice_status) }}</span>
                                                            @endif
                                                            @if ($remaining <= 0.01)
                                                                <span class="badge badge-info ml-1">Fully Paid</span>
                                                            @elseif ($remaining < $allocation->invoice_total)
                                                                <span class="badge badge-warning ml-1">Partially Paid</span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            <a href="{{ route('sales-invoices.show', $allocation->invoice_id) }}"
                                                                class="btn btn-xs btn-info" title="View Invoice">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <th colspan="4" class="text-right">Total Allocation:</th>
                                                    <th class="text-right text-success">
                                                        {{ number_format($allocations->sum('allocation_amount'), 2) }}
                                                    </th>
                                                    <th colspan="2"></th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                                <hr>
                            @endif

                            <div class="mt-4">
                                <h5 class="mb-3">
                                    <i class="fas fa-list-ul mr-1"></i>
                                    Receipt Lines
                                </h5>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Account Code</th>
                                                <th>Account Name</th>
                                                <th>Description</th>
                                                <th class="text-right" style="width: 160px;">Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($receipt->lines as $line)
                                                @php
                                                    $account = DB::table('accounts')->find($line->account_id);
                                                @endphp
                                                <tr>
                                                    <td>{{ $account->code ?? 'N/A' }}</td>
                                                    <td>{{ $account->name ?? 'N/A' }}</td>
                                                    <td>{{ $line->description ?? '-' }}</td>
                                                    <td class="text-right">{{ number_format($line->amount, 2) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th colspan="3" class="text-right">Total Receipt:</th>
                                                <th class="text-right text-primary">
                                                    {{ number_format($receipt->total_amount, 2) }}
                                                </th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @include('components.relationship-map-modal')
@endsection
