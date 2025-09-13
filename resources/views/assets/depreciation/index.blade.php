@extends('layouts.main')

@section('title', 'Depreciation Runs')

@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Depreciation Runs</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('assets.index') }}">Assets</a></li>
                        <li class="breadcrumb-item active">Depreciation Runs</li>
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
                            <h3 class="card-title">Monthly Depreciation Runs</h3>
                            @can('assets.depreciation.run')
                                <div class="card-tools">
                                    <button type="button" class="btn btn-primary btn-sm" data-toggle="modal"
                                        data-target="#createRunModal">
                                        <i class="fas fa-plus"></i> Create Run
                                    </button>
                                </div>
                            @endcan
                        </div>
                        <div class="card-body">
                            <table id="depreciationRunsTable" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Period</th>
                                        <th>Status</th>
                                        <th>Total Depreciation</th>
                                        <th>Asset Count</th>
                                        <th>Created By</th>
                                        <th>Posted By</th>
                                        <th>Posted At</th>
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
        </div>
    </section>

    @can('assets.depreciation.run')
        <!-- Create Run Modal -->
        <div class="modal fade" id="createRunModal" tabindex="-1" role="dialog" aria-labelledby="createRunModalLabel"
            aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <form id="createRunForm">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title" id="createRunModalLabel">Create Depreciation Run</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label for="period">Period <span class="text-danger">*</span></label>
                                <input type="month" class="form-control" id="period" name="period" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Create Run</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            var table = $('#depreciationRunsTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('assets.depreciation.data') }}",
                    type: 'GET'
                },
                columns: [{
                        data: 'period_display',
                        name: 'period_display'
                    },
                    {
                        data: 'status_badge',
                        name: 'status_badge',
                        orderable: false
                    },
                    {
                        data: 'total_depreciation_formatted',
                        name: 'total_depreciation_formatted'
                    },
                    {
                        data: 'asset_count',
                        name: 'asset_count'
                    },
                    {
                        data: 'creator_name',
                        name: 'creator_name'
                    },
                    {
                        data: 'poster_name',
                        name: 'poster_name'
                    },
                    {
                        data: 'posted_at_formatted',
                        name: 'posted_at_formatted'
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false
                    }
                ],
                responsive: true,
                autoWidth: false,
                pageLength: 25,
                order: [
                    [0, 'desc']
                ]
            });

            // Form submission for create run
            $('#createRunForm').on('submit', function(e) {
                e.preventDefault();

                var formData = new FormData(this);

                $.ajax({
                    url: "{{ route('assets.depreciation.store') }}",
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message);
                            $('#createRunModal').modal('hide');
                            $('#createRunForm')[0].reset();
                            table.ajax.reload();
                        }
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            var errors = xhr.responseJSON.errors;
                            $.each(errors, function(key, value) {
                                toastr.error(value[0]);
                            });
                        } else {
                            toastr.error(
                                'An error occurred while creating the depreciation run.');
                        }
                    }
                });
            });

            // Post run
            $(document).on('click', '.post-run', function() {
                var runId = $(this).data('id');
                var period = $(this).data('period');

                if (confirm(`Are you sure you want to post the depreciation run for ${period}?`)) {
                    $.ajax({
                        url: `/assets/depreciation/${runId}/post`,
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            if (response.success) {
                                toastr.success(response.message);
                                table.ajax.reload();
                            }
                        },
                        error: function(xhr) {
                            if (xhr.status === 422) {
                                toastr.error(xhr.responseJSON.message);
                            } else {
                                toastr.error(
                                    'An error occurred while posting the depreciation run.');
                            }
                        }
                    });
                }
            });

            // Reverse run
            $(document).on('click', '.reverse-run', function() {
                var runId = $(this).data('id');
                var period = $(this).data('period');

                if (confirm(
                        `Are you sure you want to reverse the depreciation run for ${period}? This action cannot be undone.`
                        )) {
                    $.ajax({
                        url: `/assets/depreciation/${runId}/reverse`,
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            if (response.success) {
                                toastr.success(response.message);
                                table.ajax.reload();
                            }
                        },
                        error: function(xhr) {
                            if (xhr.status === 422) {
                                toastr.error(xhr.responseJSON.message);
                            } else {
                                toastr.error(
                                    'An error occurred while reversing the depreciation run.'
                                    );
                            }
                        }
                    });
                }
            });
        });
    </script>
@endsection
