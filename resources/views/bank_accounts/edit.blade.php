@extends('layouts.main')

@section('title_page')
    Edit Bank Account
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('bank-accounts.index') }}">Bank Accounts</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title mb-0">Edit Bank Account</h3>
        </div>
        <form method="POST" action="{{ route('bank-accounts.update', $bankAccount) }}">
            @csrf
            @method('PUT')
            <div class="card-body">
                @include('bank_accounts.partials.form', ['bankAccount' => $bankAccount])
            </div>
            <div class="card-footer">
                <button class="btn btn-primary">Save</button>
                <a href="{{ route('bank-accounts.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
@endsection
