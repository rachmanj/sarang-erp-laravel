@extends('layouts.main')

@section('title_page')
    New Bank Reconciliation
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('bank-reconciliation.index') }}">Bank Reconciliation</a></li>
    <li class="breadcrumb-item active">New Session</li>
@endsection

@section('content')
    <div class="card card-outline card-primary">
        <div class="card-header">
            <h4 class="card-title mb-0">Create Reconciliation Session</h4>
        </div>
        <form method="POST" action="{{ route('bank-reconciliation.store') }}" enctype="multipart/form-data">
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

                <div class="form-group">
                    <label>Bank Account</label>
                    <select name="bank_account_id" class="form-control" required>
                        <option value="">Select bank account</option>
                        @foreach ($bankAccounts as $account)
                            <option value="{{ $account->id }}" @selected(old('bank_account_id') == $account->id)>
                                {{ $account->name }} ({{ $account->account_number }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label>Period (month)</label>
                    <input type="month" name="periode" class="form-control" value="{{ old('periode', now()->format('Y-m')) }}" required>
                </div>

                <div class="form-group">
                    <label>Source Mode</label>
                    <select name="source_mode" id="source-mode" class="form-control" required>
                        <option value="ai" @selected(old('source_mode', 'ai') === 'ai')>AI — Upload PDF Rekening Koran</option>
                        <option value="manual" @selected(old('source_mode') === 'manual')>Manual — Enter bank lines manually</option>
                    </select>
                </div>

                <div class="form-group" id="pdf-upload-group">
                    <label>Bank Statement PDF</label>
                    <input type="file" name="file" class="form-control-file" accept="application/pdf">
                    <small class="text-muted">Required for AI mode. Max 10 MB.</small>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Create Session</button>
                <a href="{{ route('bank-reconciliation.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        $(function() {
            function togglePdf() {
                const isAi = $('#source-mode').val() === 'ai';
                $('#pdf-upload-group').toggle(isAi);
                $('#pdf-upload-group input').prop('required', isAi);
            }

            $('#source-mode').on('change', togglePdf);
            togglePdf();
        });
    </script>
@endpush
