@extends('layouts.main')

@section('title', 'Asset Disposals')

@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Asset Disposals</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('assets.index') }}">Assets</a></li>
                        <li class="breadcrumb-item active">Disposals</li>
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
                            <h3 class="card-title">Asset Disposals</h3>
                            <div class="card-tools">
                                @can('create', App\Models\AssetDisposal::class)
                                    <a href="{{ route('assets.disposals.create') }}" class="btn btn-primary btn-sm">
                                        <i class="fas fa-plus"></i> New Disposal
                                    </a>
                                @endcan
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Filters -->
                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <select class="form-control select2bs4" id="status-filter">
                                        <option value="">All Status</option>
                                        <option value="draft">Draft</option>
                                        <option value="posted">Posted</option>
                                        <option value="reversed">Reversed</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <select class="form-control select2bs4" id="type-filter">
                                        <option value="">All Types</option>
                                        <option value="sale">Sale</option>
                                        <option value="scrap">Scrap</option>
                                        <option value="donation">Donation</option>
                                        <option value="trade_in">Trade-in</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <input type="date" class="form-control" id="date-from" placeholder="From Date">
                                </div>
                                <div class="col-md-2">
                                    <input type="date" class="form-control" id="date-to" placeholder="To Date">
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-info btn-block" id="apply-filters">
                                        <i class="fas fa-filter"></i> Filter
                                    </button>
                                </div>
                            </div>

                            <!-- DataTable -->
                            <div class="table-responsive">
                                <table id="disposals-table" class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Asset Code</th>
                                            <th>Asset Name</th>
                                            <th>Category</th>
                                            <th>Disposal Date</th>
                                            <th>Type</th>
                                            <th>Proceeds</th>
                                            <th>Book Value</th>
                                            <th>Gain/Loss</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Data loaded via AJAX -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            // Initialize Select2
            $('.select2bs4').select2({
                theme: 'bootstrap4',
                width: '100%'
            });

            // Initialize DataTable
            var table = $('#disposals-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('assets.disposals.data') }}',
                    data: function(d) {
                        d.status = $('#status-filter').val();
                        d.disposal_type = $('#type-filter').val();
                        d.date_from = $('#date-from').val();
                        d.date_to = $('#date-to').val();
                        d.search = d.search.value;
                    }
                },
                columns: [{
                        data: 'asset_code',
                        name: 'asset_code'
                    },
                    {
                        data: 'asset_name',
                        name: 'asset_name'
                    },
                    {
                        data: 'category_name',
                        name: 'category_name'
                    },
                    {
                        data: 'disposal_date',
                        name: 'disposal_date',
                        render: function(data) {
                            return data ? moment(data).format('DD/MM/YYYY') : '';
                        }
                    },
                    {
                        data: 'disposal_type',
                        name: 'disposal_type',
                        render: function(data) {
                            const types = {
                                'sale': 'Sale',
                                'scrap': 'Scrap',
                                'donation': 'Donation',
                                'trade_in': 'Trade-in',
                                'other': 'Other'
                            };
                            return types[data] || data;
                        }
                    },
                    {
                        data: 'disposal_proceeds',
                        name: 'disposal_proceeds',
                        render: function(data) {
                            return data ? 'Rp ' + parseFloat(data).toLocaleString('id-ID') : '-';
                        }
                    },
                    {
                        data: 'book_value_at_disposal',
                        name: 'book_value_at_disposal',
                        render: function(data) {
                            return 'Rp ' + parseFloat(data).toLocaleString('id-ID');
                        }
                    },
                    {
                        data: 'gain_loss_amount',
                        name: 'gain_loss_amount',
                        render: function(data, type, row) {
                            if (!data) return '-';
                            const amount = parseFloat(data).toLocaleString('id-ID');
                            const typeClass = row.gain_loss_type === 'gain' ? 'text-success' :
                                row.gain_loss_type === 'loss' ? 'text-danger' : 'text-muted';
                            const label = row.gain_loss_type === 'gain' ? 'Gain' :
                                row.gain_loss_type === 'loss' ? 'Loss' : 'No Gain/Loss';
                            return `<span class="${typeClass}">${label}: Rp ${amount}</span>`;
                        }
                    },
                    {
                        data: 'status',
                        name: 'status',
                        render: function(data) {
                            const badges = {
                                'draft': '<span class="badge badge-warning">Draft</span>',
                                'posted': '<span class="badge badge-success">Posted</span>',
                                'reversed': '<span class="badge badge-danger">Reversed</span>'
                            };
                            return badges[data] || data;
                        }
                    },
                    {
                        data: 'id',
                        name: 'actions',
                        orderable: false,
                        searchable: false,
                        render: function(data, type, row) {
                            let actions = '';

                            // View button
                            actions += `<a href="/assets/disposals/${data}" class="btn btn-info btn-sm" title="View">
                        <i class="fas fa-eye"></i>
                    </a> `;

                            // Edit button (only for draft)
                            if (row.status === 'draft') {
                                actions += `<a href="/assets/disposals/${data}/edit" class="btn btn-warning btn-sm" title="Edit">
                            <i class="fas fa-edit"></i>
                        </a> `;
                            }

                            // Post button (only for draft)
                            if (row.status === 'draft') {
                                actions += `<form method="POST" action="/assets/disposals/${data}/post" style="display: inline;">
                            @csrf
                            <button type="submit" class="btn btn-success btn-sm" title="Post" 
                                onclick="return confirm('Are you sure you want to post this disposal?')">
                                <i class="fas fa-check"></i>
                            </button>
                        </form> `;
                            }

                            // Reverse button (only for posted)
                            if (row.status === 'posted') {
                                actions += `<form method="POST" action="/assets/disposals/${data}/reverse" style="display: inline;">
                            @csrf
                            <button type="submit" class="btn btn-danger btn-sm" title="Reverse" 
                                onclick="return confirm('Are you sure you want to reverse this disposal?')">
                                <i class="fas fa-undo"></i>
                            </button>
                        </form> `;
                            }

                            // Delete button (only for draft)
                            if (row.status === 'draft') {
                                actions += `<form method="POST" action="/assets/disposals/${data}" style="display: inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm" title="Delete" 
                                onclick="return confirm('Are you sure you want to delete this disposal?')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>`;
                            }

                            return actions;
                        }
                    }
                ],
                order: [
                    [3, 'desc']
                ], // Sort by disposal date descending
                pageLength: 15,
                responsive: true,
                language: {
                    processing: "Loading...",
                    emptyTable: "No disposals found",
                    zeroRecords: "No matching disposals found"
                }
            });

            // Apply filters
            $('#apply-filters').click(function() {
                table.ajax.reload();
            });

            // Clear filters
            $('#clear-filters').click(function() {
                $('#status-filter').val('').trigger('change');
                $('#type-filter').val('').trigger('change');
                $('#date-from').val('');
                $('#date-to').val('');
                table.ajax.reload();
            });
        });
    </script>
@endsection
