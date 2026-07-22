@extends('layouts.main')

@section('title_page')
    Bank Reconciliation Report
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('bank-reconciliation.index') }}">Bank Reconciliation</a></li>
    <li class="breadcrumb-item active">Report #{{ $bankReconciliation->id }}</li>
@endsection

@section('content')
    @php
        $bankAccount = $bankReconciliation->bankAccount;
        $outstandingItems = $outstandingItems ?? collect();
    @endphp

    <div class="card" id="report-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h4 class="card-title mb-0">Bank Reconciliation Report</h4>
                <small class="text-muted">{{ $bankAccount->name }} — {{ $bankReconciliation->periode->format('F Y') }}</small>
            </div>
            <div class="d-print-none">
                <button onclick="window.print()" class="btn btn-sm btn-primary"><i class="fas fa-print"></i> Print / PDF</button>
                <a href="{{ route('bank-reconciliation.export', $bankReconciliation) }}" class="btn btn-sm btn-success">Export CSV</a>
                <a href="{{ route('bank-reconciliation.show', $bankReconciliation) }}" class="btn btn-sm btn-secondary">Back</a>
            </div>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-6">
                    <p><strong>Bank Account:</strong> {{ $bankAccount->name }} ({{ $bankAccount->account_number }})</p>
                    <p><strong>COA:</strong> {{ $bankAccount->account?->code }} — {{ $bankAccount->account?->name }}</p>
                    <p><strong>Period:</strong> {{ $bankReconciliation->periodStartDate()->format('d M Y') }} – {{ $bankReconciliation->periodEndDate()->format('d M Y') }}</p>
                    <p><strong>Currency:</strong> {{ strtoupper($bankAccount->currency ?: 'IDR') }}</p>
                </div>
                <div class="col-md-6">
                    <p><strong>Finalized:</strong> {{ $bankReconciliation->finalized_at?->format('d M Y H:i') ?? '-' }}</p>
                    <p><strong>Finalized By:</strong> {{ $bankReconciliation->finalizedBy?->name ?? '-' }}</p>
                    <p><strong>Source Mode:</strong> {{ strtoupper($bankReconciliation->source_mode) }}</p>
                </div>
            </div>

            <h5>Reconciliation Identity</h5>
            <table class="table table-sm table-bordered mb-4" style="max-width: 560px;">
                <tr><th>Statement Closing Balance</th><td class="text-right">{{ number_format($balance['statement_closing'], 2) }}</td></tr>
                <tr><th>Add: Deposits in Transit</th><td class="text-right">{{ number_format($balance['deposits_in_transit'], 2) }}</td></tr>
                <tr><th>Less: Outstanding Checks / Payments</th><td class="text-right">{{ number_format($balance['outstanding_checks'], 2) }}</td></tr>
                <tr><th>Adjusted Statement Balance</th><td class="text-right">{{ number_format($balance['adjusted_statement_balance'], 2) }}</td></tr>
                <tr><th>Book Closing Balance</th><td class="text-right">{{ number_format($balance['book_closing'], 2) }}</td></tr>
                <tr class="{{ $balance['is_balanced'] ? 'table-success' : 'table-danger' }}">
                    <th>Difference</th><td class="text-right">{{ number_format($balance['reconciliation_difference'], 2) }}</td>
                </tr>
            </table>

            <h5>Balance Summary</h5>
            <table class="table table-sm table-bordered mb-4" style="max-width: 480px;">
                <tr><th>Opening Balance (Bank)</th><td class="text-right">{{ number_format((float) ($bankReconciliation->opening_balance_bank ?? 0), 2) }}</td></tr>
                <tr><th>Closing Balance (Bank)</th><td class="text-right">{{ number_format((float) ($bankReconciliation->closing_balance_bank ?? 0), 2) }}</td></tr>
                <tr><th>Opening Balance (Book)</th><td class="text-right">{{ number_format((float) ($bankReconciliation->opening_balance_book ?? 0), 2) }}</td></tr>
                <tr><th>Closing Balance (Book)</th><td class="text-right">{{ number_format((float) ($bankReconciliation->closing_balance_book ?? 0), 2) }}</td></tr>
                <tr><th>Cleared Bank Net</th><td class="text-right">{{ number_format($balance['bank_net'], 2) }}</td></tr>
                <tr><th>Cleared Book Net</th><td class="text-right">{{ number_format($balance['book_net'], 2) }}</td></tr>
            </table>

            <h5>Outstanding Items Schedule</h5>
            <table class="table table-sm table-bordered mb-4">
                <thead>
                    <tr>
                        <th>Side</th>
                        <th>Date</th>
                        <th>Description</th>
                        <th class="text-right">Debit</th>
                        <th class="text-right">Credit</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($outstandingItems as $item)
                        <tr>
                            <td>{{ $item->side }}</td>
                            <td>{{ $item->posting_date?->format('d/m/Y') }}</td>
                            <td>{{ $item->description }}</td>
                            <td class="text-right">{{ number_format((float) $item->debit, 2) }}</td>
                            <td class="text-right">{{ number_format((float) $item->credit, 2) }}</td>
                            <td>{{ $item->line_notes }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted">No outstanding items.</td></tr>
                    @endforelse
                </tbody>
            </table>

            <h5>Match Groups ({{ $bankReconciliation->matchGroups->count() }})</h5>
            <table class="table table-sm table-bordered">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Type</th>
                        <th class="text-right">Bank Total</th>
                        <th class="text-right">Book Total</th>
                        <th>Bank Lines</th>
                        <th>Book Lines</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($bankReconciliation->matchGroups as $group)
                        <tr>
                            <td>{{ $group->id }}</td>
                            <td>{{ strtoupper(str_replace('_', ' ', $group->match_type)) }}</td>
                            <td class="text-right">{{ number_format((float) $group->bank_total, 2) }}</td>
                            <td class="text-right">{{ number_format((float) $group->book_total, 2) }}</td>
                            <td>
                                @foreach ($group->bankLines as $line)
                                    <div>{{ $line->posting_date->format('d/m/Y') }} — {{ \Illuminate\Support\Str::limit($line->description, 40) }} ({{ number_format($line->netAmount(), 2) }})</div>
                                @endforeach
                            </td>
                            <td>
                                @foreach ($group->bookLines as $line)
                                    <div>{{ ($line->posting_date ?? $line->doc_date)?->format('d/m/Y') }} — {{ \Illuminate\Support\Str::limit($line->description, 40) }} ({{ number_format($line->netAmount(), 2) }})</div>
                                @endforeach
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if ($bankReconciliation->matchAudits->isNotEmpty())
                <h5 class="mt-4">Audit Trail</h5>
                <table class="table table-sm table-bordered">
                    <thead>
                        <tr>
                            <th>When</th>
                            <th>Action</th>
                            <th>Type</th>
                            <th>By</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($bankReconciliation->matchAudits->sortByDesc('id') as $audit)
                            <tr>
                                <td>{{ $audit->created_at?->format('d/m/Y H:i') }}</td>
                                <td>{{ strtoupper($audit->action) }}</td>
                                <td>{{ $audit->match_type ? strtoupper(str_replace('_', ' ', $audit->match_type)) : '-' }}</td>
                                <td>{{ $audit->performedBy?->name ?? '-' }}</td>
                                <td>{{ $audit->notes }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
@endsection

@push('styles')
    <style>
        @media print {
            .main-sidebar, .main-header, .content-header, .d-print-none { display: none !important; }
            .content-wrapper { margin-left: 0 !important; }
        }
    </style>
@endpush
