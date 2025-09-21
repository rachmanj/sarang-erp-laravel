@extends('layouts.main')

@section('title', 'GR/GI Management')

@section('title_page')
    GR/GI Management
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">GR/GI Management</li>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-boxes mr-1"></i>
                                GR/GI Documents
                            </h3>
                            <div class="card-tools">
                                @can('gr-gi.create')
                                    <div class="btn-group">
                                        <a href="{{ route('gr-gi.create', ['type' => 'goods_receipt']) }}"
                                            class="btn btn-success btn-sm">
                                            <i class="fas fa-plus"></i> New GR
                                        </a>
                                        <a href="{{ route('gr-gi.create', ['type' => 'goods_issue']) }}"
                                            class="btn btn-warning btn-sm">
                                            <i class="fas fa-plus"></i> New GI
                                        </a>
                                    </div>
                                @endcan
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Filters -->
                            <form class="form-inline mb-3" id="filterForm">
                                <div class="form-group mr-2">
                                    <label for="document_type" class="sr-only">Document Type:</label>
                                    <select class="form-control form-control-sm" id="document_type" name="document_type">
                                        <option value="">All Types</option>
                                        <option value="goods_receipt">Goods Receipt (GR)</option>
                                        <option value="goods_issue">Goods Issue (GI)</option>
                                    </select>
                                </div>
                                <div class="form-group mr-2">
                                    <label for="status" class="sr-only">Status:</label>
                                    <select class="form-control form-control-sm" id="status" name="status">
                                        <option value="">All Status</option>
                                        <option value="draft">Draft</option>
                                        <option value="pending_approval">Pending Approval</option>
                                        <option value="approved">Approved</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                </div>
                                <div class="form-group mr-2">
                                    <label for="warehouse_id" class="sr-only">Warehouse:</label>
                                    <select class="form-control form-control-sm" id="warehouse_id" name="warehouse_id">
                                        <option value="">All Warehouses</option>
                                        @foreach ($warehouses as $warehouse)
                                            <option value="{{ $warehouse->id }}">{{ $warehouse->code }} -
                                                {{ $warehouse->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group mr-2">
                                    <label for="date_from" class="sr-only">From Date:</label>
                                    <input type="date" class="form-control form-control-sm" id="date_from"
                                        name="date_from" placeholder="From Date">
                                </div>
                                <div class="form-group mr-2">
                                    <label for="date_to" class="sr-only">To Date:</label>
                                    <input type="date" class="form-control form-control-sm" id="date_to" name="date_to"
                                        placeholder="To Date">
                                </div>
                                <button type="submit" class="btn btn-primary btn-sm mr-1">
                                    <i class="fas fa-filter"></i> Filter
                                </button>
                                <a href="{{ route('gr-gi.index') }}" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-times"></i> Clear
                                </a>
                            </form>

                            <!-- Data Table -->
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" id="gr-gi-table">
                                    <thead>
                                        <tr>
                                            <th>Document #</th>
                                            <th>Type</th>
                                            <th>Purpose</th>
                                            <th>Warehouse</th>
                                            <th>Date</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Created By</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($headers as $header)
                                            <tr>
                                                <td>
                                                    <a href="{{ route('gr-gi.show', $header) }}" class="text-primary">
                                                        {{ $header->document_number }}
                                                    </a>
                                                </td>
                                                <td>
                                                    <span
                                                        class="badge {{ $header->document_type === 'goods_receipt' ? 'badge-success' : 'badge-warning' }}">
                                                        {{ $header->document_type_name }}
                                                    </span>
                                                </td>
                                                <td>{{ $header->purpose->name }}</td>
                                                <td>{{ $header->warehouse->code }} - {{ $header->warehouse->name }}</td>
                                                <td>{{ $header->transaction_date->format('M d, Y') }}</td>
                                                <td class="text-right">{{ number_format($header->total_amount, 2) }}</td>
                                                <td>
                                                    <span class="badge {{ $header->status_badge }}">
                                                        {{ ucfirst(str_replace('_', ' ', $header->status)) }}
                                                    </span>
                                                </td>
                                                <td>{{ $header->creator->name }}</td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="{{ route('gr-gi.show', $header) }}"
                                                            class="btn btn-info btn-sm" title="View">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        @if ($header->canBeEdited())
                                                            <a href="{{ route('gr-gi.edit', $header) }}"
                                                                class="btn btn-warning btn-sm" title="Edit">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                        @endif
                                                        @if ($header->canBeApproved())
                                                            <form action="{{ route('gr-gi.approve', $header) }}"
                                                                method="POST" class="d-inline">
                                                                @csrf
                                                                <button type="submit" class="btn btn-success btn-sm"
                                                                    title="Approve"
                                                                    onclick="return confirm('Are you sure you want to approve this document?')">
                                                                    <i class="fas fa-check"></i>
                                                                </button>
                                                            </form>
                                                        @endif
                                                        @if ($header->canBeCancelled())
                                                            <button type="button" class="btn btn-danger btn-sm"
                                                                title="Cancel"
                                                                onclick="cancelDocument({{ $header->id }}, '{{ $header->document_number }}')">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <div class="d-flex justify-content-center">
                                {{ $headers->links() }}
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
        $(document).ready(function() {
            // Initialize DataTable
            $('#gr-gi-table').DataTable({
                "paging": false,
                "lengthChange": false,
                "searching": false,
                "ordering": true,
                "info": false,
                "autoWidth": false,
                "responsive": true,
                "order": [
                    [0, "desc"]
                ]
            });

            // Filter form submission
            $('#filterForm').on('submit', function(e) {
                e.preventDefault();

                const formData = new FormData(this);
                const params = new URLSearchParams();

                for (let [key, value] of formData.entries()) {
                    if (value) {
                        params.append(key, value);
                    }
                }

                const url = new URL(window.location);
                url.search = params.toString();
                window.location.href = url.toString();
            });

            // Set current filter values
            const urlParams = new URLSearchParams(window.location.search);
            $('#document_type').val(urlParams.get('document_type') || '');
            $('#status').val(urlParams.get('status') || '');
            $('#warehouse_id').val(urlParams.get('warehouse_id') || '');
            $('#date_from').val(urlParams.get('date_from') || '');
            $('#date_to').val(urlParams.get('date_to') || '');
        });

        function cancelDocument(id, documentNumber) {
            Swal.fire({
                title: 'Cancel Document',
                text: `Are you sure you want to cancel document ${documentNumber}?`,
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
                    form.action = `/gr-gi/${id}/cancel`;

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
