@extends('layouts.main')

@section('title_page')
    Account
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('accounts.index') }}">Accounts</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection

@section('content')
    <div class="container-fluid">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Edit Account</h3>
            </div>
            <form method="POST" action="{{ route('accounts.update', $account->id) }}">
                @csrf
                @method('PATCH')
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $e)
                                    <li>{{ $e }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Code</label>
                            <input type="text" name="code" class="form-control" value="{{ $account->code }}"
                                required />
                        </div>
                        <div class="form-group col-md-8">
                            <label>Name</label>
                            <input type="text" name="name" class="form-control" value="{{ $account->name }}"
                                required />
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Type</label>
                            <select name="type" class="form-control" required>
                                <option value="asset" {{ $account->type === 'asset' ? 'selected' : '' }}>Asset</option>
                                <option value="liability" {{ $account->type === 'liability' ? 'selected' : '' }}>Liability
                                </option>
                                <option value="net_assets" {{ $account->type === 'net_assets' ? 'selected' : '' }}>Net
                                    Assets</option>
                                <option value="income" {{ $account->type === 'income' ? 'selected' : '' }}>Income</option>
                                <option value="expense" {{ $account->type === 'expense' ? 'selected' : '' }}>Expense
                                </option>
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Postable</label>
                            <select name="is_postable" class="form-control" required>
                                <option value="1" {{ $account->is_postable ? 'selected' : '' }}>Yes</option>
                                <option value="0" {{ !$account->is_postable ? 'selected' : '' }}>No</option>
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Parent</label>
                            <select name="parent_id" class="form-control">
                                <option value="">(none)</option>
                                @foreach ($parents as $p)
                                    <option value="{{ $p->id }}"
                                        {{ $account->parent_id === $p->id ? 'selected' : '' }}>
                                        {{ $p->code }} - {{ $p->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button class="btn btn-primary">Save</button>
                    <a href="{{ route('accounts.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
