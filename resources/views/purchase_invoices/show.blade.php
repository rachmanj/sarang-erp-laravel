@extends('layouts.main')

@section('title', 'Purchase Invoice #' . $invoice->id)

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
                            <h3 class="card-title">Purchase Invoice #{{ $invoice->id }} ({{ strtoupper($invoice->status) }})
                            </h3>
                            <div>
                                @can('ap.invoices.post')
                                    @if ($invoice->status !== 'posted')
                                        <form method="post" action="{{ route('purchase-invoices.post', $invoice->id) }}"
                                            class="d-inline">
                                            @csrf
                                            <button class="btn btn-sm btn-success" type="submit">Post</button>
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
                        <div class="card-body">
                            <p>Date: {{ $invoice->date }}</p>
                            <p>Vendor: {{ optional(DB::table('vendors')->find($invoice->vendor_id))->name }}</p>
                            <p>Description: {{ $invoice->description }}</p>
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
                            <p>Allocated: {{ number_format($alloc, 2) }} | Remaining: {{ number_format($remaining, 2) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
