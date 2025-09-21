@extends('layouts.main')

@section('title', 'Role Details')

@section('title_page')
    Role Details
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Admin</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.roles.index') }}">Roles</a></li>
    <li class="breadcrumb-item active">{{ $role->name }}</li>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-user-shield mr-1"></i>
                                {{ ucfirst($role->name) }} Role
                            </h3>
                            <div class="card-tools">
                                @can('roles.update')
                                    <a href="{{ route('admin.roles.edit', $role->id) }}" class="btn btn-tool btn-sm">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                @endcan
                                @can('roles.delete')
                                    @if ($role->name !== 'superadmin')
                                        <form action="{{ route('admin.roles.destroy', $role->id) }}" method="POST"
                                            class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-tool btn-sm text-danger"
                                                onclick="return confirm('Are you sure you want to delete this role?')">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    @endif
                                @endcan
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <dl class="row">
                                        <dt class="col-sm-4">Role ID:</dt>
                                        <dd class="col-sm-8">{{ $role->id }}</dd>

                                        <dt class="col-sm-4">Role Name:</dt>
                                        <dd class="col-sm-8">
                                            <span class="badge badge-primary">{{ $role->name }}</span>
                                        </dd>

                                        <dt class="col-sm-4">Users Count:</dt>
                                        <dd class="col-sm-8">
                                            <span class="badge badge-success">{{ $role->users->count() }}</span>
                                        </dd>

                                        <dt class="col-sm-4">Permissions Count:</dt>
                                        <dd class="col-sm-8">
                                            <span class="badge badge-info">{{ $role->permissions->count() }}</span>
                                        </dd>
                                    </dl>
                                </div>
                                <div class="col-md-6">
                                    <h5>Users with this Role</h5>
                                    @if ($role->users->count() > 0)
                                        <div class="list-group">
                                            @foreach ($role->users as $user)
                                                <div class="list-group-item">
                                                    <div class="d-flex w-100 justify-content-between">
                                                        <h6 class="mb-1">{{ $user->name }}</h6>
                                                        <small>{{ $user->email }}</small>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <p class="text-muted">No users assigned to this role.</p>
                                    @endif
                                </div>
                            </div>

                            <hr>

                            <h5>Permissions</h5>
                            @if ($role->permissions->count() > 0)
                                <div class="row">
                                    @foreach ($role->permissions as $permission)
                                        <div class="col-md-3 col-sm-4 col-6 mb-2">
                                            <span class="badge badge-info">{{ $permission->name }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-muted">No permissions assigned to this role.</p>
                            @endif
                        </div>
                        <div class="card-footer">
                            <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Roles
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
