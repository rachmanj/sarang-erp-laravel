@extends('layouts.main')

@section('title', 'Sales Credit Memos')

@section('title_page')
    Sales Credit Memos
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">Sales Credit Memos</li>
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
                    @if (session('info'))
                        <script>
                            toastr.info(@json(session('info')));
                        </script>
                    @endif
                    @if (session('error'))
                        <script>
                            toastr.error(@json(session('error')));
                        </script>
                    @endif
                    <div class="card card-outline card-primary">
                        <div class="card-header">
                            <div class="d-flex flex-wrap justify-content-between align-items-center">
                                <h3 class="card-title mb-2 mb-md-0">
                                    <i class="fas fa-file-invoice mr-1"></i>
                                    Sales Credit Memos
                                </h3>
                            </div>

                            <div class="border-top pt-3 mt-1">
                                <div class="d-flex flex-wrap align-items-end">
                                    <x-document-index-filter-group label="Entity">
                                        <x-entity-filter-buttons />
                                    </x-document-index-filter-group>

                                    <x-document-index-filter-group label="Period" for="filter_from">
                                        <div class="d-flex align-items-center">
                                            <input type="date" id="filter_from" class="form-control form-control-sm" style="width:150px">
                                            <span class="text-muted mx-1">–</span>
                                            <input type="date" id="filter_to" class="form-control form-control-sm" style="width:150px">
                                        </div>
                                    </x-document-index-filter-group>

                                    <x-document-index-filter-group label="Search" for="filter_q">
                                        <input type="text" id="filter_q" class="form-control form-control-sm"
                                            style="width:220px" placeholder="Memo no, invoice, customer…">
                                    </x-document-index-filter-group>

                                    <x-document-index-filter-group label="Posting" for="filter_status">
                                        <select id="filter_status" class="form-control form-control-sm" style="width:120px">
                                            <option value="">Any</option>
                                            <option value="draft">Draft</option>
                                            <option value="posted">Posted</option>
                                        </select>
                                    </x-document-index-filter-group>

                                    <x-document-index-filter-group label="&nbsp;">
                                        <button id="apply_filters" type="button" class="btn btn-sm btn-info">
                                            <i class="fas fa-filter mr-1"></i>Apply
                                        </button>
                                    </x-document-index-filter-group>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-bordered table-striped table-sm mb-0" id="scm-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Memo No</th>
                                        <th>Sales Invoice</th>
                                        <th>Customer</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                                <tfoot class="bg-light">
                                    <tr>
                                        <th colspan="4" class="text-right">Totals (filtered)</th>
                                        <th class="text-right font-weight-bold" id="scm-sum-total">—</th>
                                        <th colspan="2"></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        $(function() {
            function formatMoney(n) {
                return new Intl.NumberFormat('id-ID', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }).format(Number(n) || 0);
            }

            var table = $('#scm-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('sales-credit-memos.data') }}',
                    data: function(d) {
                        d.from = $('#filter_from').val();
                        d.to = $('#filter_to').val();
                        d.q = $('#filter_q').val();
                        d.status = $('#filter_status').val();
                        d.company_entity_id = $('input[name="entity_filter"]:checked').val() || '';
                    },
                    dataSrc: function(json) {
                        if (json.sum_total_amount !== undefined) {
                            $('#scm-sum-total').text(formatMoney(json.sum_total_amount));
                        }
                        return json.data;
                    }
                },
                columns: [{
                        data: 'date',
                        name: 'scm.date'
                    },
                    {
                        data: 'memo_no',
                        name: 'scm.memo_no'
                    },
                    {
                        data: 'sales_invoice',
                        name: 'si.invoice_no',
                        orderable: false
                    },
                    {
                        data: 'customer',
                        name: 'c.name',
                        orderable: false
                    },
                    {
                        data: 'total_amount',
                        name: 'scm.total_amount',
                        className: 'text-right',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'status',
                        name: 'scm.status'
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false
                    }
                ],
                order: [
                    [0, 'desc']
                ]
            });
            $('#apply_filters').on('click', function() {
                table.ajax.reload();
            });
            $('input[name="entity_filter"]').on('change', function() {
                table.ajax.reload();
            });
        });
    </script>
@endpush
