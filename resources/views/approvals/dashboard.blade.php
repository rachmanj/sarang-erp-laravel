@extends('layouts.main')

@section('title_page')
    Approval Dashboard
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">Approval Dashboard</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-clipboard-check mr-2"></i>
                        Approval Dashboard
                    </h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Statistics Cards -->
        <div class="col-md-3">
            <div class="info-box">
                <span class="info-box-icon bg-warning">
                    <i class="fas fa-clock"></i>
                </span>
                <div class="info-box-content">
                    <span class="info-box-text">Pending</span>
                    <span class="info-box-number">{{ $approvalStats['pending'] }}</span>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="info-box">
                <span class="info-box-icon bg-success">
                    <i class="fas fa-check"></i>
                </span>
                <div class="info-box-content">
                    <span class="info-box-text">Approved</span>
                    <span class="info-box-number">{{ $approvalStats['approved'] }}</span>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="info-box">
                <span class="info-box-icon bg-danger">
                    <i class="fas fa-times"></i>
                </span>
                <div class="info-box-content">
                    <span class="info-box-text">Rejected</span>
                    <span class="info-box-number">{{ $approvalStats['rejected'] }}</span>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="info-box">
                <span class="info-box-icon bg-info">
                    <i class="fas fa-list"></i>
                </span>
                <div class="info-box-content">
                    <span class="info-box-text">Total</span>
                    <span class="info-box-number">{{ $approvalStats['total'] }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Pending Approvals -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-list-alt mr-2"></i>
                        Pending Approvals
                    </h3>
                </div>
                <div class="card-body">
                    @if ($pendingApprovals->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th width="5%"></th>
                                        <th>Document</th>
                                        <th>Vendor</th>
                                        <th>Amount</th>
                                        <th>Priority</th>
                                        <th>Level</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($pendingApprovals as $approval)
                                        <tr class="approval-row" data-approval-id="{{ $approval['id'] }}">
                                            <td>
                                                <button class="btn btn-sm btn-outline-secondary toggle-details"
                                                    data-target="details-{{ $approval['id'] }}">
                                                    <i class="fas fa-chevron-down"></i>
                                                </button>
                                            </td>
                                            <td>
                                                <strong>{{ $approval['document_number'] }}</strong>
                                                <br>
                                                <small class="text-muted">{{ $approval['document_type'] }}</small>
                                            </td>
                                            <td>
                                                <strong>{{ $approval['vendor'] ?? 'N/A' }}</strong>
                                                @if (isset($approval['warehouse']))
                                                    <br>
                                                    <small class="text-muted">{{ $approval['warehouse'] }}</small>
                                                @endif
                                            </td>
                                            <td>
                                                <strong>Rp {{ number_format($approval['amount'], 0, ',', '.') }}</strong>
                                            </td>
                                            <td>
                                                @if ($approval['priority'] === 'high')
                                                    <span class="badge badge-danger">High</span>
                                                @elseif($approval['priority'] === 'medium')
                                                    <span class="badge badge-warning">Medium</span>
                                                @else
                                                    <span class="badge badge-success">Low</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span
                                                    class="badge badge-info">{{ ucfirst($approval['approval_level']) }}</span>
                                            </td>
                                            <td>
                                                {{ $approval['created_at']->format('d/m/Y H:i') }}
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-primary"
                                                    onclick="reviewApproval({{ $approval['id'] }})">
                                                    <i class="fas fa-eye"></i> Review
                                                </button>
                                            </td>
                                        </tr>
                                        <tr class="approval-details" id="details-{{ $approval['id'] }}"
                                            style="display: none;">
                                            <td colspan="8">
                                                <div class="card card-outline card-info">
                                                    <div class="card-header">
                                                        <h3 class="card-title">
                                                            <i class="fas fa-info-circle mr-2"></i>
                                                            Purchase Order Details
                                                        </h3>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <h5>Basic Information</h5>
                                                                <table class="table table-sm">
                                                                    <tr>
                                                                        <td><strong>PO Number:</strong></td>
                                                                        <td>{{ $approval['document_number'] }}</td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td><strong>Date:</strong></td>
                                                                        <td>{{ $approval['po_date'] ? \Carbon\Carbon::parse($approval['po_date'])->format('d/m/Y') : 'N/A' }}
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td><strong>Vendor:</strong></td>
                                                                        <td>{{ $approval['vendor'] ?? 'N/A' }}</td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td><strong>Warehouse:</strong></td>
                                                                        <td>{{ $approval['warehouse'] ?? 'N/A' }}</td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td><strong>Order Type:</strong></td>
                                                                        <td>{{ ucfirst($approval['order_type'] ?? 'N/A') }}
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td><strong>Total Amount:</strong></td>
                                                                        <td><strong>Rp
                                                                                {{ number_format($approval['amount'], 0, ',', '.') }}</strong>
                                                                        </td>
                                                                    </tr>
                                                                </table>
                                                            </div>
                                                            <div class="col-md-6">
                                                                @if (!empty($approval['description']))
                                                                    <h5>Description</h5>
                                                                    <p>{{ $approval['description'] }}</p>
                                                                @endif
                                                            </div>
                                                        </div>

                                                        @if ($approval['line_items']->count() > 0)
                                                            <h5>Line Items</h5>
                                                            <div class="table-responsive">
                                                                <table class="table table-sm table-bordered">
                                                                    <thead class="thead-light">
                                                                        <tr>
                                                                            <th>Item Code</th>
                                                                            <th>Item Name</th>
                                                                            <th>Description</th>
                                                                            <th class="text-right">Qty</th>
                                                                            <th class="text-right">Unit Price</th>
                                                                            <th class="text-right">Amount</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        @foreach ($approval['line_items'] as $line)
                                                                            <tr>
                                                                                <td><code>{{ $line['item_code'] }}</code>
                                                                                </td>
                                                                                <td>{{ $line['item_name'] }}</td>
                                                                                <td>{{ $line['description'] }}</td>
                                                                                <td class="text-right">
                                                                                    {{ number_format($line['qty'], 0) }}
                                                                                </td>
                                                                                <td class="text-right">Rp
                                                                                    {{ number_format($line['unit_price'], 0, ',', '.') }}
                                                                                </td>
                                                                                <td class="text-right"><strong>Rp
                                                                                        {{ number_format($line['amount'], 0, ',', '.') }}</strong>
                                                                                </td>
                                                                            </tr>
                                                                        @endforeach
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                            <h4>No Pending Approvals</h4>
                            <p class="text-muted">You're all caught up! No documents require your approval at the
                                moment.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-history mr-2"></i>
                        Recent Activity
                    </h3>
                </div>
                <div class="card-body">
                    @if ($recentActivity->count() > 0)
                        <div class="timeline">
                            @foreach ($recentActivity as $activity)
                                <div class="time-label">
                                    <span class="bg-{{ $activity['action'] === 'approved' ? 'success' : 'danger' }}">
                                        {{ $activity['date']->format('d M') }}
                                    </span>
                                </div>
                                <div>
                                    <i
                                        class="fas fa-{{ $activity['action'] === 'approved' ? 'check' : 'times' }} bg-{{ $activity['action'] === 'approved' ? 'green' : 'red' }}"></i>
                                    <div class="timeline-item">
                                        <span class="time">
                                            <i class="fas fa-clock"></i> {{ $activity['date']->format('H:i') }}
                                        </span>
                                        <h3 class="timeline-header">
                                            {{ ucfirst($activity['action']) }} {{ $activity['document_type'] }}
                                        </h3>
                                        <div class="timeline-body">
                                            {{ $activity['document_number'] }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-history fa-2x text-muted mb-3"></i>
                            <p class="text-muted">No recent activity</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    </div>

    <script>
        // Toggle details functionality
        document.addEventListener('DOMContentLoaded', function() {
            const toggleButtons = document.querySelectorAll('.toggle-details');
            toggleButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    const target = this.getAttribute('data-target');
                    const detailsRow = document.getElementById(target);
                    const icon = this.querySelector('i');

                    if (detailsRow.style.display === 'none' || detailsRow.style.display === '') {
                        detailsRow.style.display = 'table-row';
                        icon.classList.remove('fa-chevron-down');
                        icon.classList.add('fa-chevron-up');
                    } else {
                        detailsRow.style.display = 'none';
                        icon.classList.remove('fa-chevron-up');
                        icon.classList.add('fa-chevron-down');
                    }
                });
            });
        });

        function reviewApproval(approvalId) {
            Swal.fire({
                title: 'Review Approval',
                text: 'What action would you like to take?',
                icon: 'question',
                showCancelButton: true,
                showDenyButton: true,
                confirmButtonText: 'Approve',
                denyButtonText: 'Reject',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#28a745',
                denyButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d'
            }).then((result) => {
                if (result.isConfirmed) {
                    approveDocument(approvalId);
                } else if (result.isDenied) {
                    rejectDocument(approvalId);
                }
            });
        }

        function approveDocument(approvalId) {
            Swal.fire({
                title: 'Confirm Approval',
                text: 'Are you sure you want to approve this document?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, Approve',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#28a745'
            }).then((result) => {
                if (result.isConfirmed) {
                    submitApproval(approvalId, 'approve');
                }
            });
        }

        function rejectDocument(approvalId) {
            Swal.fire({
                title: 'Confirm Rejection',
                text: 'Are you sure you want to reject this document?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, Reject',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#dc3545'
            }).then((result) => {
                if (result.isConfirmed) {
                    submitApproval(approvalId, 'reject');
                }
            });
        }

        function submitApproval(approvalId, action) {
            // Show loading
            Swal.fire({
                title: 'Processing...',
                text: 'Please wait while we process your request',
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Submit the approval/rejection
            fetch(`/approvals/${approvalId}/${action}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({})
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            title: 'Success!',
                            text: data.message,
                            icon: 'success',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            // Reload the page to show updated data
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            title: 'Error!',
                            text: data.error || 'An error occurred',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        title: 'Error!',
                        text: 'An error occurred while processing your request',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                });
        }
    </script>
@endsection
