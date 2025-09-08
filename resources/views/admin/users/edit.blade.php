@extends('layouts.main')

@section('title_page')
    Edit User
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Users</a></li>
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
                            <h3 class="card-title">User Information</h3>
                            <div class="card-tools">
                                <a href="{{ route('admin.users.index') }}" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-arrow-left"></i> Back to List
                                </a>
                            </div>
                        </div>
                        <form action="{{ route('admin.users.update', $user) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="name">Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                                id="name" name="name" value="{{ old('name', $user->name) }}"
                                                required>
                                            @error('name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="email">Email <span class="text-danger">*</span></label>
                                            <input type="email" class="form-control @error('email') is-invalid @enderror"
                                                id="email" name="email" value="{{ old('email', $user->email) }}"
                                                required>
                                            @error('email')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="nik">NIK</label>
                                            <input type="text" class="form-control @error('nik') is-invalid @enderror"
                                                id="nik" name="nik" value="{{ old('nik', $user->nik) }}">
                                            @error('nik')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="username">Username</label>
                                            <input type="text"
                                                class="form-control @error('username') is-invalid @enderror" id="username"
                                                name="username" value="{{ old('username', $user->username) }}">
                                            @error('username')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="project">Project</label>
                                            <select class="form-control @error('project') is-invalid @enderror"
                                                id="project" name="project">
                                                <option value="">Select Project</option>
                                                @foreach ($projects as $project)
                                                    <option value="{{ $project->code }}"
                                                        {{ old('project', $user->project) == $project->code ? 'selected' : '' }}>
                                                        {{ $project->code }} - {{ $project->owner }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('project')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="department_id">Department</label>
                                            <select class="form-control @error('department_id') is-invalid @enderror"
                                                id="department_id" name="department_id">
                                                <option value="">Select Department</option>
                                                @foreach ($departments as $department)
                                                    <option value="{{ $department->id }}"
                                                        {{ old('department_id', $user->department_id) == $department->id ? 'selected' : '' }}>
                                                        {{ $department->name }}
                                                        @if ($department->project)
                                                            - {{ $department->project }}
                                                        @endif
                                                        @if ($department->location_code)
                                                            ({{ $department->location_code }})
                                                        @endif
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('department_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="password">Password <small class="text-muted">(Leave blank to keep
                                                    current password)</small></label>
                                            <input type="password"
                                                class="form-control @error('password') is-invalid @enderror" id="password"
                                                name="password">
                                            @error('password')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="password_confirmation">Confirm Password</label>
                                            <input type="password" class="form-control" id="password_confirmation"
                                                name="password_confirmation">
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="is_active" name="is_active"
                                            value="1" {{ old('is_active', $user->is_active) ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="is_active">Active User</label>
                                    </div>
                                    @error('is_active')
                                        <div class="text-danger mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label>Roles</label>
                                    <div class="row">
                                        @foreach ($roles as $role)
                                            <div class="col-md-4">
                                                <div class="custom-control custom-checkbox">
                                                    <input type="checkbox" class="custom-control-input"
                                                        id="role_{{ $role->id }}" name="roles[]"
                                                        value="{{ $role->id }}"
                                                        {{ in_array($role->id, old('roles', $user->roles->pluck('id')->toArray())) ? 'checked' : '' }}>
                                                    <label class="custom-control-label" for="role_{{ $role->id }}">
                                                        {{ $role->name }}
                                                    </label>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                    @error('roles')
                                        <div class="text-danger mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update User
                                </button>
                                <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">
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
