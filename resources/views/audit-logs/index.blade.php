@extends('layouts.main')

@section('title_page')
    Audit Logs
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">Audit Logs</li>
@endsection

@section('content')
    <!-- Statistics Cards -->
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ number_format($stats['total'] ?? 0) }}</h3>
                    <p>Total Logs</p>
                </div>
                <div class="icon">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <div class="small-box-footer">
                    All time
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ number_format($stats['today'] ?? 0) }}</h3>
                    <p>Today's Activity</p>
                </div>
                <div class="icon">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div class="small-box-footer">
                    Last 24 hours
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $stats['by_action']->count() ?? 0 }}</h3>
                    <p>Action Types</p>
                </div>
                <div class="icon">
                    <i class="fas fa-tasks"></i>
                </div>
                <div class="small-box-footer">
                    Action breakdown
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ $stats['by_entity']->count() ?? 0 }}</h3>
                    <p>Entity Types</p>
                </div>
                <div class="icon">
                    <i class="fas fa-layer-group"></i>
                </div>
                <div class="small-box-footer">
                    Entity breakdown
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="row">
        <div class="col-12">
            <div class="card card-outline collapsed-card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-filter"></i> Filters
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <form id="filter-form">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>From Date</label>
                                    <input type="date" class="form-control" id="filter_date_from" name="date_from" 
                                           value="{{ request('date_from', now()->subDays(7)->format('Y-m-d')) }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>To Date</label>
                                    <input type="date" class="form-control" id="filter_date_to" name="date_to" 
                                           value="{{ request('date_to', now()->format('Y-m-d')) }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Entity Type(s)</label>
                                    <select class="form-control select2" id="filter_entity_types" name="entity_types[]" multiple style="width: 100%;">
                                        @foreach($entityTypes as $type)
                                            <option value="{{ $type }}" {{ in_array($type, (array)request('entity_types')) ? 'selected' : '' }}>
                                                {{ ucwords(str_replace('_', ' ', $type)) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Action(s)</label>
                                    <select class="form-control select2" id="filter_actions" name="actions[]" multiple style="width: 100%;">
                                        @foreach($actions as $action)
                                            <option value="{{ $action }}" {{ in_array($action, (array)request('actions')) ? 'selected' : '' }}>
                                                {{ ucfirst($action) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>User(s)</label>
                                    <select class="form-control select2" id="filter_user_ids" name="user_ids[]" multiple style="width: 100%;">
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}" {{ in_array($user->id, (array)request('user_ids')) ? 'selected' : '' }}>
                                                {{ $user->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>IP Address</label>
                                    <input type="text" class="form-control" id="filter_ip_address" name="ip_address" 
                                           placeholder="Filter by IP..." value="{{ request('ip_address') }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Search</label>
                                    <input type="text" class="form-control" id="filter_search" name="search" 
                                           placeholder="Search in descriptions..." value="{{ request('search') }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Filter Presets</label>
                                    <div class="input-group">
                                        <select class="form-control select2" id="filter_preset" style="width: 100%;">
                                            <option value="">Load Preset...</option>
                                        </select>
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-info" id="save-preset-btn" title="Save current filters">
                                                <i class="fas fa-save"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>Quick Filters</label>
                                    <div>
                                        <button type="button" class="btn btn-sm btn-outline-primary quick-filter" data-days="0">Today</button>
                                        <button type="button" class="btn btn-sm btn-outline-primary quick-filter" data-days="7">Last 7 Days</button>
                                        <button type="button" class="btn btn-sm btn-outline-primary quick-filter" data-days="30">Last 30 Days</button>
                                        <button type="button" class="btn btn-sm btn-outline-primary quick-filter" data-days="90">Last 90 Days</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i> Apply Filters
                                    </button>
                                    <button type="button" class="btn btn-secondary" id="reset-filters">
                                        <i class="fas fa-redo"></i> Reset
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- DataTable Card -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-list"></i> Audit Logs
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('audit-logs.export', 'excel') }}" class="btn btn-sm btn-success" id="export-excel">
                            <i class="fas fa-file-excel"></i> Excel
                        </a>
                        <a href="{{ route('audit-logs.export', 'pdf') }}" class="btn btn-sm btn-danger" id="export-pdf">
                            <i class="fas fa-file-pdf"></i> PDF
                        </a>
                        <a href="{{ route('audit-logs.export', 'csv') }}" class="btn btn-sm btn-info" id="export-csv">
                            <i class="fas fa-file-csv"></i> CSV
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped" id="audit-logs-table">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>User</th>
                                <th>Action</th>
                                <th>Entity Type</th>
                                <th>Entity ID</th>
                                <th>Description</th>
                                <th>IP Address</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Change Comparison Modal -->
    <div class="modal fade" id="change-modal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Change Details</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="change-modal-body">
                    <div class="text-center">
                        <i class="fa fa-spinner fa-spin"></i> Loading...
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize Select2
    $('.select2').select2({
        theme: 'bootstrap4'
    });

    // Load filter presets
    function loadFilterPresets() {
        $.ajax({
            url: '{{ route("audit-logs.filter-presets") }}',
            method: 'GET',
            success: function(response) {
                var presetSelect = $('#filter_preset');
                presetSelect.empty().append('<option value="">Load Preset...</option>');
                response.presets.forEach(function(preset) {
                    presetSelect.append('<option value="' + preset.id + '">' + preset.name + '</option>');
                });
            }
        });
    }

    loadFilterPresets();

    // Load preset
    $('#filter_preset').on('change', function() {
        var presetId = $(this).val();
        if (presetId) {
            $.ajax({
                url: '/audit-logs/filter-presets/' + presetId,
                method: 'GET',
                success: function(response) {
                    applyFilters(response.preset.filters);
                    table.ajax.reload();
                }
            });
        }
    });

    // Save preset
    $('#save-preset-btn').on('click', function() {
        var presetName = prompt('Enter preset name:');
        if (presetName) {
            var filters = {
                date_from: $('#filter_date_from').val(),
                date_to: $('#filter_date_to').val(),
                entity_types: $('#filter_entity_types').val(),
                actions: $('#filter_actions').val(),
                user_ids: $('#filter_user_ids').val(),
                ip_address: $('#filter_ip_address').val(),
                search: $('#filter_search').val(),
            };

            $.ajax({
                url: '{{ route("audit-logs.filter-presets.save") }}',
                method: 'POST',
                data: {
                    name: presetName,
                    filters: filters,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    toastr.success('Filter preset saved');
                    loadFilterPresets();
                },
                error: function() {
                    toastr.error('Failed to save preset');
                }
            });
        }
    });

    // Apply filters from preset
    function applyFilters(filters) {
        if (filters.date_from) $('#filter_date_from').val(filters.date_from);
        if (filters.date_to) $('#filter_date_to').val(filters.date_to);
        if (filters.entity_types) $('#filter_entity_types').val(filters.entity_types).trigger('change');
        if (filters.actions) $('#filter_actions').val(filters.actions).trigger('change');
        if (filters.user_ids) $('#filter_user_ids').val(filters.user_ids).trigger('change');
        if (filters.ip_address) $('#filter_ip_address').val(filters.ip_address);
        if (filters.search) $('#filter_search').val(filters.search);
    }

    // Quick filter buttons
    $('.quick-filter').on('click', function() {
        var days = $(this).data('days');
        var today = new Date();
        var fromDate = new Date();
        fromDate.setDate(today.getDate() - days);
        
        $('#filter_date_from').val(fromDate.toISOString().split('T')[0]);
        $('#filter_date_to').val(today.toISOString().split('T')[0]);
        table.ajax.reload();
    });

    // Initialize DataTable
    var table = $('#audit-logs-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("audit-logs.data") }}',
            data: function(d) {
                d.date_from = $('#filter_date_from').val();
                d.date_to = $('#filter_date_to').val();
                d.entity_types = $('#filter_entity_types').val();
                d.actions = $('#filter_actions').val();
                d.user_ids = $('#filter_user_ids').val();
                d.ip_address = $('#filter_ip_address').val();
                d.search = $('#filter_search').val();
            }
        },
        columns: [
            { 
                data: 'created_at', 
                name: 'created_at',
                width: '150px'
            },
            { 
                data: 'user', 
                name: 'user.name',
                width: '150px'
            },
            { 
                data: 'action', 
                name: 'action',
                width: '100px'
            },
            { 
                data: 'entity_type', 
                name: 'entity_type',
                width: '150px'
            },
            { 
                data: 'entity_id', 
                name: 'entity_id',
                width: '100px'
            },
            { 
                data: 'description', 
                name: 'description',
                width: '300px',
                orderable: false
            },
            { 
                data: 'ip_address', 
                name: 'ip_address',
                width: '120px'
            },
            { 
                data: 'actions', 
                name: 'actions',
                width: '100px',
                orderable: false,
                searchable: false
            }
        ],
        order: [[0, 'desc']],
        pageLength: 25,
        responsive: true,
        language: {
            processing: '<i class="fa fa-spinner fa-spin"></i> Loading...'
        }
    });

    // Filter form submission
    $('#filter-form').on('submit', function(e) {
        e.preventDefault();
        table.ajax.reload();
    });

    // Reset filters
    $('#reset-filters').on('click', function() {
        $('#filter-form')[0].reset();
        $('#filter_entity_types, #filter_actions, #filter_user_ids').val(null).trigger('change');
        $('#filter_preset').val(null).trigger('change');
        $('#filter_date_from').val('{{ now()->subDays(7)->format('Y-m-d') }}');
        $('#filter_date_to').val('{{ now()->format('Y-m-d') }}');
        table.ajax.reload();
    });

    // Export buttons - add current filters to URL
    $('#export-excel, #export-pdf, #export-csv').on('click', function(e) {
        var url = $(this).attr('href');
        var params = $('#filter-form').serialize();
        if (params) {
            url += '?' + params;
        }
        $(this).attr('href', url);
    });

    // View changes button
    $(document).on('click', '.view-changes-btn', function() {
        var logId = $(this).data('log-id');
        loadChangeModal(logId);
    });

    function loadChangeModal(logId) {
        $('#change-modal-body').html('<div class="text-center"><i class="fa fa-spinner fa-spin"></i> Loading...</div>');
        $('#change-modal').modal('show');
        
        $.ajax({
            url: '{{ url("audit-logs") }}/' + logId + '/changes',
            method: 'GET',
            success: function(data) {
                populateChangeModal(data);
            },
            error: function() {
                $('#change-modal-body').html('<div class="alert alert-danger">Error loading change details.</div>');
            }
        });
    }

    function populateChangeModal(data) {
        var html = '<div class="row mb-3">';
        html += '<div class="col-md-4"><strong>Action:</strong> ' + data.action + '</div>';
        html += '<div class="col-md-4"><strong>Timestamp:</strong> ' + data.created_at + '</div>';
        html += '<div class="col-md-4"><strong>User:</strong> ' + (data.user || 'System') + '</div>';
        html += '</div>';

        if (data.changes && data.changes.length > 0) {
            html += '<table class="table table-bordered table-sm">';
            html += '<thead><tr><th>Field</th><th>Old Value</th><th>New Value</th></tr></thead>';
            html += '<tbody>';
            data.changes.forEach(function(change) {
                html += '<tr>';
                html += '<td><strong>' + change.field + '</strong></td>';
                html += '<td>' + (change.old_value !== null ? change.old_value : '<em>null</em>') + '</td>';
                html += '<td>' + (change.new_value !== null ? change.new_value : '<em>null</em>') + '</td>';
                html += '</tr>';
            });
            html += '</tbody></table>';
        } else {
            html += '<div class="alert alert-info">No field changes recorded.</div>';
        }

        if (data.old_values || data.new_values) {
            html += '<div class="mt-3">';
            html += '<button class="btn btn-sm btn-secondary" type="button" data-toggle="collapse" data-target="#raw-data">Show Raw Data</button>';
            html += '<div class="collapse mt-2" id="raw-data">';
            if (data.old_values) {
                html += '<div class="mb-2"><strong>Old Values:</strong><pre class="bg-light p-2">' + JSON.stringify(data.old_values, null, 2) + '</pre></div>';
            }
            if (data.new_values) {
                html += '<div><strong>New Values:</strong><pre class="bg-light p-2">' + JSON.stringify(data.new_values, null, 2) + '</pre></div>';
            }
            html += '</div></div>';
        }

        html += '<div class="mt-3"><small class="text-muted">';
        html += '<strong>IP Address:</strong> ' + (data.ip_address || 'N/A') + ' | ';
        html += '<strong>User Agent:</strong> ' + (data.user_agent ? data.user_agent.substring(0, 50) + '...' : 'N/A');
        html += '</small></div>';

        $('#change-modal-body').html(html);
    }
});
</script>
@endpush
