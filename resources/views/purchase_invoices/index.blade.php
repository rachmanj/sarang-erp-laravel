@extends('layouts.main')

@section('title', 'Purchase Invoices')

@section('title_page')
    Purchase Invoices
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">Purchase Invoices</li>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h3 class="card-title">Purchase Invoices</h3>
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
                                <input type="date" id="filter_from" class="form-control form-control-sm d-inline-block"
                                    style="width:160px">
                                <input type="date" id="filter_to" class="form-control form-control-sm d-inline-block"
                                    style="width:160px">
                                <input type="text" id="filter_q" class="form-control form-control-sm d-inline-block"
                                    style="width:200px" placeholder="Search...">
                                <select id="filter_status" class="form-control form-control-sm d-inline-block"
                                    style="width:140px">
                                    <option value="">All</option>
                                    <option value="draft">Draft</option>
                                    <option value="posted">Posted</option>
                                </select>
                                <button id="apply_filters" class="btn btn-sm btn-info">Apply</button>
                                <a id="export_excel" href="#" class="btn btn-sm btn-success">
                                    <i class="fas fa-file-excel mr-1"></i>Export Excel
                                </a>
                                @can('ap.invoices.create')
                                    <a href="{{ route('purchase-invoices.create') }}" class="btn btn-sm btn-primary">Create</a>
                                @endcan
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-bordered table-striped table-sm mb-0" id="pi-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Invoice No</th>
                                        <th>Vendor</th>
                                        <th>Total</th>
                                        <th>VAT</th>
                                        <th>Amount After VAT</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                                <tfoot class="bg-light">
                                    <tr>
                                        <th colspan="3" class="text-right">Totals (filtered)</th>
                                        <th class="text-right font-weight-bold" id="pi-sum-total">—</th>
                                        <th></th>
                                        <th class="text-right font-weight-bold" id="pi-sum-after-vat">—</th>
                                        <th colspan="2"></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <div class="card-footer"></div>
                    </div>
                </div>
            </div>
        </div>
    @endsection

    @push('scripts')
        <script>
            $(function() {
                function formatPiMoney(n) {
                    return new Intl.NumberFormat('id-ID', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }).format(Number(n) || 0);
                }

                function piExportUrl() {
                    var p = new URLSearchParams();
                    var from = $('#filter_from').val();
                    var to = $('#filter_to').val();
                    var q = $('#filter_q').val();
                    var status = $('#filter_status').val();
                    var entity = $('input[name="entity_filter"]:checked').val();
                    if (from) p.set('from', from);
                    if (to) p.set('to', to);
                    if (q) p.set('q', q);
                    if (status) p.set('status', status);
                    if (entity) p.set('company_entity_id', entity);
                    return '{{ route('purchase-invoices.export') }}' + (p.toString() ? '?' + p.toString() : '');
                }

                $('#export_excel').on('click', function(e) {
                    e.preventDefault();
                    window.location.href = piExportUrl();
                });

                var table = $('#pi-table').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: '{{ route('purchase-invoices.data') }}',
                        data: function(d) {
                            d.from = $('#filter_from').val();
                            d.to = $('#filter_to').val();
                            d.q = $('#filter_q').val();
                            d.status = $('#filter_status').val();
                            d.company_entity_id = $('input[name="entity_filter"]:checked').val() || '';
                        },
                        dataSrc: function(json) {
                            if (json.sum_total_amount !== undefined) {
                                $('#pi-sum-total').text(formatPiMoney(json.sum_total_amount));
                            }
                            if (json.sum_amount_after_vat !== undefined) {
                                $('#pi-sum-after-vat').text(formatPiMoney(json.sum_amount_after_vat));
                            }
                            return json.data;
                        }
                    },
                    columns: [{
                            data: 'date',
                            name: 'pi.date'
                        },
                        {
                            data: 'invoice_no',
                            name: 'pi.invoice_no'
                        },
                        {
                            data: 'vendor',
                            name: 'v.name',
                            orderable: false
                        },
                        {
                            data: 'total_amount',
                            name: 'pi.total_amount',
                            className: 'text-right',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'total_vat',
                            name: 'total_vat',
                            className: 'text-right',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'total_amount_after_vat',
                            name: 'total_amount_after_vat',
                            className: 'text-right',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'status',
                            name: 'pi.status'
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
