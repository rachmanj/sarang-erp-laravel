@extends('layouts.main')

@section('title_page')
    Change Password
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">Change Password</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Update Your Password</h3>
                </div>
                @if (session('success'))
                    <script>
                        toastr.success(@json(session('success')));
                    </script>
                @endif
                <form method="POST" action="{{ route('password.update') }}">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        @if (session('status') === 'password-updated')
                            <div class="alert alert-success">Password updated.</div>
                        @endif
                        @if ($errors->updatePassword->any())
                            <div class="alert alert-danger">Please fix the errors below.</div>
                            <script>
                                toastr.error('Failed to update password');
                            </script>
                        @endif
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password"
                                class="form-control @error('updatePassword.current_password') is-invalid @enderror"
                                id="current_password" name="current_password" autocomplete="current-password">
                            @error('updatePassword.current_password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="password">New Password</label>
                            <input type="password"
                                class="form-control @error('updatePassword.password') is-invalid @enderror" id="password"
                                name="password" autocomplete="new-password">
                            @error('updatePassword.password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="password_confirmation">Confirm Password</label>
                            <input type="password" class="form-control" id="password_confirmation"
                                name="password_confirmation" autocomplete="new-password">
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">Save</button>
                        <a href="{{ route('dashboard') }}" class="btn btn-secondary ml-2">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
