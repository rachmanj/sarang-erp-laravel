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
        $suggestions = $suggestions ?? [];
        $staleLines = $staleLines ?? [];
        $counterAccounts = $counterAccounts ?? collect();
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

    @if (count($staleLines) > 0)
        <div class="alert alert-warning">
            <strong>Snapshot integrity:</strong> {{ count($staleLines) }} book line(s) reference missing or changed journals.
            Re-fetch book lines before finalizing.
        </div>
    @endif

    @if (! empty($balance['cross_foot']) && ! $balance['cross_foot']['valid'] && ($balance['has_statement_balances'] ?? false))
        <div class="alert alert-warning">
            <strong>Cross-foot mismatch:</strong>
            Statement lines movement {{ number_format($balance['cross_foot']['actual_movement'], 2) }}
            vs expected {{ number_format($balance['cross_foot']['expected_movement'], 2) }}
            (diff {{ number_format($balance['cross_foot']['difference'], 2) }}).
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
                    | Currency: {{ strtoupper($bankAccount->currency ?: 'IDR') }}
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
                <a href="{{ route('bank-reconciliation.export', $bankReconciliation) }}" class="btn btn-sm btn-outline-success">Export CSV</a>
                <a href="{{ route('bank-reconciliation.index') }}" class="btn btn-sm btn-secondary">Back</a>
            </div>
        </div>
        <div class="card-body py-2">
            <div class="row text-center mb-2">
                <div class="col-md-2"><strong>Stmt Closing</strong><br><span id="statement-closing">{{ number_format($balance['statement_closing'], 2) }}</span></div>
                <div class="col-md-2"><strong>+ Deposits in Transit</strong><br><span id="deposits-in-transit">{{ number_format($balance['deposits_in_transit'], 2) }}</span></div>
                <div class="col-md-2"><strong>− Outstanding Checks</strong><br><span id="outstanding-checks">{{ number_format($balance['outstanding_checks'], 2) }}</span></div>
                <div class="col-md-2"><strong>= Adjusted Stmt</strong><br><span id="adjusted-statement">{{ number_format($balance['adjusted_statement_balance'], 2) }}</span></div>
                <div class="col-md-2"><strong>Book Closing</strong><br><span id="book-closing">{{ number_format($balance['book_closing'], 2) }}</span></div>
                <div class="col-md-2">
                    <strong>Recon Diff</strong><br>
                    <span id="recon-difference" class="{{ ($balance['is_balanced'] ?? false) ? 'text-success' : 'text-danger' }}">
                        {{ number_format($balance['reconciliation_difference'], 2) }}
                    </span>
                </div>
            </div>
            <div class="row text-center border-top pt-2">
                <div class="col-md-3"><strong>Cleared Bank Net</strong><br><span id="bank-net">{{ number_format($balance['bank_net'], 2) }}</span></div>
                <div class="col-md-3"><strong>Cleared Book Net</strong><br><span id="book-net">{{ number_format($balance['book_net'], 2) }}</span></div>
                <div class="col-md-3"><strong>Cleared Diff</strong><br><span id="difference">{{ number_format($balance['difference'], 2) }}</span></div>
                <div class="col-md-3"><strong>Match Groups</strong><br><span id="match-groups-count">{{ $balance['match_groups_count'] ?? 0 }}</span></div>
            </div>
        </div>
    </div>

    @if ($isEditable)
        @can('bank_reconciliation.reconcile')
            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title mb-0">Statement Balances</h3></div>
                <form method="POST" action="{{ route('bank-reconciliation.balances', $bankReconciliation) }}" class="card-body">
                    @csrf
                    <div class="form-row align-items-end">
                        <div class="form-group col-md-3">
                            <label>Opening (Bank Statement)</label>
                            <input type="number" step="0.01" name="opening_balance_bank" class="form-control form-control-sm"
                                   value="{{ old('opening_balance_bank', $bankReconciliation->opening_balance_bank ?? $bankReconciliation->statement_opening ?? 0) }}">
                        </div>
                        <div class="form-group col-md-3">
                            <label>Closing (Bank Statement)</label>
                            <input type="number" step="0.01" name="closing_balance_bank" class="form-control form-control-sm"
                                   value="{{ old('closing_balance_bank', $bankReconciliation->closing_balance_bank ?? $bankReconciliation->statement_closing ?? 0) }}">
                        </div>
                        <div class="form-group col-md-3">
                            <button class="btn btn-sm btn-primary">Update Balances</button>
                        </div>
                    </div>
                </form>
            </div>

            <form method="POST" action="{{ route('bank-reconciliation.match', $bankReconciliation) }}" id="manual-match-form" class="mb-3">
                @csrf
                <div class="d-flex flex-wrap align-items-center" style="gap: 0.5rem;">
                    <button type="submit" class="btn btn-primary btn-sm" id="match-selected-btn" disabled>Match Selected</button>
                    <input type="search" id="line-filter" class="form-control form-control-sm" style="max-width: 240px;" placeholder="Filter lines…">
                    <select id="status-filter" class="form-control form-control-sm" style="max-width: 160px;">
                        <option value="">All statuses</option>
                        <option value="unmatched">Unmatched</option>
                        <option value="matched">Matched</option>
                        <option value="manual">Manual</option>
                        <option value="outstanding">Outstanding</option>
                        <option value="excluded">Excluded</option>
                    </select>
                    <small class="text-muted">Select bank + book lines; totals must net to zero. Use Outstanding for timing differences.</small>
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
                    <table class="table table-sm table-striped mb-0" id="bank-lines-table">
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
                                @php
                                    $rowClass = match ($line->match_status) {
                                        'unmatched' => 'table-warning',
                                        'excluded' => 'table-secondary',
                                        'outstanding' => 'table-info',
                                        default => '',
                                    };
                                    $lineSuggestions = $suggestions[$line->id] ?? [];
                                @endphp
                                <tr class="line-row {{ $rowClass }}" data-status="{{ $line->match_status }}" data-search="{{ strtolower(($line->description ?? '').' '.($line->reference_no ?? '')) }}">
                                    @if ($isEditable) @can('bank_reconciliation.reconcile')
                                        <td>
                                            @if ($line->match_status === 'unmatched')
                                                <input type="checkbox" class="bank-line-check" value="{{ $line->id }}" form="manual-match-form" name="bank_line_ids[]"
                                                       @if (! empty($lineSuggestions)) data-suggest="{{ $lineSuggestions[0]['book_line_id'] }}" @endif>
                                            @endif
                                        </td>
                                    @endcan @endif
                                    <td>{{ $line->posting_date->format('d/m/Y') }}</td>
                                    <td>
                                        <div>{{ \Illuminate\Support\Str::limit($line->description, 50) }}</div>
                                        @if ($line->reference_no)<small class="text-muted">{{ $line->reference_no }}</small>@endif
                                        @if ($line->is_carried_forward)<span class="badge badge-info">CF</span>@endif
                                        @if (! empty($lineSuggestions))
                                            <div class="small text-primary">Suggest: book #{{ $lineSuggestions[0]['book_line_id'] }} ({{ $lineSuggestions[0]['score'] }})</div>
                                        @endif
                                    </td>
                                    <td class="text-right">{{ $line->debit > 0 ? number_format((float) $line->debit, 2) : '-' }}</td>
                                    <td class="text-right">{{ $line->credit > 0 ? number_format((float) $line->credit, 2) : '-' }}</td>
                                    <td><span class="badge badge-secondary">{{ strtoupper($line->match_status) }}</span></td>
                                    <td class="text-nowrap">
                                        @if ($isEditable && in_array($line->match_status, ['unmatched', 'excluded', 'outstanding']))
                                            @can('bank_reconciliation.reconcile')
                                                @if ($line->match_status === 'unmatched')
                                                    <form method="POST" action="{{ route('bank-reconciliation.lines.outstanding', [$bankReconciliation, $line]) }}" class="d-inline">
                                                        @csrf
                                                        <input type="hidden" name="outstanding" value="1">
                                                        <input type="hidden" name="reason" value="Timing difference">
                                                        <button class="btn btn-xs btn-info" title="Mark Outstanding">O</button>
                                                    </form>
                                                    <form method="POST" action="{{ route('bank-reconciliation.lines.exclude', [$bankReconciliation, $line]) }}" class="d-inline exclude-form">
                                                        @csrf
                                                        <input type="hidden" name="exclude" value="1">
                                                        <input type="hidden" name="exclude_reason" value="Excluded by user">
                                                        <button class="btn btn-xs btn-secondary" title="Exclude">X</button>
                                                    </form>
                                                    <button type="button" class="btn btn-xs btn-warning adjust-btn"
                                                            data-line-id="{{ $line->id }}"
                                                            data-desc="{{ $line->description }}"
                                                            title="Post adjusting journal">J</button>
                                                @elseif ($line->match_status === 'outstanding')
                                                    <form method="POST" action="{{ route('bank-reconciliation.lines.outstanding', [$bankReconciliation, $line]) }}" class="d-inline">
                                                        @csrf
                                                        <input type="hidden" name="outstanding" value="0">
                                                        <button class="btn btn-xs btn-outline-primary" title="Clear Outstanding">+</button>
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
                    <table class="table table-sm table-striped mb-0" id="book-lines-table">
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
                                @php
                                    $rowClass = match ($line->match_status) {
                                        'unmatched' => 'table-warning',
                                        'excluded' => 'table-secondary',
                                        'outstanding' => 'table-info',
                                        default => '',
                                    };
                                    if ($line->is_stale) {
                                        $rowClass = 'table-danger';
                                    }
                                @endphp
                                <tr class="line-row {{ $rowClass }}" data-status="{{ $line->match_status }}" data-search="{{ strtolower($line->description ?? '') }}" data-book-id="{{ $line->id }}">
                                    @if ($isEditable) @can('bank_reconciliation.reconcile')
                                        <td>
                                            @if ($line->match_status === 'unmatched')
                                                <input type="checkbox" class="book-line-check" value="{{ $line->id }}" form="manual-match-form" name="book_line_ids[]">
                                            @endif
                                        </td>
                                    @endcan @endif
                                    <td>{{ ($line->posting_date ?? $line->doc_date)?->format('d/m/Y') }}</td>
                                    <td>
                                        {{ \Illuminate\Support\Str::limit($line->description, 60) }}
                                        @if ($line->is_carried_forward)<span class="badge badge-info">CF</span>@endif
                                        @if ($line->is_stale)<span class="badge badge-danger" title="{{ $line->stale_reason }}">STALE</span>@endif
                                    </td>
                                    <td class="text-right">{{ number_format((float) $line->debit, 2) }}</td>
                                    <td class="text-right">{{ number_format((float) $line->credit, 2) }}</td>
                                    <td><span class="badge badge-secondary">{{ strtoupper($line->match_status) }}</span></td>
                                    <td>
                                        @if ($isEditable && in_array($line->match_status, ['unmatched', 'excluded', 'outstanding']))
                                            @can('bank_reconciliation.reconcile')
                                                @if ($line->match_status === 'unmatched')
                                                    <form method="POST" action="{{ route('bank-reconciliation.book-lines.outstanding', [$bankReconciliation, $line]) }}" class="d-inline">
                                                        @csrf
                                                        <input type="hidden" name="outstanding" value="1">
                                                        <input type="hidden" name="reason" value="Timing difference">
                                                        <button class="btn btn-xs btn-info" title="Mark Outstanding">O</button>
                                                    </form>
                                                    <form method="POST" action="{{ route('bank-reconciliation.book-lines.exclude', [$bankReconciliation, $line]) }}" class="d-inline">
                                                        @csrf
                                                        <input type="hidden" name="exclude" value="1">
                                                        <input type="hidden" name="exclude_reason" value="Excluded by user">
                                                        <button class="btn btn-xs btn-secondary">X</button>
                                                    </form>
                                                @elseif ($line->match_status === 'outstanding')
                                                    <form method="POST" action="{{ route('bank-reconciliation.book-lines.outstanding', [$bankReconciliation, $line]) }}" class="d-inline">
                                                        @csrf
                                                        <input type="hidden" name="outstanding" value="0">
                                                        <button class="btn btn-xs btn-outline-primary">+</button>
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

    @if ($bankReconciliation->matchAudits->isNotEmpty())
        <div class="card mt-3">
            <div class="card-header"><h3 class="card-title mb-0">Audit Trail</h3></div>
            <div class="card-body table-responsive p-0" style="max-height: 240px; overflow-y: auto;">
                <table class="table table-sm mb-0">
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
            </div>
        </div>
    @endif

    <div class="modal fade" id="adjustModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <form method="POST" id="adjust-form" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Post Adjusting Journal</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <p id="adjust-desc" class="text-muted small"></p>
                    <div class="form-group">
                        <label>Counter Account</label>
                        <select name="counter_account_id" class="form-control" required>
                            <option value="">— select —</option>
                            @foreach ($counterAccounts as $account)
                                <option value="{{ $account->id }}">{{ $account->code }} — {{ $account->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Memo</label>
                        <input type="text" name="memo" class="form-control" maxlength="255">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Post Journal</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(function() {
            const statusUrl = @json(route('bank-reconciliation.status', $bankReconciliation));
            const isProcessing = @json($bankReconciliation->status === \App\Models\Bank\BankReconciliation::STATUS_PROCESSING);
            const adjustBase = @json(url('/bank-reconciliation/'.$bankReconciliation->id.'/lines'));

            function updateMatchButton() {
                const bankCount = $('.bank-line-check:checked').length;
                const bookCount = $('.book-line-check:checked').length;
                $('#match-selected-btn').prop('disabled', bankCount === 0 || bookCount === 0);
            }

            function applyFilters() {
                const q = ($('#line-filter').val() || '').toLowerCase();
                const status = $('#status-filter').val() || '';
                $('.line-row').each(function() {
                    const hay = $(this).data('search') || '';
                    const st = $(this).data('status') || '';
                    const matchText = !q || hay.indexOf(q) !== -1;
                    const matchStatus = !status || st === status;
                    $(this).toggle(matchText && matchStatus);
                });
            }

            $(document).on('change', '.bank-line-check, .book-line-check', function() {
                updateMatchButton();
                if ($(this).hasClass('bank-line-check') && this.checked) {
                    const suggestId = $(this).data('suggest');
                    if (suggestId) {
                        const $book = $('.book-line-check[value="' + suggestId + '"]');
                        if ($book.length) {
                            $book.prop('checked', true);
                            $book.closest('tr').addClass('table-success');
                            updateMatchButton();
                        }
                    }
                }
            });

            $('#line-filter, #status-filter').on('input change', applyFilters);

            $('.adjust-btn').on('click', function() {
                const lineId = $(this).data('line-id');
                $('#adjust-desc').text($(this).data('desc') || '');
                $('#adjust-form').attr('action', adjustBase + '/' + lineId + '/adjust');
                $('#adjustModal').modal('show');
            });

            function pollStatus() {
                $.get(statusUrl).done(function(data) {
                    $('#bank-net').text(Number(data.bank_net).toLocaleString(undefined, {minimumFractionDigits: 2}));
                    $('#book-net').text(Number(data.book_net).toLocaleString(undefined, {minimumFractionDigits: 2}));
                    $('#difference').text(Number(data.difference).toLocaleString(undefined, {minimumFractionDigits: 2}));
                    $('#statement-closing').text(Number(data.statement_closing).toLocaleString(undefined, {minimumFractionDigits: 2}));
                    $('#deposits-in-transit').text(Number(data.deposits_in_transit).toLocaleString(undefined, {minimumFractionDigits: 2}));
                    $('#outstanding-checks').text(Number(data.outstanding_checks).toLocaleString(undefined, {minimumFractionDigits: 2}));
                    $('#adjusted-statement').text(Number(data.adjusted_statement_balance).toLocaleString(undefined, {minimumFractionDigits: 2}));
                    $('#book-closing').text(Number(data.book_closing).toLocaleString(undefined, {minimumFractionDigits: 2}));
                    $('#recon-difference').text(Number(data.reconciliation_difference).toLocaleString(undefined, {minimumFractionDigits: 2}))
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
