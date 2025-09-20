@extends('layouts.main')

@section('title_page')
    Edit Role
@endsection

@section('styles')
    <style>
        .permission-group {
            border: 1px solid #e3e6f0;
            border-radius: 8px;
            padding: 15px;
            background-color: #f8f9fc;
        }

        .permission-group h6 {
            border-bottom: 2px solid #4e73df;
            padding-bottom: 8px;
            margin-bottom: 15px;
        }

        .permission-group .custom-control-label {
            font-size: 0.9rem;
            color: #5a5c69;
        }

        .permissions-container {
            max-height: 600px;
            overflow-y: auto;
            border: 1px solid #e3e6f0;
            border-radius: 8px;
            padding: 15px;
            background-color: #ffffff;
        }
    </style>
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.roles.index') }}">Roles</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection

@section('content')
    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Role Information</h3>
                            <div class="card-tools">
                                <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-arrow-left"></i> Back to List
                                </a>
                            </div>
                        </div>
                        <form action="{{ route('admin.roles.update', $role) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="name">Role Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                                        id="name" name="name" value="{{ old('name', $role->name) }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label>Permissions</label>
                                    <div class="permissions-container">
                                        @foreach ($groupedPermissions as $groupName => $groupPermissions)
                                            <div class="permission-group mb-4">
                                                <h6 class="text-primary font-weight-bold mb-3">
                                                    <i class="fas fa-folder"></i> {{ $groupName }}
                                                </h6>
                                                <div class="row">
                                                    @foreach ($groupPermissions as $permission)
                                                        <div class="col-md-4 col-lg-3">
                                                            <div class="custom-control custom-checkbox">
                                                                <input type="checkbox" class="custom-control-input"
                                                                    id="permission_{{ $permission->id }}"
                                                                    name="permissions[]" value="{{ $permission->id }}"
                                                                    {{ in_array($permission->id, old('permissions', $role->permissions->pluck('id')->toArray())) ? 'checked' : '' }}>
                                                                <label class="custom-control-label"
                                                                    for="permission_{{ $permission->id }}">
                                                                    {{ ucfirst(str_replace('-', ' ', $permission->name)) }}
                                                                </label>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                    @error('permissions')
                                        <div class="text-danger mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update Role
                                </button>
                                <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
