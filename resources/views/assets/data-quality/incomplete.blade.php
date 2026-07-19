@extends('layouts.main')

@section('title', 'Incomplete Assets')

@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Incomplete Assets</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('assets.index') }}">Assets</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('assets.data-quality.index') }}">Data Quality</a>
                        </li>
                        <li class="breadcrumb-item active">Incomplete</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Assets With Incomplete Data</h3>
                    <div class="card-tools">
                        <a href="{{ route('assets.data-quality.index') }}" class="btn btn-sm btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Missing Fields</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($incompleteAssets as $asset)
                                    @php
                                        $missing = [];
                                        if (blank($asset->description)) {
                                            $missing[] = 'Description';
                                        }
                                        if (blank($asset->serial_number)) {
                                            $missing[] = 'Serial';
                                        }
                                        if (!$asset->business_partner_id) {
                                            $missing[] = 'Vendor';
                                        }
                                        if (!$asset->project_id) {
                                            $missing[] = 'Project';
                                        }
                                        if (!$asset->department_id) {
                                            $missing[] = 'Department';
                                        }
                                        if (!$asset->placed_in_service_date) {
                                            $missing[] = 'Service Date';
                                        }
                                    @endphp
                                    <tr>
                                        <td>{{ $asset->code }}</td>
                                        <td>{{ $asset->name }}</td>
                                        <td>{{ $asset->category->name ?? '-' }}</td>
                                        <td>
                                            @foreach ($missing as $field)
                                                <span class="badge badge-warning mr-1">{{ $field }}</span>
                                            @endforeach
                                        </td>
                                        <td>
                                            <a href="{{ route('assets.show', $asset) }}"
                                                class="btn btn-info btn-sm mr-1 mb-1" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            @can('assets.update')
                                                <a href="{{ route('assets.edit', $asset) }}"
                                                    class="btn btn-warning btn-sm mb-1" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-muted text-center p-3">No incomplete assets found
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
