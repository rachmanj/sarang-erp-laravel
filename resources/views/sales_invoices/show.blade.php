@extends('layouts.main')

@section('title', 'Invoice #' . $invoice->id)

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
                            <h3 class="card-title">Invoice #{{ $invoice->id }} ({{ strtoupper($invoice->status) }})</h3>
                            <div>
                                @can('ar.invoices.post')
                                    @if ($invoice->status !== 'posted')
                                        <form method="post" action="{{ route('sales-invoices.post', $invoice->id) }}"
                                            class="d-inline">
                                            @csrf
                                            <button class="btn btn-sm btn-success" type="submit">Post</button>
                                        </form>
                                    @endif
                                @endcan
                                <a class="btn btn-sm btn-outline-secondary"
                                    href="{{ route('sales-invoices.print', $invoice->id) }}" target="_blank">Print</a>
                                <a class="btn btn-sm btn-outline-primary"
                                    href="{{ route('sales-invoices.pdf', $invoice->id) }}" target="_blank">PDF</a>
                                <form method="post" action="{{ route('sales-invoices.queuePdf', $invoice->id) }}"
                                    class="d-inline">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-info" type="submit">Queue PDF</button>
                                </form>
                            </div>
                        </div>
                        <div class="card-body">
                            <p>Date: {{ $invoice->date }}</p>
                            <p>Customer:
                                {{ optional(DB::table('business_partners')->find($invoice->business_partner_id))->name }}
                            </p>
                            <p>Description: {{ $invoice->description }}</p>
                            @if (!empty($invoice->sales_order_id))
                                <p><strong>Related:</strong> <a
                                        href="{{ route('sales-orders.show', $invoice->sales_order_id) }}"
                                        class="badge badge-info">SO #{{ $invoice->sales_order_id }}</a></p>
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
                                $alloc = DB::table('sales_receipt_allocations')
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
@endsection
