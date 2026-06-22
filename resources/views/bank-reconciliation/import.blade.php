@extends('layouts.main')

@section('title_page')
    Import Bank Statement
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('bank-reconciliation.index') }}">Bank Reconciliation</a></li>
    <li class="breadcrumb-item active">Import</li>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title mb-0">Import Bank Statement PDF</h3>
        </div>
        <form method="POST" action="{{ route('bank-reconciliation.import.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="alert alert-info">
                    Upload a bank statement PDF (Mandiri, CIMB Niaga, etc.). Secured/encrypted PDFs from internet banking are supported via OpenRouter PDF parsing when local text extraction fails.
                </div>

                @if ($bankAccounts->isEmpty())
                    <div class="alert alert-warning">
                        No bank accounts are registered yet.
                        @can('bank_accounts.manage')
                            <a href="{{ route('bank-accounts.create') }}">Create a bank account</a>
                        @endcan
                        before importing, or the system will try to auto-create one from Chart of Accounts when the PDF account number matches a COA name.
                    </div>
                @endif

                <div class="form-group">
                    <label>Bank Account @if($bankAccounts->isEmpty())<span class="text-danger">*</span>@endif</label>
                    <select name="bank_account_id" class="form-control" @if($bankAccounts->isEmpty()) required @endif>
                        <option value="">Auto-detect from PDF / COA</option>
                        @foreach ($bankAccounts as $bankAccount)
                            <option value="{{ $bankAccount->id }}" @selected(old('bank_account_id') == $bankAccount->id)>
                                {{ $bankAccount->bank_name }} - {{ $bankAccount->account_number }} ({{ $bankAccount->name }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label>Statement PDF</label>
                    <input type="file" name="file" class="form-control-file" accept="application/pdf" required>
                </div>
            </div>
            <div class="card-footer">
                <button class="btn btn-primary">Import &amp; Start Reconciliation</button>
                <a href="{{ route('bank-reconciliation.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
@endsection
