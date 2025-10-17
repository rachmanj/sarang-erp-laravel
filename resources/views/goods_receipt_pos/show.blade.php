@extends('layouts.main')

@section('title', 'Goods Receipt PO ' . ($grpo->grn_no ?? '#' . $grpo->id))

@section('title_page')
    Goods Receipt PO {{ $grpo->grn_no ?? '#' . $grpo->id }}
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('goods-receipt-pos.index') }}">Goods Receipt PO</a></li>
    <li class="breadcrumb-item active">{{ $grpo->grn_no ?? '#' . $grpo->id }}</li>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    @if (session('success'))
                        <script>
                            toastr.success(@json(session('success')));
                        </script>
                    @endif
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h3 class="card-title">Goods Receipt PO {{ $grpo->grn_no ?? '#' . $grpo->id }}
                                ({{ strtoupper($grpo->status) }})</h3>
                            <div class="float-right">
                                <button type="button" class="btn btn-sm btn-info mr-1"
                                    onclick="showRelationshipMap('goods-receipt-pos', {{ $grpo->id }})">
                                    <i class="fas fa-sitemap"></i> Relationship Map
                                </button>
                                @can('po.receipts.receive')
                                    @if ($grpo->status === 'draft')
                                        <form method="post" action="{{ route('goods-receipt-pos.receive', $grpo->id) }}"
                                            class="d-inline" data-confirm="Mark this GRPO as received?">
                                            @csrf
                                            <button class="btn btn-sm btn-primary" type="submit">Mark Received</button>
                                        </form>
                                    @endif
                                @endcan
                                @can('ap.invoices.create')
                                    <a href="{{ route('goods-receipt-pos.create-invoice', $grpo->id) }}"
                                        class="btn btn-sm btn-success">Create Purchase Invoice</a>
                                @endcan
                                @can('po.receipts.print')
                                    <a class="btn btn-sm btn-outline-secondary" href="#" target="_blank">Print</a>
                                @endcan
                                @can('po.receipts.pdf')
                                    <a class="btn btn-sm btn-outline-primary" href="#" target="_blank">PDF</a>
                                @endcan
                                <a href="{{ route('goods-receipt-pos.index') }}" class="btn btn-sm btn-secondary ml-2">
                                    <i class="fas fa-arrow-left"></i> Back to GRPO List
                                </a>
                            </div>
                        </div>

                        {{-- Document Navigation Components --}}
                        <div class="card-body border-bottom">
                            @include('components.document-navigation', [
                                'documentType' => 'goods-receipt-po',
                                'documentId' => $grpo->id,
                            ])
                        </div>

                        <div class="card-body">
                            <p>Date: {{ $grpo->date }}</p>
                            <p>Vendor:
                                {{ optional(DB::table('business_partners')->find($grpo->business_partner_id))->name ?? '#' . $grpo->business_partner_id }}
                            </p>
                            <p>Description: {{ $grpo->description }}</p>
                            @if (!empty($grpo->purchase_order_id))
                                @php
                                    $po = DB::table('purchase_orders')->find($grpo->purchase_order_id);
                                @endphp
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <strong>Base Document:</strong>
                                        <a href="{{ route('purchase-orders.show', $grpo->purchase_order_id) }}"
                                            class="btn btn-sm btn-info ml-2">
                                            <i class="fas fa-file-invoice"></i>
                                            {{ $po->order_no ?? 'PO #' . $grpo->purchase_order_id }}
                                        </a>
                                    </div>
                                    <div class="col-md-6">
                                        <strong>PO Date:</strong> {{ $po->date ?? 'N/A' }}
                                    </div>
                                </div>
                            @endif
                            @php
                                $orderedQty = null;
                                if (!empty($grpo->purchase_order_id)) {
                                    $orderedQty = (float) DB::table('purchase_order_lines')
                                        ->where('order_id', $grpo->purchase_order_id)
                                        ->sum('qty');
                                }
                                $receivedQty = (float) DB::table('goods_receipt_po_lines')
                                    ->where('grpo_id', $grpo->id)
                                    ->sum('qty');
                            @endphp
                            @if (!is_null($orderedQty))
                                <p><strong>Ordered vs Received:</strong> {{ number_format($orderedQty, 2) }} ordered |
                                    {{ number_format($receivedQty, 2) }} received</p>
                            @else
                                <p><strong>Received Qty:</strong> {{ number_format($receivedQty, 2) }}</p>
                            @endif
                            <hr>
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Account</th>
                                        <th>Description</th>
                                        <th>Qty</th>
                                        <th>Unit Price</th>
                                        <th>Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($grpo->lines as $l)
                                        <tr>
                                            <td>{{ optional(DB::table('accounts')->find($l->account_id))->code ?? '#' . $l->account_id }}
                                            </td>
                                            <td>{{ $l->description }}</td>
                                            <td>{{ number_format($l->qty, 2) }}</td>
                                            <td>{{ number_format($l->unit_price, 2) }}</td>
                                            <td>{{ number_format($l->amount, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <p class="text-right"><strong>Total: {{ number_format($grpo->total_amount, 2) }}</strong></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Include Relationship Map Modal --}}
    @include('components.relationship-map-modal')
@endsection

@push('scripts')
    <script>
        function previewJournal(documentType, documentId) {
            // Show loading
            Swal.fire({
                title: 'Generating Journal Preview...',
                text: 'Please wait while we prepare the journal entries.',
                allowOutsideClick: false,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading();
                }
            });

            // Send AJAX request to preview journal
            $.ajax({
                url: `/api/documents/goods-receipt-pos/{{ $grpo->id }}/journal-preview`,
                method: 'POST',
                data: {
                    action_type: 'post',
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    Swal.close();
                    if (response.success) {
                        showJournalPreviewModal(response.data);
                    } else {
                        toastr.error(response.message || 'Failed to generate journal preview.');
                    }
                },
                error: function(xhr) {
                    Swal.close();
                    let errorMessage = 'Failed to generate journal preview.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    toastr.error(errorMessage);
                }
            });
        }

        function showJournalPreviewModal(data) {
            let modalHtml = `
            <div class="modal fade" id="journalPreviewModal" tabindex="-1" role="dialog">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="fas fa-eye mr-2"></i>Preview Journal Entries
                            </h5>
                            <button type="button" class="close" data-dismiss="modal">
                                <span>&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Journal Number:</strong> ${data.journal_number || 'Auto-generated'}
                                </div>
                                <div class="col-md-6">
                                    <strong>Date:</strong> ${data.date || new Date().toLocaleDateString()}
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-12">
                                    <strong>Description:</strong> ${data.description || 'GRPO Receipt -'}
                                </div>
                            </div>
                            <h6>Journal Lines:</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-striped">
                                    <thead>
                                        <tr>
                                            <th>Account</th>
                                            <th>Description</th>
                                            <th class="text-right">Debit</th>
                                            <th class="text-right">Credit</th>
                                        </tr>
                                    </thead>
                                    <tbody>`;

            data.lines.forEach(function(line) {
                modalHtml += `
                <tr>
                    <td>${line.account_code} - ${line.account_name}</td>
                    <td>${line.description}</td>
                    <td class="text-right">${line.debit ? 'Rp ' + parseFloat(line.debit).toLocaleString('id-ID') : ''}</td>
                    <td class="text-right">${line.credit ? 'Rp ' + parseFloat(line.credit).toLocaleString('id-ID') : ''}</td>
                </tr>`;
            });

            modalHtml += `
                                    </tbody>
                                    <tfoot>
                                        <tr class="font-weight-bold">
                                            <td colspan="2">Total</td>
                                            <td class="text-right">Rp ${parseFloat(data.total_debit || 0).toLocaleString('id-ID')}</td>
                                            <td class="text-right">Rp ${parseFloat(data.total_credit || 0).toLocaleString('id-ID')}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            <div class="alert ${data.is_balanced ? 'alert-success' : 'alert-danger'}">
                                <i class="fas ${data.is_balanced ? 'fa-check' : 'fa-times'} mr-2"></i>
                                Journal is ${data.is_balanced ? 'balanced' : 'not balanced'}
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>`;

            // Remove existing modal if any
            $('#journalPreviewModal').remove();

            // Add modal to body
            $('body').append(modalHtml);

            // Show modal
            $('#journalPreviewModal').modal('show');
        }
    </script>
@endpush
