@extends('layouts.main')

@section('title_page')
    Journals
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">Journals</li>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Journals</h3>
                            <div class="card-tools">
                                <a href="{{ route('journals.manual.create') }}" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus"></i> Manual Journal
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <div class="card card-outline card-info search-card">
                                        <div class="card-header">
                                            <h3 class="card-title"><i class="fas fa-search"></i> Advanced Search</h3>
                                            <div class="card-tools">
                                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <label>Date From</label>
                                                    <input type="date" id="filter_from" class="form-control" />
                                                </div>
                                                <div class="col-md-3">
                                                    <label>Date To</label>
                                                    <input type="date" id="filter_to" class="form-control" />
                                                </div>
                                                <div class="col-md-4">
                                                    <label>Description</label>
                                                    <input type="text" id="filter_desc" class="form-control"
                                                        placeholder="Search description" />
                                                </div>
                                                <div class="col-md-2 d-flex align-items-end justify-content-end">
                                                    <button class="btn btn-info mr-2" id="apply_search"><i
                                                            class="fas fa-search"></i> Apply</button>
                                                    <button class="btn btn-secondary" id="clear_search"><i
                                                            class="fas fa-times"></i> Clear</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            @if (session('success'))
                                <script>
                                    toastr.success(@json(session('success')));
                                </script>
                            @endif

                            <table class="table table-bordered table-striped table-sm" id="journals-table">
                                <thead>
                                    <tr>
                                        <th style="width:160px;">Journal No</th>
                                        <th>Date</th>
                                        <th>Description</th>
                                        <th class="text-right">Debit</th>
                                        <th class="text-right">Credit</th>
                                        <th style="width:100px;"></th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
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
            var table = $('#journals-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('journals.data') }}',
                    data: function(d) {
                        d.from = $('#filter_from').val();
                        d.to = $('#filter_to').val();
                        d.desc = $('#filter_desc').val();
                    }
                },
                columns: [{
                        data: 'journal_no',
                        name: 'j.journal_no'
                    },
                    {
                        data: 'date',
                        name: 'j.date'
                    },
                    {
                        data: 'description',
                        name: 'j.description'
                    },
                    {
                        data: 'debit',
                        name: 'debit',
                        className: 'text-right',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'credit',
                        name: 'credit',
                        className: 'text-right',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false
                    }
                ],
                order: [
                    [1, 'desc'],
                    [0, 'desc']
                ],
                pageLength: 25,
                responsive: true
            });

            $('#apply_search').on('click', function() {
                table.ajax.reload();
            });
            $('#clear_search').on('click', function() {
                $('#filter_from').val('');
                $('#filter_to').val('');
                $('#filter_desc').val('');
                table.ajax.reload();
            });

            $('#journals-table').on('click', '.reverse-button', function() {
                var url = $(this).data('url');
                Swal.fire({
                    title: 'Reverse Journal?',
                    text: 'This will post a full reversal.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, reverse',
                    cancelButtonText: 'Cancel'
                }).then((res) => {
                    if (res.isConfirmed) {
                        var form = $('<form method="POST" action="' + url + '">@csrf</form>');
                        $('body').append(form);
                        form.submit();
                    }
                });
            });
        });
    </script>
@endpush
