@extends('layouts.main')

@section('title_page')
    Bank Reconciliation Workbench
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('bank-reconciliation.index') }}">Bank Reconciliation</a></li>
    <li class="breadcrumb-item active">Session #{{ $bankReconciliation->id }}</li>
@endsection

@section('content')
    @php
        $bankAccount = $bankReconciliation->bankAccount;
        $isEditable = ! $bankReconciliation->isLockedForEditing() && $bankReconciliation->status !== \App\Models\Bank\BankReconciliation::STATUS_PROCESSING;
    @endphp

    @if (session('success'))
        <script>toastr.success(@json(session('success')));</script>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($bankReconciliation->status === \App\Models\Bank\BankReconciliation::STATUS_PROCESSING)
        <div class="alert alert-info" id="processing-alert">
            <i class="fas fa-spinner fa-spin"></i> Processing statement and fetching book lines…
        </div>
    @endif

    @if ($bankReconciliation->status === \App\Models\Bank\BankReconciliation::STATUS_FAILED)
        <div class="alert alert-danger">
            <strong>Failed:</strong> {{ $bankReconciliation->notes }}
        </div>
    @endif

    <div class="card card-outline card-primary mb-3">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h4 class="card-title mb-0">{{ $bankAccount->name }} ({{ $bankAccount->account_number }})</h4>
                <small class="text-muted">
                    Period: {{ $bankReconciliation->periode->format('F Y') }}
                    | COA: {{ $bankAccount->account?->code }} - {{ $bankAccount->account?->name }}
                    | Mode: {{ strtoupper($bankReconciliation->source_mode) }}
                </small>
            </div>
            <div class="d-flex flex-wrap" style="gap: 0.35rem;">
                @if ($isEditable)
                    @can('bank_reconciliation.reconcile')
                        @if ($bankReconciliation->source_mode === 'ai')
                            <form method="POST" action="{{ route('bank-reconciliation.parse', $bankReconciliation) }}">@csrf<button class="btn btn-sm btn-outline-info">Re-parse PDF</button></form>
                        @endif
                        <form method="POST" action="{{ route('bank-reconciliation.fetch-book', $bankReconciliation) }}">@csrf<button class="btn btn-sm btn-outline-secondary">Fetch Book Lines</button></form>
                        <form method="POST" action="{{ route('bank-reconciliation.auto-match', $bankReconciliation) }}">@csrf<button class="btn btn-sm btn-info">Auto Match</button></form>
                    @endcan
                    @can('bank_reconciliation.finalize')
                        <form method="POST" action="{{ route('bank-reconciliation.finalize', $bankReconciliation) }}">@csrf<button class="btn btn-sm btn-success" id="finalize-btn" @disabled(! ($balance['is_balanced'] ?? false))>Finalize</button></form>
                    @endcan
                @endif
                @if ($bankReconciliation->status === \App\Models\Bank\BankReconciliation::STATUS_COMPLETED)
                    <a href="{{ route('bank-reconciliation.report', $bankReconciliation) }}" class="btn btn-sm btn-secondary">Report</a>
                @endif
                <a href="{{ route('bank-reconciliation.index') }}" class="btn btn-sm btn-secondary">Back</a>
            </div>
        </div>
        <div class="card-body py-2">
            <div class="row text-center">
                <div class="col-md-3"><strong>Bank Net</strong><br><span id="bank-net">{{ number_format($balance['bank_net'], 2) }}</span></div>
                <div class="col-md-3"><strong>Book Net</strong><br><span id="book-net">{{ number_format($balance['book_net'], 2) }}</span></div>
                <div class="col-md-3"><strong>Difference</strong><br><span id="difference" class="{{ ($balance['is_balanced'] ?? false) ? 'text-success' : 'text-danger' }}">{{ number_format($balance['difference'], 2) }}</span></div>
                <div class="col-md-3"><strong>Match Groups</strong><br><span id="match-groups-count">{{ $balance['match_groups_count'] ?? 0 }}</span></div>
            </div>
        </div>
    </div>

    @if ($isEditable)
        @can('bank_reconciliation.reconcile')
            <form method="POST" action="{{ route('bank-reconciliation.match', $bankReconciliation) }}" id="manual-match-form" class="mb-3">
                @csrf
                <div class="d-flex flex-wrap align-items-center" style="gap: 0.5rem;">
                    <button type="submit" class="btn btn-primary btn-sm" id="match-selected-btn" disabled>Match Selected</button>
                    <small class="text-muted">Select bank line(s) and book line(s); totals must net to zero.</small>
                </div>
            </form>
        @endcan
    @endif

    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">Bank Lines ({{ $balance['bank_lines_count'] ?? 0 }})</h3>
                </div>
                <div class="card-body table-responsive p-0" style="max-height: 420px; overflow-y: auto;">
                    <table class="table table-sm table-striped mb-0">
                        <thead class="thead-light" style="position: sticky; top: 0;">
                            <tr>
                                @if ($isEditable) @can('bank_reconciliation.reconcile')<th></th>@endcan @endif
                                <th>Date</th>
                                <th>Description</th>
                                <th class="text-right">Debit</th>
                                <th class="text-right">Credit</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($bankReconciliation->bankLines as $line)
                                <tr class="{{ $line->match_status === 'unmatched' ? 'table-warning' : ($line->match_status === 'excluded' ? 'table-secondary' : '') }}">
                                    @if ($isEditable) @can('bank_reconciliation.reconcile')
                                        <td>
                                            @if ($line->match_status === 'unmatched')
                                                <input type="checkbox" class="bank-line-check" value="{{ $line->id }}" form="manual-match-form" name="bank_line_ids[]">
                                            @endif
                                        </td>
                                    @endcan @endif
                                    <td>{{ $line->posting_date->format('d/m/Y') }}</td>
                                    <td>
                                        <div>{{ \Illuminate\Support\Str::limit($line->description, 50) }}</div>
                                        @if ($line->reference_no)<small class="text-muted">{{ $line->reference_no }}</small>@endif
                                    </td>
                                    <td class="text-right">{{ $line->debit > 0 ? number_format((float) $line->debit, 2) : '-' }}</td>
                                    <td class="text-right">{{ $line->credit > 0 ? number_format((float) $line->credit, 2) : '-' }}</td>
                                    <td><span class="badge badge-secondary">{{ strtoupper($line->match_status) }}</span></td>
                                    <td class="text-nowrap">
                                        @if ($isEditable && in_array($line->match_status, ['unmatched', 'excluded']))
                                            @can('bank_reconciliation.reconcile')
                                                @if ($line->match_status === 'unmatched')
                                                    <form method="POST" action="{{ route('bank-reconciliation.lines.exclude', [$bankReconciliation, $line]) }}" class="d-inline exclude-form">
                                                        @csrf
                                                        <input type="hidden" name="exclude" value="1">
                                                        <input type="hidden" name="exclude_reason" value="Excluded by user">
                                                        <button class="btn btn-xs btn-secondary" title="Exclude">X</button>
                                                    </form>
                                                @else
                                                    <form method="POST" action="{{ route('bank-reconciliation.lines.exclude', [$bankReconciliation, $line]) }}" class="d-inline">
                                                        @csrf
                                                        <input type="hidden" name="exclude" value="0">
                                                        <button class="btn btn-xs btn-outline-primary" title="Include">+</button>
                                                    </form>
                                                @endif
                                            @endcan
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted">No bank lines.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($isEditable && $bankReconciliation->source_mode === 'manual')
                @can('bank_reconciliation.reconcile')
                    <div class="card mt-2">
                        <div class="card-header"><h3 class="card-title mb-0">Add Bank Line</h3></div>
                        <form method="POST" action="{{ route('bank-reconciliation.lines.store', $bankReconciliation) }}">
                            @csrf
                            <div class="card-body">@include('bank-reconciliation.partials.bank-line-fields')</div>
                            <div class="card-footer"><button class="btn btn-sm btn-primary">Add Line</button></div>
                        </form>
                    </div>
                @endcan
            @endif
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header"><h3 class="card-title mb-0">Book Lines ({{ $balance['book_lines_count'] ?? 0 }})</h3></div>
                <div class="card-body table-responsive p-0" style="max-height: 420px; overflow-y: auto;">
                    <table class="table table-sm table-striped mb-0">
                        <thead class="thead-light" style="position: sticky; top: 0;">
                            <tr>
                                @if ($isEditable) @can('bank_reconciliation.reconcile')<th></th>@endcan @endif
                                <th>Date</th>
                                <th>Description</th>
                                <th class="text-right">Debit</th>
                                <th class="text-right">Credit</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($bankReconciliation->bookLines as $line)
                                <tr class="{{ $line->match_status === 'unmatched' ? 'table-warning' : ($line->match_status === 'excluded' ? 'table-secondary' : '') }}">
                                    @if ($isEditable) @can('bank_reconciliation.reconcile')
                                        <td>
                                            @if ($line->match_status === 'unmatched')
                                                <input type="checkbox" class="book-line-check" value="{{ $line->id }}" form="manual-match-form" name="book_line_ids[]">
                                            @endif
                                        </td>
                                    @endcan @endif
                                    <td>{{ ($line->posting_date ?? $line->doc_date)?->format('d/m/Y') }}</td>
                                    <td>{{ \Illuminate\Support\Str::limit($line->description, 60) }}</td>
                                    <td class="text-right">{{ number_format((float) $line->debit, 2) }}</td>
                                    <td class="text-right">{{ number_format((float) $line->credit, 2) }}</td>
                                    <td><span class="badge badge-secondary">{{ strtoupper($line->match_status) }}</span></td>
                                    <td>
                                        @if ($isEditable && in_array($line->match_status, ['unmatched', 'excluded']))
                                            @can('bank_reconciliation.reconcile')
                                                @if ($line->match_status === 'unmatched')
                                                    <form method="POST" action="{{ route('bank-reconciliation.book-lines.exclude', [$bankReconciliation, $line]) }}" class="d-inline">
                                                        @csrf
                                                        <input type="hidden" name="exclude" value="1">
                                                        <input type="hidden" name="exclude_reason" value="Excluded by user">
                                                        <button class="btn btn-xs btn-secondary">X</button>
                                                    </form>
                                                @else
                                                    <form method="POST" action="{{ route('bank-reconciliation.book-lines.exclude', [$bankReconciliation, $line]) }}" class="d-inline">
                                                        @csrf
                                                        <input type="hidden" name="exclude" value="0">
                                                        <button class="btn btn-xs btn-outline-primary">+</button>
                                                    </form>
                                                @endif
                                            @endcan
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted">No book lines. Click Fetch Book Lines.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header"><h3 class="card-title mb-0">Match Groups</h3></div>
        <div class="card-body table-responsive p-0">
            <table class="table table-sm mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Type</th>
                        <th class="text-right">Bank Total</th>
                        <th class="text-right">Book Total</th>
                        <th class="text-right">Diff</th>
                        <th>Lines</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($bankReconciliation->matchGroups as $group)
                        <tr>
                            <td>{{ $group->id }}</td>
                            <td>{{ strtoupper(str_replace('_', ' ', $group->match_type)) }}</td>
                            <td class="text-right">{{ number_format((float) $group->bank_total, 2) }}</td>
                            <td class="text-right">{{ number_format((float) $group->book_total, 2) }}</td>
                            <td class="text-right">{{ number_format((float) $group->difference, 2) }}</td>
                            <td>{{ $group->bankLines->count() }} bank / {{ $group->bookLines->count() }} book</td>
                            <td>
                                @if ($isEditable)
                                    @can('bank_reconciliation.reconcile')
                                        <form method="POST" action="{{ route('bank-reconciliation.unmatch', [$bankReconciliation, $group]) }}" class="d-inline">
                                            @csrf
                                            <button class="btn btn-xs btn-danger" onclick="return confirm('Remove this match group?')">Unmatch</button>
                                        </form>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted">No match groups yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(function() {
            const statusUrl = @json(route('bank-reconciliation.status', $bankReconciliation));
            const isProcessing = @json($bankReconciliation->status === \App\Models\Bank\BankReconciliation::STATUS_PROCESSING);

            function updateMatchButton() {
                const bankCount = $('.bank-line-check:checked').length;
                const bookCount = $('.book-line-check:checked').length;
                $('#match-selected-btn').prop('disabled', bankCount === 0 || bookCount === 0);
            }

            $(document).on('change', '.bank-line-check, .book-line-check', updateMatchButton);

            function pollStatus() {
                $.get(statusUrl).done(function(data) {
                    $('#bank-net').text(Number(data.bank_net).toLocaleString(undefined, {minimumFractionDigits: 2}));
                    $('#book-net').text(Number(data.book_net).toLocaleString(undefined, {minimumFractionDigits: 2}));
                    $('#difference').text(Number(data.difference).toLocaleString(undefined, {minimumFractionDigits: 2}))
                        .toggleClass('text-success', data.is_balanced)
                        .toggleClass('text-danger', !data.is_balanced);
                    $('#match-groups-count').text(data.match_groups_count);
                    $('#finalize-btn').prop('disabled', !data.is_balanced);

                    if (data.status !== 'processing') {
                        $('#processing-alert').remove();
                        if (isProcessing) {
                            window.location.reload();
                        }
                    }
                });
            }

            if (isProcessing) {
                setInterval(pollStatus, 3000);
            }
        });
    </script>
@endpush
