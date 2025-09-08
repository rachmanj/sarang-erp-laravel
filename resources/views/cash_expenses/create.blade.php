@extends('layouts.main')

@section('title_page')
    Cash Expense
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('cash-expenses.index') }}">Cash Expenses</a></li>
    <li class="breadcrumb-item active">Create</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">New Cash Expense</h3>
                </div>
                <form method="post" action="{{ route('cash-expenses.store') }}">
                    @csrf
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group col-md-3"><label>Date</label><input type="date" name="date"
                                    value="{{ now()->toDateString() }}" class="form-control" required></div>
                            <div class="form-group col-md-5"><label>Description</label><input name="description"
                                    class="form-control"></div>
                            <div class="form-group col-md-2"><label>Amount</label><input type="number" step="0.01"
                                    min="0.01" name="amount" class="form-control" required></div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6"><label>Expense Account</label>
                                <select name="expense_account_id" class="form-control" required>
                                    @foreach ($expenseAccounts as $a)
                                        <option value="{{ $a->id }}">{{ $a->code }} - {{ $a->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-6"><label>Cash/Bank Account</label>
                                <select name="cash_account_id" class="form-control" required>
                                    @foreach ($cashAccounts as $a)
                                        <option value="{{ $a->id }}">{{ $a->code }} - {{ $a->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-4"><label>Project</label>
                                <select name="project_id" class="form-control">
                                    <option value="">-- none --</option>
                                    @foreach ($projects as $p)
                                        <option value="{{ $p->id }}">{{ $p->code }} - {{ $p->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-4"><label>Fund</label>
                                <select name="fund_id" class="form-control">
                                    <option value="">-- none --</option>
                                    @foreach ($funds as $f)
                                        <option value="{{ $f->id }}">{{ $f->code }} - {{ $f->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-4"><label>Department</label>
                                <select name="dept_id" class="form-control">
                                    <option value="">-- none --</option>
                                    @foreach ($departments as $d)
                                        <option value="{{ $d->id }}">{{ $d->code }} - {{ $d->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer"><button class="btn btn-sm btn-primary">Post Expense</button><a
                            href="{{ route('cash-expenses.index') }}" class="btn btn-sm btn-secondary ml-2">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
