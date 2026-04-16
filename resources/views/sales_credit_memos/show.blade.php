@extends('layouts.main')

@section('title', 'Credit Memo ' . ($memo->memo_no ?? '#' . $memo->id))

@section('title_page')
    Sales Credit Memo {{ $memo->memo_no ?? '#' . $memo->id }}
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('sales-credit-memos.index') }}">Sales Credit Memos</a></li>
    <li class="breadcrumb-item active">{{ $memo->memo_no ?? '#' . $memo->id }}</li>
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

            <div class="card card-warning card-outline">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-file-invoice mr-1"></i>
                        Credit Memo {{ $memo->memo_no ?? '#' . $memo->id }}
                        <span class="badge badge-{{ $memo->status === 'posted' ? 'success' : 'secondary' }} ml-2">{{ strtoupper($memo->status) }}</span>
                    </h3>
                    <div class="d-flex flex-wrap gap-1">
                        <a href="{{ route('sales-invoices.show', $memo->sales_invoice_id) }}" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-file-invoice-dollar"></i> Sales Invoice
                        </a>
                        @can('ar.credit-memos.post')
                            @if ($memo->status !== 'posted')
                                <form method="post" action="{{ route('sales-credit-memos.post', $memo->id) }}" class="d-inline" onsubmit="return confirm('Post this credit memo to the general ledger?');">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-success">Post</button>
                                </form>
                            @endif
                        @endcan
                        <a href="{{ route('sales-credit-memos.index') }}" class="btn btn-sm btn-secondary">List</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Date:</strong> {{ $memo->date?->format('d M Y') }}</p>
                            <p class="mb-1"><strong>Customer:</strong> {{ optional($memo->businessPartner)->name ?? '—' }}</p>
                            <p class="mb-0"><strong>Entity:</strong> {{ optional($memo->companyEntity)->name ?? '—' }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Sales invoice:</strong> {{ optional($memo->salesInvoice)->invoice_no ?? ('#'.$memo->sales_invoice_id) }}</p>
                            @if ($memo->description)
                                <p class="mb-0"><strong>Description:</strong> {{ $memo->description }}</p>
                            @endif
                        </div>
                    </div>

                    <h5 class="mb-2">Lines</h5>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="bg-light">
                                <tr>
                                    <th>Item</th>
                                    <th class="text-right">Qty</th>
                                    <th class="text-right">Amount</th>
                                    <th>Tax</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($memo->lines as $line)
                                    <tr>
                                        <td>
                                            <div>{{ $line->item_name ?? $line->description ?? '—' }}</div>
                                            @if ($line->item_code)
                                                <small class="text-muted">{{ $line->item_code }}</small>
                                            @endif
                                        </td>
                                        <td class="text-right">{{ number_format((float) $line->qty, 2) }}</td>
                                        <td class="text-right">{{ number_format((float) $line->amount, 2) }}</td>
                                        <td>{{ optional($line->taxCode)->code ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="2" class="text-right">Total</th>
                                    <th class="text-right">{{ number_format((float) $memo->total_amount, 2) }}</th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
