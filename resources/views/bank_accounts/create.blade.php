@extends('layouts.main')

@section('title_page')
    Create Bank Account
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('bank-accounts.index') }}">Bank Accounts</a></li>
    <li class="breadcrumb-item active">Create</li>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title mb-0">Create Bank Account</h3>
        </div>
        <form method="POST" action="{{ route('bank-accounts.store') }}">
            @csrf
            <div class="card-body">
                @include('bank_accounts.partials.form')
            </div>
            <div class="card-footer">
                <button class="btn btn-primary">Save</button>
                <a href="{{ route('bank-accounts.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
@endsection
