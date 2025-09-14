@extends('layouts.app')

@section('title', 'Tax Reports')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Tax Reports</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('tax.index') }}">Tax Compliance</a></li>
                    <li class="breadcrumb-item active">Reports</li>
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
                            <i class="fas fa-file-alt mr-2"></i>
                            Tax Reports Management
                        </h3>
                        <div class="card-tools">
                            <a href="{{ route('tax.reports.create') }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus mr-1"></i>
                                Generate Report
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Report Name</th>
                                        <th>Report Type</th>
                                        <th>Tax Period</th>
                                        <th>Status</th>
                                        <th>Due Date</th>
                                        <th>Submission Date</th>
                                        <th>Created By</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($reports as $report)
                                    <tr>
                                        <td>
                                            <strong>{{ $report->report_name }}</strong>
                                            @if($report->is_overdue)
                                                <span class="badge badge-danger ml-1">Overdue</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge badge-info">{{ $report->report_type_name }}</span>
                                        </td>
                                        <td>{{ $report->taxPeriod->period_name }}</td>
                                        <td>
                                            <span class="badge badge-{{ $report->status_color }}">
                                                {{ ucfirst($report->status) }}
                                            </span>
                                        </td>
                                        <td>
                                            {{ $report->due_date ? $report->due_date->format('d M Y') : '-' }}
                                            @if($report->is_overdue)
                                                <br><small class="text-danger">{{ $report->days_overdue }} days overdue</small>
                                            @endif
                                        </td>
                                        <td>
                                            @if($report->submission_date)
                                                {{ $report->submission_date->format('d M Y') }}
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>{{ $report->createdBy->name ?? 'System' }}</td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="{{ route('tax.reports.show', $report->id) }}" class="btn btn-info btn-sm">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                @if($report->canBeSubmitted())
                                                    <button class="btn btn-primary btn-sm submit-report-btn" data-id="{{ $report->id }}" data-name="{{ $report->report_name }}">
                                                        <i class="fas fa-paper-plane"></i>
                                                    </button>
                                                @endif
                                                @if($report->canBeApproved())
                                                    <button class="btn btn-success btn-sm approve-report-btn" data-id="{{ $report->id }}" data-name="{{ $report->report_name }}">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                @endif
                                                @if($report->canBeRejected())
                                                    <button class="btn btn-warning btn-sm reject-report-btn" data-id="{{ $report->id }}" data-name="{{ $report->report_name }}">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">
                                            <i class="fas fa-file-alt fa-2x mb-3"></i>
                                            <br>
                                            No tax reports found. Generate your first tax report to get started.
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        @if($reports->hasPages())
                        <div class="d-flex justify-content-center">
                            {{ $reports->links() }}
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Submit Report Modal -->
<div class="modal fade" id="submitReportModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Submit Tax Report</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to submit the tax report <strong id="submit-report-name"></strong>?</p>
                <p class="text-info">
                    <i class="fas fa-info-circle"></i>
                    Once submitted, the report will be marked as submitted and cannot be edited.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirm-submit-report">Submit Report</button>
            </div>
        </div>
    </div>
</div>

<!-- Approve Report Modal -->
<div class="modal fade" id="approveReportModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Approve Tax Report</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to approve the tax report <strong id="approve-report-name"></strong>?</p>
                <p class="text-success">
                    <i class="fas fa-check-circle"></i>
                    Once approved, the report will be marked as approved and ready for submission to tax office.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="confirm-approve-report">Approve Report</button>
            </div>
        </div>
    </div>
</div>

<!-- Reject Report Modal -->
<div class="modal fade" id="rejectReportModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reject Tax Report</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to reject the tax report <strong id="reject-report-name"></strong>?</p>
                <div class="form-group">
                    <label for="reject-reason">Rejection Reason</label>
                    <textarea class="form-control" id="reject-reason" rows="3" placeholder="Please provide a reason for rejection..."></textarea>
                </div>
                <p class="text-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    Once rejected, the report will be marked as rejected and can be edited again.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" id="confirm-reject-report">Reject Report</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    var reportIdToAction = null;

    // Submit report button
    $('.submit-report-btn').click(function() {
        reportIdToAction = $(this).data('id');
        var reportName = $(this).data('name');
        $('#submit-report-name').text(reportName);
        $('#submitReportModal').modal('show');
    });

    // Approve report button
    $('.approve-report-btn').click(function() {
        reportIdToAction = $(this).data('id');
        var reportName = $(this).data('name');
        $('#approve-report-name').text(reportName);
        $('#approveReportModal').modal('show');
    });

    // Reject report button
    $('.reject-report-btn').click(function() {
        reportIdToAction = $(this).data('id');
        var reportName = $(this).data('name');
        $('#reject-report-name').text(reportName);
        $('#rejectReportModal').modal('show');
    });

    // Confirm submit report
    $('#confirm-submit-report').click(function() {
        if (reportIdToAction) {
            $.ajax({
                url: '/tax/reports/' + reportIdToAction + '/submit',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    $('#submitReportModal').modal('hide');
                    location.reload();
                },
                error: function(xhr) {
                    toastr.error('Error submitting report: ' + xhr.responseJSON.message);
                }
            });
        }
    });

    // Confirm approve report
    $('#confirm-approve-report').click(function() {
        if (reportIdToAction) {
            $.ajax({
                url: '/tax/reports/' + reportIdToAction + '/approve',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    $('#approveReportModal').modal('hide');
                    location.reload();
                },
                error: function(xhr) {
                    toastr.error('Error approving report: ' + xhr.responseJSON.message);
                }
            });
        }
    });

    // Confirm reject report
    $('#confirm-reject-report').click(function() {
        if (reportIdToAction) {
            var rejectReason = $('#reject-reason').val();
            if (!rejectReason.trim()) {
                toastr.error('Please provide a reason for rejection.');
                return;
            }

            $.ajax({
                url: '/tax/reports/' + reportIdToAction + '/reject',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    notes: rejectReason
                },
                success: function(response) {
                    $('#rejectReportModal').modal('hide');
                    location.reload();
                },
                error: function(xhr) {
                    toastr.error('Error rejecting report: ' + xhr.responseJSON.message);
                }
            });
        }
    });
});
</script>
@endpush