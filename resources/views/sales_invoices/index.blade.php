@extends('layouts.main')

@section('title', 'Sales Invoices')

@section('title_page')
    Sales Invoices
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">Sales Invoices</li>
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
                            <h3 class="card-title">Sales Invoices</h3>
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
                                    style="width:200px" placeholder="Search invoice, customer, ref no...">
                                <select id="filter_status" class="form-control form-control-sm d-inline-block"
                                    style="width:140px">
                                    <option value="">All</option>
                                    <option value="draft">Draft</option>
                                    <option value="posted">Posted</option>
                                </select>
                                <button id="apply_filters" class="btn btn-sm btn-info">Apply</button>
                                @can('ar.invoices.create')
                                    <a href="{{ route('sales-invoices.create') }}" class="btn btn-sm btn-primary">Create</a>
                                    <a href="{{ route('sales-invoices.create', ['from_do' => 1]) }}" class="btn btn-sm btn-info">
                                        <i class="fas fa-truck"></i> Create from Delivery Order
                                    </a>
                                    <a href="{{ route('sales-invoices.import.index') }}" class="btn btn-sm btn-success">
                                        <i class="fas fa-upload"></i> Import Opening Balance
                                    </a>
                                @endcan
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-bordered table-striped table-sm mb-0" id="si-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>SI No</th>
                                        <th>Customer</th>
                                        <th>Customer Ref No</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                                <tfoot class="bg-light">
                                    <tr>
                                        <th colspan="4" class="text-right">Totals (filtered)</th>
                                        <th class="text-right font-weight-bold" id="si-sum-total">—</th>
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
                function formatSiMoney(n) {
                    return new Intl.NumberFormat('id-ID', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }).format(Number(n) || 0);
                }

                function formatSiDate(iso) {
                    if (!iso) {
                        return '';
                    }
                    var parts = String(iso).split(/[-T]/);
                    if (parts.length < 3) {
                        return iso;
                    }
                    var y = parseInt(parts[0], 10);
                    var m = parseInt(parts[1], 10);
                    var day = parseInt(parts[2], 10);
                    var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                    if (!y || !m || !day || m < 1 || m > 12) {
                        return iso;
                    }
                    return String(day).padStart(2, '0') + '-' + months[m - 1] + '-' + y;
                }

                var table = $('#si-table').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: '{{ route('sales-invoices.data') }}',
                        data: function(d) {
                            d.from = $('#filter_from').val();
                            d.to = $('#filter_to').val();
                            d.q = $('#filter_q').val();
                            d.status = $('#filter_status').val();
                            d.company_entity_id = $('input[name="entity_filter"]:checked').val() || '';
                        },
                        dataSrc: function(json) {
                            if (json.sum_total_amount !== undefined) {
                                $('#si-sum-total').text(formatSiMoney(json.sum_total_amount));
                            }
                            return json.data;
                        }
                    },
                    columns: [{
                            data: 'date',
                            name: 'si.date',
                            render: function(data, type) {
                                if (type === 'display' || type === 'filter') {
                                    return formatSiDate(data);
                                }
                                return data;
                            }
                        },
                        {
                            data: 'invoice_no',
                            name: 'si.invoice_no'
                        },
                        {
                            data: 'customer',
                            name: 'c.name',
                            orderable: false
                        },
                        {
                            data: 'reference_no',
                            name: 'si.reference_no'
                        },
                        {
                            data: 'total_amount',
                            name: 'si.total_amount',
                            className: 'text-right',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'status',
                            name: 'si.status'
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
