@extends('layouts.main')

@section('title', 'Purchase Payment #' . $payment->id)

@section('title_page')
    Purchase Payment #{{ $payment->id }}
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('purchase-payments.index') }}">Purchase Payments</a></li>
    <li class="breadcrumb-item active">#{{ $payment->id }}</li>
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
                            <h3 class="card-title">Purchase Payment #{{ $payment->id }}
                                ({{ strtoupper($payment->status) }})
                            </h3>
                            <div>
                                <button type="button" class="btn btn-sm btn-info mr-1"
                                    onclick="showRelationshipMap('purchase-payments', {{ $payment->id }})">
                                    <i class="fas fa-sitemap"></i> Relationship Map
                                </button>
                                @can('ap.payments.post')
                                    @if ($payment->status !== 'posted')
                                        <form method="post" action="{{ route('purchase-payments.post', $payment->id) }}"
                                            class="d-inline">
                                            @csrf
                                            <button class="btn btn-sm btn-success" type="submit">Post</button>
                                        </form>
                                    @endif
                                @endcan
                                <a class="btn btn-sm btn-outline-secondary"
                                    href="{{ route('purchase-payments.print', $payment->id) }}" target="_blank">Print</a>
                                <a class="btn btn-sm btn-outline-primary"
                                    href="{{ route('purchase-payments.pdf', $payment->id) }}" target="_blank">PDF</a>
                                <form method="post" action="{{ route('purchase-payments.queuePdf', $payment->id) }}"
                                    class="d-inline">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-info" type="submit">Queue PDF</button>
                                </form>
                            </div>
                        </div>

                        {{-- Document Navigation Components --}}
                        <div class="card-body border-bottom">
                            @include('components.document-navigation', [
                                'documentType' => 'purchase-payment',
                                'documentId' => $payment->id,
                            ])
                        </div>

                        <div class="card-body">
                            <p>Date: {{ $payment->date }}</p>
                            <p>Vendor:
                                {{ optional(DB::table('business_partners')->find($payment->business_partner_id))->name }}
                            </p>
                            <p>Description: {{ $payment->description }}</p>
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
                                    @foreach ($payment->lines as $l)
                                        <tr>
                                            <td>{{ optional(DB::table('accounts')->find($l->account_id))->code }}</td>
                                            <td>{{ $l->description }}</td>
                                            <td style="text-align:right">{{ number_format($l->amount, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <p class="text-right"><strong>Total: {{ number_format($payment->total_amount, 2) }}</strong>
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
