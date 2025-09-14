@extends('layouts.app')

@section('title', 'Tax Transaction Details')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Tax Transaction Details</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('tax.index') }}">Tax Compliance</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('tax.transactions') }}">Transactions</a></li>
                    <li class="breadcrumb-item active">{{ $transaction->transaction_no }}</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-receipt mr-2"></i>
                            Transaction Information
                        </h3>
                        <div class="card-tools">
                            <span class="badge badge-{{ $transaction->status === 'paid' ? 'success' : ($transaction->status === 'pending' ? 'warning' : 'info') }}">
                                {{ ucfirst($transaction->status) }}
                            </span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-borderless">
                                    <tr>
                                        <td><strong>Transaction Number:</strong></td>
                                        <td>{{ $transaction->transaction_no }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Transaction Date:</strong></td>
                                        <td>{{ $transaction->transaction_date->format('d M Y') }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Transaction Type:</strong></td>
                                        <td>{{ ucfirst($transaction->transaction_type) }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Tax Type:</strong></td>
                                        <td>{{ strtoupper($transaction->tax_type) }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Tax Category:</strong></td>
                                        <td>{{ ucfirst($transaction->tax_category) }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Status:</strong></td>
                                        <td>
                                            <span class="badge badge-{{ $transaction->status === 'paid' ? 'success' : ($transaction->status === 'pending' ? 'warning' : 'info') }}">
                                                {{ ucfirst($transaction->status) }}
                                            </span>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-borderless">
                                    <tr>
                                        <td><strong>Tax Entity Name:</strong></td>
                                        <td>{{ $transaction->tax_name }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Tax Number (NPWP):</strong></td>
                                        <td>{{ $transaction->tax_number ?: 'Not provided' }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Taxable Amount:</strong></td>
                                        <td>Rp {{ number_format($transaction->taxable_amount, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Tax Rate:</strong></td>
                                        <td>{{ $transaction->tax_rate }}%</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Tax Amount:</strong></td>
                                        <td>Rp {{ number_format($transaction->tax_amount, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Total Amount:</strong></td>
                                        <td><strong>Rp {{ number_format($transaction->total_amount, 2) }}</strong></td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        @if($transaction->tax_address)
                        <div class="row mt-3">
                            <div class="col-12">
                                <h5>Tax Entity Address</h5>
                                <p class="text-muted">{{ $transaction->tax_address }}</p>
                            </div>
                        </div>
                        @endif

                        @if($transaction->notes)
                        <div class="row mt-3">
                            <div class="col-12">
                                <h5>Notes</h5>
                                <p class="text-muted">{{ $transaction->notes }}</p>
                            </div>
                        </div>
                        @endif

                        @if($transaction->due_date)
                        <div class="row mt-3">
                            <div class="col-12">
                                <h5>Due Date</h5>
                                <p class="{{ $transaction->is_overdue ? 'text-danger' : 'text-muted' }}">
                                    {{ $transaction->due_date->format('d M Y') }}
                                    @if($transaction->is_overdue)
                                        <span class="badge badge-danger ml-2">{{ $transaction->days_overdue }} days overdue</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                        @endif

                        @if($transaction->paid_date)
                        <div class="row mt-3">
                            <div class="col-12">
                                <h5>Payment Information</h5>
                                <table class="table table-borderless">
                                    <tr>
                                        <td><strong>Paid Date:</strong></td>
                                        <td>{{ $transaction->paid_date->format('d M Y') }}</td>
                                    </tr>
                                    @if($transaction->payment_method)
                                    <tr>
                                        <td><strong>Payment Method:</strong></td>
                                        <td>{{ $transaction->payment_method }}</td>
                                    </tr>
                                    @endif
                                    @if($transaction->payment_reference)
                                    <tr>
                                        <td><strong>Payment Reference:</strong></td>
                                        <td>{{ $transaction->payment_reference }}</td>
                                    </tr>
                                    @endif
                                </table>
                            </div>
                        </div>
                        @endif

                        <div class="row mt-3">
                            <div class="col-12">
                                <h5>System Information</h5>
                                <table class="table table-borderless">
                                    <tr>
                                        <td><strong>Created By:</strong></td>
                                        <td>{{ $transaction->createdBy->name ?? 'System' }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Created At:</strong></td>
                                        <td>{{ $transaction->created_at->format('d M Y H:i') }}</td>
                                    </tr>
                                    @if($transaction->updatedBy)
                                    <tr>
                                        <td><strong>Last Updated By:</strong></td>
                                        <td>{{ $transaction->updatedBy->name }}</td>
                                    </tr>
                                    @endif
                                    <tr>
                                        <td><strong>Last Updated:</strong></td>
                                        <td>{{ $transaction->updated_at->format('d M Y H:i') }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        @if($transaction->status === 'pending' || $transaction->status === 'approved')
                        <button type="button" class="btn btn-success" data-toggle="modal" data-target="#paymentModal">
                            <i class="fas fa-check mr-2"></i>
                            Mark as Paid
                        </button>
                        @endif
                        <a href="{{ route('tax.transactions') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left mr-2"></i>
                            Back to Transactions
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <!-- Related Entities -->
                @if($transaction->vendor || $transaction->customer)
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-building mr-2"></i>
                            Related Entity
                        </h3>
                    </div>
                    <div class="card-body">
                        @if($transaction->vendor)
                        <h5>Vendor Information</h5>
                        <table class="table table-sm">
                            <tr>
                                <td><strong>Name:</strong></td>
                                <td>{{ $transaction->vendor->name }}</td>
                            </tr>
                            <tr>
                                <td><strong>NPWP:</strong></td>
                                <td>{{ $transaction->vendor->npwp ?: 'Not provided' }}</td>
                            </tr>
                            <tr>
                                <td><strong>Phone:</strong></td>
                                <td>{{ $transaction->vendor->phone ?: 'Not provided' }}</td>
                            </tr>
                            <tr>
                                <td><strong>Email:</strong></td>
                                <td>{{ $transaction->vendor->email ?: 'Not provided' }}</td>
                            </tr>
                        </table>
                        @endif

                        @if($transaction->customer)
                        <h5>Customer Information</h5>
                        <table class="table table-sm">
                            <tr>
                                <td><strong>Name:</strong></td>
                                <td>{{ $transaction->customer->name }}</td>
                            </tr>
                            <tr>
                                <td><strong>NPWP:</strong></td>
                                <td>{{ $transaction->customer->npwp ?: 'Not provided' }}</td>
                            </tr>
                            <tr>
                                <td><strong>Phone:</strong></td>
                                <td>{{ $transaction->customer->phone ?: 'Not provided' }}</td>
                            </tr>
                            <tr>
                                <td><strong>Email:</strong></td>
                                <td>{{ $transaction->customer->email ?: 'Not provided' }}</td>
                            </tr>
                        </table>
                        @endif
                    </div>
                </div>
                @endif

                <!-- Audit Trail -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-history mr-2"></i>
                            Audit Trail
                        </h3>
                    </div>
                    <div class="card-body">
                        @if($auditTrail->count() > 0)
                        <div class="timeline">
                            @foreach($auditTrail as $log)
                            <div class="time-label">
                                <span class="bg-{{ $log->action_color }}">
                                    {{ $log->created_at->format('d M') }}
                                </span>
                            </div>
                            <div>
                                <i class="fas fa-{{ $log->action === 'created' ? 'plus' : ($log->action === 'updated' ? 'edit' : 'check') }} bg-{{ $log->action_color }}"></i>
                                <div class="timeline-item">
                                    <span class="time">
                                        <i class="fas fa-clock"></i> {{ $log->created_at->format('H:i') }}
                                    </span>
                                    <h3 class="timeline-header">
                                        {{ ucfirst($log->action) }} by {{ $log->user->name ?? 'System' }}
                                    </h3>
                                    @if($log->description)
                                    <div class="timeline-body">
                                        {{ $log->description }}
                                    </div>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @else
                        <p class="text-muted">No audit trail available.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Mark Transaction as Paid</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="payment-form">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="payment_method">Payment Method</label>
                        <input type="text" class="form-control" id="payment_method" placeholder="e.g., Bank Transfer, Cash">
                    </div>
                    <div class="form-group">
                        <label for="payment_reference">Payment Reference</label>
                        <input type="text" class="form-control" id="payment_reference" placeholder="e.g., Receipt number, Transaction ID">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Mark as Paid</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Payment form submission
    $('#payment-form').submit(function(e) {
        e.preventDefault();
        
        var paymentMethod = $('#payment_method').val();
        var paymentReference = $('#payment_reference').val();
        
        $.ajax({
            url: '{{ route("tax.transactions.mark-paid", $transaction->id) }}',
            method: 'POST',
            data: {
                payment_method: paymentMethod,
                payment_reference: paymentReference,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                $('#paymentModal').modal('hide');
                location.reload();
            },
            error: function(xhr) {
                toastr.error('Error updating transaction: ' + xhr.responseJSON.message);
            }
        });
    });
});
</script>
@endpush