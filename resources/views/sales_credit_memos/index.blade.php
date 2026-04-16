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
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h3 class="card-title">Sales Credit Memos</h3>
                            <div>
                                <div class="d-inline-block mr-2">
                                    <label class="mr-1 small mb-0">Entity:</label>
                                    <div class="form-check form-check-inline d-inline">
                                        <input class="form-check-input" type="radio" name="entity_filter" id="entity-all" value="" checked>
                                        <label class="form-check-label" for="entity-all">All</label>
                                    </div>
                                    @if ($ptCahaya ?? null)
                                        <div class="form-check form-check-inline d-inline">
                                            <input class="form-check-input" type="radio" name="entity_filter" id="entity-pt" value="{{ $ptCahaya->id }}">
                                            <label class="form-check-label" for="entity-pt">PT Cahaya Sarange Jaya</label>
                                        </div>
                                    @endif
                                    @if ($cvCahaya ?? null)
                                        <div class="form-check form-check-inline d-inline">
                                            <input class="form-check-input" type="radio" name="entity_filter" id="entity-cv" value="{{ $cvCahaya->id }}">
                                            <label class="form-check-label" for="entity-cv">CV Cahaya Saranghae</label>
                                        </div>
                                    @endif
                                </div>
                                <input type="date" id="filter_from" class="form-control form-control-sm d-inline-block" style="width:160px">
                                <input type="date" id="filter_to" class="form-control form-control-sm d-inline-block" style="width:160px">
                                <input type="text" id="filter_q" class="form-control form-control-sm d-inline-block" style="width:200px" placeholder="Memo, invoice, customer...">
                                <select id="filter_status" class="form-control form-control-sm d-inline-block" style="width:140px">
                                    <option value="">All</option>
                                    <option value="draft">Draft</option>
                                    <option value="posted">Posted</option>
                                </select>
                                <button id="apply_filters" class="btn btn-sm btn-info">Apply</button>
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
