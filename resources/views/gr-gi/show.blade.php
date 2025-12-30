@extends('layouts.main')

@section('title', 'GR/GI Document Details')

@section('title_page')
    {{ $grGi->document_number }} - {{ $grGi->document_type_name }}
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('gr-gi.index') }}">GR/GI Management</a></li>
    <li class="breadcrumb-item active">{{ $grGi->document_number }}</li>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-file-alt mr-1"></i>
                                {{ $grGi->document_number }} - {{ $grGi->document_type_name }}
                            </h3>
                            <div class="card-tools">
                                <a href="{{ route('gr-gi.index') }}" class="btn btn-tool btn-sm">
                                    <i class="fas fa-arrow-left"></i> Back to GR/GI
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Document Status -->
                            <div class="row mb-3">
                                <div class="col-12">
                                    <div
                                        class="alert alert-{{ $grGi->status === 'approved' ? 'success' : ($grGi->status === 'cancelled' ? 'danger' : 'info') }}">
                                        <h5 class="mb-0">
                                            <i class="fas fa-info-circle mr-1"></i>
                                            Document Status:
                                            <span class="badge {{ $grGi->status_badge }}">
                                                {{ ucfirst(str_replace('_', ' ', $grGi->status)) }}
                                            </span>
                                        </h5>
                                    </div>
                                </div>
                            </div>

                            <!-- Document Information -->
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="card-title mb-0">
                                                <i class="fas fa-info mr-1"></i>
                                                Document Information
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <table class="table table-sm">
                                                <tr>
                                                    <th width="40%">Document Number:</th>
                                                    <td>{{ $grGi->document_number }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Document Type:</th>
                                                    <td>
                                                        <span
                                                            class="badge {{ $grGi->document_type === 'goods_receipt' ? 'badge-success' : 'badge-warning' }}">
                                                            {{ $grGi->document_type_name }}
                                                        </span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Purpose:</th>
                                                    <td>{{ $grGi->purpose->name }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Warehouse:</th>
                                                    <td>{{ $grGi->warehouse->code }} - {{ $grGi->warehouse->name }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Transaction Date:</th>
                                                    <td>{{ $grGi->transaction_date->format('M d, Y') }}</td>
                                                </tr>
                                                @if ($grGi->reference_number)
                                                    <tr>
                                                        <th>Reference Number:</th>
                                                        <td>{{ $grGi->reference_number }}</td>
                                                    </tr>
                                                @endif
                                                @if ($grGi->notes)
                                                    <tr>
                                                        <th>Notes:</th>
                                                        <td>{{ $grGi->notes }}</td>
                                                    </tr>
                                                @endif
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="card-title mb-0">
                                                <i class="fas fa-user mr-1"></i>
                                                User Information
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <table class="table table-sm">
                                                <tr>
                                                    <th width="40%">Created By:</th>
                                                    <td>{{ $grGi->creator->name }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Created At:</th>
                                                    <td>{{ $grGi->created_at->format('M d, Y H:i') }}</td>
                                                </tr>
                                                @if ($grGi->approved_by)
                                                    <tr>
                                                        <th>Approved By:</th>
                                                        <td>{{ $grGi->approver->name }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th>Approved At:</th>
                                                        <td>{{ $grGi->approved_at->format('M d, Y H:i') }}</td>
                                                    </tr>
                                                @endif
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Document Lines -->
                            <div class="row mt-3">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="card-title mb-0">
                                                <i class="fas fa-list mr-1"></i>
                                                Document Lines
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table table-bordered table-striped">
                                                    <thead>
                                                        <tr>
                                                            <th>Item Code</th>
                                                            <th>Item Name</th>
                                                            <th>Unit</th>
                                                            <th class="text-right">Quantity</th>
                                                            <th class="text-right">Unit Price</th>
                                                            <th class="text-right">Total Amount</th>
                                                            <th>Notes</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($grGi->lines as $line)
                                                            <tr>
                                                                <td>{{ $line->item->code }}</td>
                                                                <td>{{ $line->item->name }}</td>
                                                                <td>{{ $line->item->unit_of_measure }}</td>
                                                                <td class="text-right">
                                                                    {{ number_format($line->quantity, 2) }}</td>
                                                                <td class="text-right">
                                                                    {{ number_format($line->unit_price, 2) }}</td>
                                                                <td class="text-right">
                                                                    {{ number_format($line->total_amount, 2) }}</td>
                                                                <td>{{ $line->notes ?? '-' }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                    <tfoot>
                                                        <tr class="table-primary">
                                                            <th colspan="5" class="text-right">Total Amount:</th>
                                                            <th class="text-right">
                                                                {{ number_format($grGi->total_amount, 2) }}</th>
                                                            <th></th>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card-footer">
                            <div class="row">
                                <div class="col-md-6">
                                    @if ($grGi->canBeEdited())
                                        <a href="{{ route('gr-gi.edit', $grGi) }}" class="btn btn-warning">
                                            <i class="fas fa-edit"></i> Edit Document
                                        </a>
                                    @endif
                                </div>
                                <div class="col-md-6 text-right">
                                    @if ($grGi->status === 'draft')
                                        <button type="button" class="btn btn-warning" onclick="submitForApproval()">
                                            <i class="fas fa-paper-plane"></i> Submit for Approval
                                        </button>
                                    @endif

                                    @if ($grGi->canBeApproved())
                                        <button type="button" class="btn btn-success" onclick="approveDocument()">
                                            <i class="fas fa-check"></i> Approve Document
                                        </button>
                                    @endif

                                    @if ($grGi->canBeCancelled())
                                        <button type="button" class="btn btn-danger" onclick="cancelDocument()">
                                            <i class="fas fa-times"></i> Cancel Document
                                        </button>
                                    @endif

                                    <a href="{{ route('gr-gi.index') }}" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left"></i> Back to List
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        function submitForApproval() {
            Swal.fire({
                title: 'Submit for Approval',
                text: 'Are you sure you want to submit this document for approval?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#ffc107',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Submit!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '{{ route('gr-gi.submit', $grGi) }}';

                    const csrfToken = document.createElement('input');
                    csrfToken.type = 'hidden';
                    csrfToken.name = '_token';
                    csrfToken.value = '{{ csrf_token() }}';

                    form.appendChild(csrfToken);
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        function approveDocument() {
            Swal.fire({
                title: 'Approve Document',
                text: 'Are you sure you want to approve this document? This will create journal entries and update inventory.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Approve!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '{{ route('gr-gi.approve', $grGi) }}';

                    const csrfToken = document.createElement('input');
                    csrfToken.type = 'hidden';
                    csrfToken.name = '_token';
                    csrfToken.value = '{{ csrf_token() }}';

                    form.appendChild(csrfToken);
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        function cancelDocument() {
            Swal.fire({
                title: 'Cancel Document',
                text: 'Are you sure you want to cancel this document?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Cancel!',
                cancelButtonText: 'No'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '{{ route('gr-gi.cancel', $grGi) }}';

                    const csrfToken = document.createElement('input');
                    csrfToken.type = 'hidden';
                    csrfToken.name = '_token';
                    csrfToken.value = '{{ csrf_token() }}';

                    form.appendChild(csrfToken);
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }
    </script>
@endpush
