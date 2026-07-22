@extends('layouts.main')

@section('title_page')
    Journal {{ $journal->journal_no }}
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('journals.index') }}">Journals</a></li>
    <li class="breadcrumb-item active">{{ $journal->journal_no }}</li>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="card card-outline card-primary mb-3">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-start">
                    <div class="flex-grow-1 pr-3 mb-2 mb-md-0">
                        <h3 class="mb-1">
                            <i class="fas fa-book mr-1"></i>
                            Journal {{ $journal->journal_no }}
                        </h3>
                        <div class="text-muted small d-flex flex-wrap">
                            <span class="mr-3 mb-1">Date: {{ $journal->date->format('d M Y') }}</span>
                            @if ($journal->postedBy)
                                <span class="mr-3 mb-1">Posted by: {{ $journal->postedBy->name }}</span>
                            @endif
                            @if ($journal->posted_at)
                                <span class="mb-1">Posted at: {{ $journal->posted_at->format('d M Y H:i') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="d-flex flex-wrap align-items-center flex-shrink-0">
                        <a href="{{ route('journals.index') }}" class="btn btn-sm btn-secondary mr-1 mb-1">
                            <i class="fas fa-arrow-left mr-1"></i>Back to Journals
                        </a>
                        @can('journals.reverse')
                            <button type="button" class="btn btn-sm btn-danger reverse-button mb-1"
                                data-url="{{ route('journals.reverse', $journal) }}">
                                <i class="fas fa-undo mr-1"></i>Reverse
                            </button>
                        @endcan
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Description:</strong>
                            <p class="mb-2">{{ $journal->description ?: '—' }}</p>
                        </div>
                        <div class="col-md-6">
                            <strong>Source Document:</strong>
                            <p class="mb-2">
                                @if ($sourceUrl)
                                    <a href="{{ $sourceUrl }}">{{ $sourceLabel }}</a>
                                @else
                                    {{ $sourceLabel }}
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-sm-6">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3>{{ number_format($totalDebit, 2) }}</h3>
                            <p>Total Debit</p>
                        </div>
                        <div class="icon"><i class="fas fa-arrow-down"></i></div>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3>{{ number_format($totalCredit, 2) }}</h3>
                            <p>Total Credit</p>
                        </div>
                        <div class="icon"><i class="fas fa-arrow-up"></i></div>
                    </div>
                </div>
            </div>

            <div class="card card-outline card-secondary">
                <div class="card-header">
                    <h3 class="card-title">Journal Lines</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Account</th>
                                    <th>Memo</th>
                                    <th>Project</th>
                                    <th>Department</th>
                                    @if ($hasForeignCurrency)
                                        <th>Currency / Rate</th>
                                    @endif
                                    <th class="text-right">Debit</th>
                                    <th class="text-right">Credit</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($journal->lines as $line)
                                    <tr>
                                        <td>
                                            @if ($line->account)
                                                {{ $line->account->code }} — {{ $line->account->name }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td>{{ $line->memo ?: '—' }}</td>
                                        <td>
                                            @if ($line->project)
                                                {{ $line->project->code }} — {{ $line->project->name }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td>
                                            @if ($line->dept)
                                                {{ $line->dept->code }} — {{ $line->dept->name }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                        @if ($hasForeignCurrency)
                                            <td>
                                                @if ($line->currency)
                                                    {{ $line->currency->code }}
                                                    @if ($line->exchange_rate)
                                                        @ {{ number_format($line->exchange_rate, 6) }}
                                                    @endif
                                                @else
                                                    —
                                                @endif
                                            </td>
                                        @endif
                                        <td class="text-right">
                                            {{ $line->debit > 0 ? number_format($line->debit, 2) : '' }}
                                        </td>
                                        <td class="text-right">
                                            {{ $line->credit > 0 ? number_format($line->credit, 2) : '' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ $hasForeignCurrency ? 7 : 6 }}" class="text-center text-muted py-4">
                                            No journal lines.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                            @if ($journal->lines->isNotEmpty())
                                <tfoot>
                                    <tr class="font-weight-bold">
                                        <td colspan="{{ $hasForeignCurrency ? 5 : 4 }}">Total</td>
                                        <td class="text-right">{{ number_format($totalDebit, 2) }}</td>
                                        <td class="text-right">{{ number_format($totalCredit, 2) }}</td>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        $(function() {
            $('.reverse-button').on('click', function() {
                var url = $(this).data('url');
                Swal.fire({
                    title: 'Reverse Journal?',
                    text: 'This will post a full reversal.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, reverse',
                    cancelButtonText: 'Cancel'
                }).then((res) => {
                    if (res.isConfirmed) {
                        var form = $('<form method="POST" action="' + url + '">@csrf</form>');
                        $('body').append(form);
                        form.submit();
                    }
                });
            });
        });
    </script>
@endpush
