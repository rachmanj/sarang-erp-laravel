@extends('layouts.main')

@section('title', 'Approval Workflows Management')

@section('title_page')
    Approval Workflows Management
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Admin</a></li>
    <li class="breadcrumb-item active">Approval Workflows</li>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                            {{ session('success') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                            {{ session('error') }}
                        </div>
                    @endif

                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-check-double mr-1"></i>
                                Approval Workflows Management
                            </h3>
                            <div class="card-tools">
                                <a href="{{ route('admin.approval-workflows.create') }}" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus"></i>
                                    Add New Workflow
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered table-striped" id="tbl-workflows">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Document Type</th>
                                        <th>Workflow Name</th>
                                        <th>Steps</th>
                                        <th>Steps Count</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {{-- DataTables will populate this --}}
                                </tbody>
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
            $('#tbl-workflows').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('admin.approval-workflows.data') }}',
                columns: [{
                        data: 'id',
                        name: 'id',
                        orderable: true,
                        searchable: false
                    },
                    {
                        data: 'document_type_label',
                        name: 'document_type'
                    },
                    {
                        data: 'workflow_name',
                        name: 'workflow_name'
                    },
                    {
                        data: 'steps_preview',
                        name: 'steps_preview',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'steps_count',
                        name: 'steps_count',
                        searchable: false
                    },
                    {
                        data: 'is_active_label',
                        name: 'is_active',
                        searchable: false
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false
                    }
                ],
                "responsive": true,
                "lengthChange": false,
                "autoWidth": false
            });
        });
    </script>
@endpush
