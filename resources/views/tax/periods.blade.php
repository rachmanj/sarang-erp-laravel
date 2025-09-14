@extends('layouts.app')

@section('title', 'Tax Periods')

@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Tax Periods</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('tax.index') }}">Tax Compliance</a></li>
                        <li class="breadcrumb-item active">Periods</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-calendar-alt mr-2"></i>
                                Tax Periods Management
                            </h3>
                            <div class="card-tools">
                                <a href="{{ route('tax.periods.create') }}" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus mr-1"></i>
                                    New Period
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Period</th>
                                            <th>Type</th>
                                            <th>Start Date</th>
                                            <th>End Date</th>
                                            <th>Status</th>
                                            <th>Closing Date</th>
                                            <th>Closed By</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($periods as $period)
                                            <tr>
                                                <td>
                                                    <strong>{{ $period->period_name }}</strong>
                                                    @if ($period->is_current)
                                                        <span class="badge badge-info ml-1">Current</span>
                                                    @endif
                                                </td>
                                                <td>{{ ucfirst($period->period_type) }}</td>
                                                <td>{{ $period->start_date->format('d M Y') }}</td>
                                                <td>{{ $period->end_date->format('d M Y') }}</td>
                                                <td>
                                                    <span
                                                        class="badge badge-{{ $period->status === 'open' ? 'success' : ($period->status === 'closed' ? 'secondary' : 'warning') }}">
                                                        {{ ucfirst($period->status) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @if ($period->closing_date)
                                                        {{ $period->closing_date->format('d M Y') }}
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($period->closedBy)
                                                        {{ $period->closedBy->name }}
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="btn-group">
                                                        <a href="{{ route('tax.index') }}?period={{ $period->id }}"
                                                            class="btn btn-info btn-sm">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        @if ($period->canBeClosed())
                                                            <button class="btn btn-warning btn-sm close-period-btn"
                                                                data-id="{{ $period->id }}"
                                                                data-name="{{ $period->period_name }}">
                                                                <i class="fas fa-lock"></i>
                                                            </button>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="8" class="text-center text-muted">
                                                    <i class="fas fa-calendar-alt fa-2x mb-3"></i>
                                                    <br>
                                                    No tax periods found. Create your first tax period to get started.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            @if ($periods->hasPages())
                                <div class="d-flex justify-content-center">
                                    {{ $periods->links() }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Close Period Modal -->
    <div class="modal fade" id="closePeriodModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Close Tax Period</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to close the tax period <strong id="period-name"></strong>?</p>
                    <p class="text-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Warning:</strong> Once closed, this period cannot be reopened and no new transactions can be
                        added to it.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-warning" id="confirm-close-period">Close Period</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            var periodIdToClose = null;

            // Close period button
            $('.close-period-btn').click(function() {
                periodIdToClose = $(this).data('id');
                var periodName = $(this).data('name');
                $('#period-name').text(periodName);
                $('#closePeriodModal').modal('show');
            });

            // Confirm close period
            $('#confirm-close-period').click(function() {
                if (periodIdToClose) {
                    $.ajax({
                        url: '/tax/periods/' + periodIdToClose + '/close',
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            $('#closePeriodModal').modal('hide');
                            location.reload();
                        },
                        error: function(xhr) {
                            toastr.error('Error closing period: ' + xhr.responseJSON.message);
                        }
                    });
                }
            });
        });
    </script>
@endpush
