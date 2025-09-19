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
                            <div class="form-group col-md-3">
                                <label>Date</label>
                                <input type="date" name="date" value="{{ now()->toDateString() }}"
                                    class="form-control" required>
                            </div>
                            <div class="form-group col-md-5">
                                <label>Description</label>
                                <input name="description" class="form-control" placeholder="Enter expense description">
                            </div>
                            <div class="form-group col-md-4">
                                <label>Amount</label>
                                <input type="text" name="amount" id="amount" class="form-control" placeholder="0.00"
                                    required>
                                <input type="hidden" name="amount_raw" id="amount_raw">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Expense Account</label>
                                <select name="expense_account_id" id="expense_account_id" class="form-control select2bs4"
                                    required>
                                    <option value="">-- Select Expense Account --</option>
                                    @foreach ($expenseAccounts as $a)
                                        <option value="{{ $a->id }}">{{ $a->code }} - {{ $a->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label>Cash/Bank Account</label>
                                <select name="cash_account_id" id="cash_account_id" class="form-control select2bs4"
                                    required>
                                    <option value="">-- Select Cash/Bank Account --</option>
                                    @foreach ($cashAccounts as $a)
                                        <option value="{{ $a->id }}">{{ $a->code }} - {{ $a->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label>Project</label>
                                <select name="project_id" id="project_id" class="form-control select2bs4">
                                    <option value="">-- Select Project (Optional) --</option>
                                    @foreach ($projects as $p)
                                        <option value="{{ $p->id }}">{{ $p->code }} - {{ $p->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-4">
                                <label>Fund</label>
                                <select name="fund_id" id="fund_id" class="form-control select2bs4">
                                    <option value="">-- Select Fund (Optional) --</option>
                                </select>
                            </div>
                            <div class="form-group col-md-4">
                                <label>Department</label>
                                <select name="dept_id" id="dept_id" class="form-control select2bs4">
                                    <option value="">-- Select Department (Optional) --</option>
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

@section('styles')
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
@endsection

@section('scripts')
    <script src="{{ asset('adminlte/plugins/select2/js/select2.full.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            // Initialize Select2BS4 for all select elements
            $('.select2bs4').select2({
                theme: 'bootstrap4',
                placeholder: function() {
                    return $(this).find('option:first').text();
                },
                allowClear: true,
                width: '100%'
            });

            // Auto thousand separator for amount input
            $('#amount').on('input', function() {
                let input = $(this);
                let value = input.val().replace(/[^\d.]/g,
                    ''); // Remove non-numeric characters except decimal point

                // Handle multiple decimal points
                let parts = value.split('.');
                if (parts.length > 2) {
                    value = parts[0] + '.' + parts.slice(1).join('');
                }

                // Store raw value for form submission
                $('#amount_raw').val(value);

                // Format with thousand separators for display
                if (value && value !== '') {
                    let number = parseFloat(value);
                    if (!isNaN(number)) {
                        let formatted = number.toLocaleString('en-US', {
                            minimumFractionDigits: 0,
                            maximumFractionDigits: 2
                        });
                        input.val(formatted);
                    }
                }
            });

            // Handle form submission - use raw value
            $('form').on('submit', function() {
                $('#amount').val($('#amount_raw').val());
            });

            // Handle backspace and delete keys
            $('#amount').on('keydown', function(e) {
                if (e.key === 'Backspace' || e.key === 'Delete') {
                    setTimeout(() => {
                        let input = $(this);
                        let value = input.val().replace(/[^\d.]/g, '');
                        $('#amount_raw').val(value);

                        if (value && value !== '') {
                            let number = parseFloat(value);
                            if (!isNaN(number)) {
                                let formatted = number.toLocaleString('en-US', {
                                    minimumFractionDigits: 0,
                                    maximumFractionDigits: 2
                                });
                                input.val(formatted);
                            }
                        }
                    }, 10);
                }
            });
        });
    </script>
@endsection
