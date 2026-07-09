@extends('layouts.main')

@section('title_page')
    Rekening Koran
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">Rekening Koran</li>
@endsection

@section('content')
    @php
        $prevYear = $year - 1;
        $nextYear = $year + 1;
    @endphp

    <div class="card card-outline card-primary">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h4 class="card-title mb-0">Rekening Koran — {{ $year }}</h4>
                <small class="text-muted">Bank account × month matrix</small>
            </div>
            <div class="d-flex flex-wrap align-items-center" style="gap: 0.35rem;">
                <a href="{{ route('bank-reconciliation.index', ['year' => $prevYear]) }}" class="btn btn-sm btn-outline-secondary">&laquo; {{ $prevYear }}</a>
                <span class="badge badge-light px-3 py-2">{{ $year }}</span>
                <a href="{{ route('bank-reconciliation.index', ['year' => $nextYear]) }}" class="btn btn-sm btn-outline-secondary">{{ $nextYear }} &raquo;</a>
                <a href="{{ route('bank-reconciliation.sessions') }}" class="btn btn-sm btn-secondary">All Sessions</a>
                @can('bank_accounts.view')
                    <a href="{{ route('bank-accounts.index') }}" class="btn btn-sm btn-secondary">Bank Accounts</a>
                @endcan
            </div>
        </div>
        <div class="card-body p-0 table-responsive">
            @if (session('success'))
                <script>toastr.success(@json(session('success')));</script>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger m-3">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($accounts->isEmpty())
                <p class="text-muted p-3 mb-0">No active bank accounts. <a href="{{ route('bank-accounts.index') }}">Set up bank accounts</a> first.</p>
            @else
                <table class="table table-bordered table-sm mb-0 koran-grid">
                    <thead class="thead-light">
                        <tr>
                            <th style="min-width: 200px; position: sticky; left: 0; background: #f4f6f9; z-index: 2;">Bank Account</th>
                            @foreach ($months as $month)
                                <th class="text-center" style="min-width: 72px;">{{ $month['label'] }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($accounts as $account)
                            <tr>
                                <td style="position: sticky; left: 0; background: #fff; z-index: 1;">
                                    <strong>{{ $account->name }}</strong><br>
                                    <small class="text-muted">{{ $account->account_number }}</small>
                                </td>
                                @foreach ($months as $month)
                                    @include('bank-reconciliation.partials.koran-cell', [
                                        'cell' => $matrix[$account->id][$month['num']],
                                        'year' => $year,
                                    ])
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
        <div class="card-footer text-muted small">
            Click a cell to upload PDF, create a manual session, or open the workbench.
            <span class="koran-legend-item ml-2"><span class="koran-status-box koran-status-box--missing koran-status-box--legend"><i class="fas fa-times"></i></span> Empty</span>
            <span class="koran-legend-item ml-2"><span class="koran-status-box koran-status-box--present koran-status-box--legend"><i class="fas fa-check"></i></span> Session</span>
            <span class="koran-legend-item ml-2"><span class="koran-status-overlay koran-status-overlay--teal koran-status-overlay--legend"><i class="fas fa-list"></i></span> Processing</span>
            <span class="koran-legend-item ml-2"><span class="koran-status-overlay koran-status-overlay--blue koran-status-overlay--legend"><i class="fas fa-balance-scale"></i></span> In review</span>
            <span class="koran-legend-item ml-2"><span class="koran-status-overlay koran-status-overlay--red koran-status-overlay--legend"><i class="fas fa-file-pdf"></i></span> PDF</span>
            <span class="koran-legend-item ml-2"><span class="koran-status-overlay koran-status-overlay--green koran-status-overlay--legend"><i class="fas fa-check-double"></i></span> Completed</span>
        </div>
    </div>

    @include('bank-reconciliation.partials.koran-cell-modal')
@endsection

@push('scripts')
    <script>
        $(function() {
            const cellDataUrl = @json(route('bank-reconciliation.koran.cell'));
            const showBaseUrl = @json(url('bank-reconciliation'));
            const reportBaseUrl = @json(url('bank-reconciliation'));
            const parseBaseUrl = @json(url('bank-reconciliation'));

            $('.koran-cell').on('click', function() {
                const $cell = $(this);
                const bankAccountId = $cell.data('bank-account-id');
                const month = $cell.data('month');
                const year = $cell.data('year');
                const bankName = $cell.closest('tr').find('strong').text().trim();
                const monthLabel = @json(collect($months)->pluck('label', 'num'));

                $('#koran-modal-title').text(bankName);
                $('#koran-modal-subtitle').text(monthLabel[month] + ' ' + year);

                $.get(cellDataUrl, {
                    bank_account_id: bankAccountId,
                    year: year,
                    month: month,
                }).done(function(cell) {
                    if (cell.status === 'empty') {
                        $('#koran-modal-empty').show();
                        $('#koran-modal-session').hide();
                        const periode = cell.periode.substring(0, 7);
                        $('#koran-form-bank-account-id, #koran-manual-bank-account-id').val(bankAccountId);
                        $('#koran-form-periode, #koran-manual-periode').val(periode);
                    } else {
                        $('#koran-modal-empty').hide();
                        $('#koran-modal-session').show();
                        $('#koran-session-status')
                            .text(cell.label)
                            .removeClass('badge-secondary badge-info badge-warning badge-success badge-danger')
                            .addClass('badge-' + cell.badge_class);
                        $('#koran-open-workbench').attr('href', showBaseUrl + '/' + cell.reconciliation_id);
                        if (cell.is_completed) {
                            $('#koran-open-report').show().attr('href', reportBaseUrl + '/' + cell.reconciliation_id + '/report');
                        } else {
                            $('#koran-open-report').hide();
                        }
                        if (cell.is_ai) {
                            $('#koran-reparse-form').show().attr('action', parseBaseUrl + '/' + cell.reconciliation_id + '/parse');
                        } else {
                            $('#koran-reparse-form').hide();
                        }
                        if (cell.has_pdf && cell.statement_pdf_url) {
                            $('#koran-open-pdf').show().attr('href', cell.statement_pdf_url);
                        } else {
                            $('#koran-open-pdf').hide();
                        }
                    }
                    $('#koranCellModal').modal('show');
                }).fail(function() {
                    toastr.error('Failed to load cell data.');
                });
            });
        });
    </script>
@endpush

@push('styles')
    <style>
        .koran-grid td.koran-cell:hover { background-color: #f0f4ff; }
        .koran-grid td.koran-cell:hover .koran-status-box { box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12); }

        .koran-status-cell {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 0.15rem 0;
        }

        .koran-status-box {
            position: relative;
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 0.45rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid transparent;
            transition: box-shadow 0.15s ease;
        }

        .koran-status-box--present {
            background: #e8f5e9;
            border-color: #a5d6a7;
            color: #2e7d32;
        }

        .koran-status-box--missing {
            background: #ffebee;
            border-color: #ef9a9a;
            color: #c62828;
        }

        .koran-status-box-icon {
            font-size: 1.1rem;
            line-height: 1;
        }

        .koran-status-overlay {
            position: absolute;
            right: -0.35rem;
            bottom: -0.35rem;
            width: 1.15rem;
            height: 1.15rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 0.55rem;
            line-height: 1;
            border: 2px solid #fff;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.15);
        }

        .koran-status-overlay-link:hover {
            color: #fff;
            transform: scale(1.08);
        }

        .koran-status-overlay--blue { background: #1976d2; }
        .koran-status-overlay--teal { background: #00897b; }
        .koran-status-overlay--red { background: #d32f2f; }
        .koran-status-overlay--green { background: #388e3c; }

        .koran-status-box--legend,
        .koran-status-overlay--legend {
            position: static;
            display: inline-flex;
            vertical-align: middle;
            margin-right: 0.2rem;
            box-shadow: none;
        }

        .koran-status-box--legend {
            width: 1.35rem;
            height: 1.35rem;
            border-radius: 0.3rem;
        }

        .koran-status-box--legend .koran-status-box-icon {
            font-size: 0.65rem;
        }

        .koran-status-overlay--legend {
            width: 1rem;
            height: 1rem;
            font-size: 0.5rem;
            border-width: 1px;
        }

        .koran-legend-item {
            display: inline-flex;
            align-items: center;
            white-space: nowrap;
        }
    </style>
@endpush
