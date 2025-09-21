@extends('layouts.main')

@section('title', 'Sales Receipt #' . $receipt->id)

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
                            <h3 class="card-title">Sales Receipt #{{ $receipt->id }} ({{ strtoupper($receipt->status) }})
                            </h3>
                            <div>
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
                        <div class="card-body">
                            <p>Date: {{ $receipt->date }}</p>
                            <p>Customer:
                                {{ optional(DB::table('business_partners')->find($receipt->business_partner_id))->name }}
                            </p>
                            <p>Description: {{ $receipt->description }}</p>
                            <hr>
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Account</th>
                                        <th>Description</th>
                                        <th style="width: 160px;">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($receipt->lines as $l)
                                        <tr>
                                            <td>{{ optional(DB::table('accounts')->find($l->account_id))->code }}</td>
                                            <td>{{ $l->description }}</td>
                                            <td style="text-align:right">{{ number_format($l->amount, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <p class="text-right"><strong>Total: {{ number_format($receipt->total_amount, 2) }}</strong>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
