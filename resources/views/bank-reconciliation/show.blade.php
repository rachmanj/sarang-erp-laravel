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
        $statement = $bankReconciliation->statement;
        $bankAccount = $bankReconciliation->bankAccount;
        $unmatchedCount = $statement->lines->where('match_status', 'unmatched')->count();
        $matchedCount = $statement->lines->whereIn('match_status', ['matched', 'adjustment'])->count();
        $computedClosing = app(\App\Services\Bank\BankReconciliationService::class)->calculateReconciledClosingBalance($bankReconciliation);
    @endphp

    <div class="row">
        <div class="col-12">
            @if (session('success'))
                <script>
                    toastr.success(@json(session('success')));
                </script>
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

            <div class="card card-outline card-primary">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                    <div>
                        <h4 class="card-title mb-0">{{ $bankAccount->name }} ({{ $bankAccount->account_number }})</h4>
                        <small class="text-muted">
                            Period: {{ $statement->period_start->format('d M Y') }} - {{ $statement->period_end->format('d M Y') }}
                            | COA: {{ $bankAccount->account?->code }} - {{ $bankAccount->account?->name }}
                        </small>
                    </div>
                    <div class="d-flex flex-wrap" style="gap: 0.35rem;">
                        @if ($bankReconciliation->status === 'open')
                            @can('bank_reconciliation.reconcile')
                                <form method="POST" action="{{ route('bank-reconciliation.auto-match', $bankReconciliation) }}">
                                    @csrf
                                    <button class="btn btn-sm btn-info">Auto Match</button>
                                </form>
                                <form method="POST" action="{{ route('bank-reconciliation.ai-match', $bankReconciliation) }}">
                                    @csrf
                                    <button class="btn btn-sm btn-purple" style="background:#6f42c1;color:#fff;">AI Suggest</button>
                                </form>
                            @endcan
                            @can('bank_reconciliation.finalize')
                                <form method="POST" action="{{ route('bank-reconciliation.finalize', $bankReconciliation) }}">
                                    @csrf
                                    <button class="btn btn-sm btn-success" @disabled($unmatchedCount > 0)>Finalize</button>
                                </form>
                            @endcan
                        @endif
                        <a href="{{ route('bank-reconciliation.index') }}" class="btn btn-sm btn-secondary">Back</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="info-box bg-light">
                                <span class="info-box-icon"><i class="fas fa-door-open"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Opening</span>
                                    <span class="info-box-number">{{ number_format((float) $statement->opening_balance, 2) }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box bg-light">
                                <span class="info-box-icon"><i class="fas fa-door-closed"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Statement Closing</span>
                                    <span class="info-box-number">{{ number_format((float) $statement->closing_balance, 2) }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box bg-light">
                                <span class="info-box-icon"><i class="fas fa-book"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Book Balance</span>
                                    <span class="info-box-number">{{ number_format($computedClosing, 2) }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box bg-light">
                                <span class="info-box-icon"><i class="fas fa-check-double"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Matched / Unmatched</span>
                                    <span class="info-box-number">{{ $matchedCount }} / {{ $unmatchedCount }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Bank Statement Lines</h3>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-sm table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Description</th>
                                <th class="text-right">Debit</th>
                                <th class="text-right">Credit</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($statement->lines as $line)
                                <tr class="{{ $line->match_status === 'unmatched' ? 'table-warning' : '' }}">
                                    <td>{{ $line->posting_date->format('d/m/Y') }}</td>
                                    <td>
                                        <div>{{ \Illuminate\Support\Str::limit($line->description, 60) }}</div>
                                        @if ($line->reference_no)
                                            <small class="text-muted">{{ $line->reference_no }}</small>
                                        @endif
                                    </td>
                                    <td class="text-right">{{ $line->direction === 'debit' ? number_format((float) $line->amount, 2) : '-' }}</td>
                                    <td class="text-right">{{ $line->direction === 'credit' ? number_format((float) $line->amount, 2) : '-' }}</td>
                                    <td><span class="badge badge-secondary">{{ strtoupper($line->match_status) }}</span></td>
                                    <td class="text-nowrap">
                                        @if ($bankReconciliation->status === 'open' && $line->match_status === 'unmatched')
                                            @can('bank_reconciliation.reconcile')
                                                <button type="button" class="btn btn-xs btn-primary match-btn"
                                                    data-line-id="{{ $line->id }}"
                                                    data-description="{{ $line->description }}"
                                                    data-suggested-account="{{ \App\Services\Bank\BankReconciliationSupport::suggestCounterAccountCode($line->description ?? '') }}">
                                                    Match
                                                </button>
                                                <form method="POST" action="{{ route('bank-reconciliation.ignore-line', $bankReconciliation) }}" class="d-inline">
                                                    @csrf
                                                    <input type="hidden" name="bank_statement_line_id" value="{{ $line->id }}">
                                                    <button class="btn btn-xs btn-secondary">Ignore</button>
                                                </form>
                                            @endcan
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No statement lines imported.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">Unmatched Book Lines</h3>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="refresh-book-lines-btn" title="Refresh book lines">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-sm table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Description</th>
                                <th class="text-right">Debit</th>
                                <th class="text-right">Credit</th>
                                <th>Source</th>
                            </tr>
                        </thead>
                        <tbody id="book-lines-tbody">
                            @forelse ($bookLines as $bookLine)
                                <tr>
                                    <td>{{ \Illuminate\Support\Carbon::parse($bookLine->date)->format('d/m/Y') }}</td>
                                    <td>{{ \Illuminate\Support\Str::limit(trim(($bookLine->description ?? '') . ' ' . ($bookLine->memo ?? '')), 70) }}</td>
                                    <td class="text-right">{{ number_format((float) $bookLine->debit, 2) }}</td>
                                    <td class="text-right">{{ number_format((float) $bookLine->credit, 2) }}</td>
                                    <td><small>{{ $bookLine->source_type }}</small></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">No unmatched book lines.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @can('bank_reconciliation.reconcile')
        <div class="modal fade" id="matchModal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Match Statement Line</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <p id="match-line-description" class="text-muted"></p>
                        <form method="POST" action="{{ route('bank-reconciliation.manual-match', $bankReconciliation) }}" id="manual-match-form">
                            @csrf
                            <input type="hidden" name="bank_statement_line_id" id="manual-bank-line-id">
                            <div class="form-group">
                                <label>Book Journal Line</label>
                                <select name="journal_line_id" id="manual-journal-line-id" class="form-control" required>
                                    <option value="">Select book line</option>
                                    @foreach ($bookLines as $bookLine)
                                        <option value="{{ $bookLine->journal_line_id }}">
                                            {{ \Illuminate\Support\Carbon::parse($bookLine->date)->format('d/m/Y') }}
                                            | D {{ number_format((float) $bookLine->debit, 2) }}
                                            | C {{ number_format((float) $bookLine->credit, 2) }}
                                            | {{ \Illuminate\Support\Str::limit(trim(($bookLine->description ?? '') . ' ' . ($bookLine->memo ?? '')), 40) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <button class="btn btn-primary">Save Manual Match</button>
                        </form>

                        <hr>

                        <form method="POST" action="{{ route('bank-reconciliation.adjustment', $bankReconciliation) }}" id="adjustment-form">
                            @csrf
                            <input type="hidden" name="bank_statement_line_id" id="adjustment-bank-line-id">
                            <div class="form-group">
                                <label>Counter Account (for bank-only item)</label>
                                <select name="counter_account_id" id="adjustment-counter-account-id" class="form-control" required>
                                    <option value="">Select counter account</option>
                                    @foreach ($expenseAccounts as $account)
                                        <option value="{{ $account->id }}" data-code="{{ $account->code }}">
                                            {{ $account->code }} - {{ $account->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Memo</label>
                                <input type="text" name="memo" class="form-control">
                            </div>
                            <button class="btn btn-warning">Post Adjustment &amp; Match</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endcan
@endsection

@push('scripts')
    <script>
        $(function() {
            const bookLinesUrl = @json(route('bank-reconciliation.book-data', $bankReconciliation));

            function truncateText(text, maxLength) {
                if (!text || text.length <= maxLength) {
                    return text || '';
                }

                return text.substring(0, maxLength) + '...';
            }

            function buildBookLineRow(row) {
                return $('<tr>').append(
                    $('<td>').text(row.date_display),
                    $('<td>').text(truncateText(row.description, 70)),
                    $('<td>').addClass('text-right').text(row.debit),
                    $('<td>').addClass('text-right').text(row.credit),
                    $('<td>').append($('<small>').text(row.source_type))
                );
            }

            function refreshManualMatchOptions(rows) {
                const $select = $('#manual-journal-line-id');
                const currentValue = $select.val();

                $select.find('option:not(:first)').remove();

                rows.forEach(function(row) {
                    const label = row.date_display
                        + ' | D ' + row.debit
                        + ' | C ' + row.credit
                        + ' | ' + truncateText(row.description, 40);

                    $select.append($('<option>', {
                        value: row.journal_line_id,
                        text: label,
                    }));
                });

                if (currentValue) {
                    $select.val(currentValue);
                }
            }

            function refreshBookLines() {
                const $btn = $('#refresh-book-lines-btn');
                const $tbody = $('#book-lines-tbody');

                $btn.prop('disabled', true);
                $btn.find('i').addClass('fa-spin');

                $.get(bookLinesUrl)
                    .done(function(response) {
                        const rows = response.data || [];

                        $tbody.empty();

                        if (rows.length === 0) {
                            $tbody.append(
                                $('<tr>').append(
                                    $('<td>').attr('colspan', 5).addClass('text-center text-muted').text('No unmatched book lines.')
                                )
                            );
                        } else {
                            rows.forEach(function(row) {
                                $tbody.append(buildBookLineRow(row));
                            });
                        }

                        refreshManualMatchOptions(rows);
                        toastr.success('Book lines refreshed.');
                    })
                    .fail(function() {
                        toastr.error('Failed to refresh book lines.');
                    })
                    .always(function() {
                        $btn.prop('disabled', false);
                        $btn.find('i').removeClass('fa-spin');
                    });
            }

            $('#refresh-book-lines-btn').on('click', refreshBookLines);

            $('.match-btn').on('click', function() {
                const lineId = $(this).data('line-id');
                const description = $(this).data('description') || '';
                const suggestedCode = $(this).data('suggested-account');

                $('#manual-bank-line-id').val(lineId);
                $('#adjustment-bank-line-id').val(lineId);
                $('#match-line-description').text(description);
                $('#manual-journal-line-id').val('');

                if (suggestedCode) {
                    $('#adjustment-counter-account-id option').each(function() {
                        if ($(this).data('code') === suggestedCode) {
                            $('#adjustment-counter-account-id').val($(this).val());
                        }
                    });
                }

                $('#matchModal').modal('show');
            });
        });
    </script>
@endpush
