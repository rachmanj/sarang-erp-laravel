@extends('layouts.main')

@section('title', 'Create Sales Invoice')

@section('title_page')
    Create Sales Invoice
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('sales-invoices.index') }}">Sales Invoices</a></li>
    <li class="breadcrumb-item active">Create</li>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $e)
                                    <li>{{ $e }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-file-invoice-dollar mr-1"></i>
                                New Sales Invoice
                            </h3>
                            <a href="{{ route('sales-invoices.index') }}" class="btn btn-sm btn-secondary float-right">
                                <i class="fas fa-arrow-left"></i> Back to Sales Invoices
                            </a>
                        </div>
                        <form method="post" action="{{ route('sales-invoices.store') }}">
                            @csrf
                            <div class="card-body pb-1">
                                @isset($sales_order_id)
                                    <input type="hidden" name="sales_order_id" value="{{ $sales_order_id }}" />
                                @endisset

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-3 col-form-label">Date <span
                                                    class="text-danger">*</span></label>
                                            <div class="col-sm-9">
                                                <div class="input-group input-group-sm">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><i
                                                                class="far fa-calendar-alt"></i></span>
                                                    </div>
                                                    <input type="date" name="date"
                                                        value="{{ old('date', now()->toDateString()) }}"
                                                        class="form-control" required>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-3 col-form-label">Company <span
                                                    class="text-danger">*</span></label>
                                            <div class="col-sm-9">
                                                <select name="company_entity_id" id="company_entity_id"
                                                    class="form-control form-control-sm select2bs4" required>
                                                    @foreach ($entities as $entity)
                                                        <option value="{{ $entity->id }}"
                                                            {{ old('company_entity_id', $defaultEntity->id) == $entity->id ? 'selected' : '' }}>
                                                            {{ $entity->name }} ({{ $entity->code }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-3 col-form-label">Customer <span
                                                    class="text-danger">*</span></label>
                                            <div class="col-sm-9">
                                                <select name="business_partner_id"
                                                    class="form-control form-control-sm select2bs4" required>
                                                    <option value="">-- select customer --</option>
                                                    @foreach ($customers as $c)
                                                        <option value="{{ $c->id }}"
                                                            {{ old('business_partner_id') == $c->id ? 'selected' : '' }}>
                                                            {{ $c->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-3 col-form-label">Terms (days)</label>
                                            <div class="col-sm-9">
                                                <input type="number" min="0" name="terms_days"
                                                    value="{{ old('terms_days', 30) }}"
                                                    class="form-control form-control-sm">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-3 col-form-label">Description</label>
                                            <div class="col-sm-9">
                                                <input type="text" name="description" value="{{ old('description') }}"
                                                    class="form-control form-control-sm" placeholder="Invoice description">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-3 col-form-label">Due Date</label>
                                            <div class="col-sm-9">
                                                <div class="input-group input-group-sm">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><i
                                                                class="far fa-calendar-alt"></i></span>
                                                    </div>
                                                    <input type="date" name="due_date" value="{{ old('due_date') }}"
                                                        class="form-control">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="card card-secondary card-outline mt-3 mb-2">
                                    <div class="card-header py-2">
                                        <h3 class="card-title">
                                            <i class="fas fa-list-ul mr-1"></i>
                                            Invoice Lines
                                        </h3>
                                        <button type="button" class="btn btn-xs btn-primary float-right"
                                            onclick="addLine()">
                                            <i class="fas fa-plus"></i> Add Line
                                        </button>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive">
                                            <table class="table table-sm table-striped mb-0" id="lines-table">
                                                <thead>
                                                    <tr>
                                                        <th style="width: 20%">Revenue Account <span
                                                                class="text-danger">*</span></th>
                                                        <th style="width: 20%">Description</th>
                                                        <th style="width: 10%">Qty <span class="text-danger">*</span></th>
                                                        <th style="width: 12%">Unit Price <span class="text-danger">*</span>
                                                        </th>
                                                        <th style="width: 10%">Tax</th>
                                                        <th style="width: 8%">Project</th>
                                                        <th style="width: 8%">Fund</th>
                                                        <th style="width: 8%">Dept</th>
                                                        <th style="width: 4%">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="lines">
                                                    <tr class="line-item">
                                                        <td>
                                                            <select name="lines[0][account_id]"
                                                                class="form-control form-control-sm select2bs4" required>
                                                                @foreach ($accounts as $a)
                                                                    <option value="{{ $a->id }}">
                                                                        {{ $a->code }} - {{ $a->name }}</option>
                                                                @endforeach
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <input type="text" name="lines[0][description]"
                                                                class="form-control form-control-sm"
                                                                placeholder="Description">
                                                        </td>
                                                        <td>
                                                            <input type="number" step="0.01" min="0.01"
                                                                name="lines[0][qty]"
                                                                class="form-control form-control-sm text-right qty-input"
                                                                value="1" required>
                                                        </td>
                                                        <td>
                                                            <input type="number" step="0.01" min="0"
                                                                name="lines[0][unit_price]"
                                                                class="form-control form-control-sm text-right price-input"
                                                                value="0" required>
                                                        </td>
                                                        <td>
                                                            <select name="lines[0][tax_code_id]"
                                                                class="form-control form-control-sm select2bs4">
                                                                <option value="">-- none --</option>
                                                                @foreach ($taxCodes as $t)
                                                                    <option value="{{ $t->id }}">
                                                                        {{ $t->code }}</option>
                                                                @endforeach
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <select name="lines[0][project_id]"
                                                                class="form-control form-control-sm select2bs4">
                                                                <option value="">-- none --</option>
                                                                @foreach ($projects as $p)
                                                                    <option value="{{ $p->id }}">
                                                                        {{ $p->code }}</option>
                                                                @endforeach
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <select name="lines[0][fund_id]"
                                                                class="form-control form-control-sm select2bs4">
                                                                <option value="">-- none --</option>
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <select name="lines[0][dept_id]"
                                                                class="form-control form-control-sm select2bs4">
                                                                <option value="">-- none --</option>
                                                                @foreach ($departments as $d)
                                                                    <option value="{{ $d->id }}">
                                                                        {{ $d->code }}</option>
                                                                @endforeach
                                                            </select>
                                                        </td>
                                                        <td class="text-center">
                                                            <button type="button" class="btn btn-xs btn-danger rm">
                                                                <i class="fas fa-trash-alt"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                                <tfoot>
                                                    <tr>
                                                        <th colspan="3" class="text-right">Total:</th>
                                                        <th class="text-right" id="total-amount">0.00</th>
                                                        <th colspan="5"></th>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <div class="row">
                                    <div class="col-md-6">
                                        <button class="btn btn-primary" type="submit">
                                            <i class="fas fa-save mr-1"></i> Save Invoice
                                        </button>
                                        <a href="{{ route('sales-invoices.index') }}" class="btn btn-default">
                                            <i class="fas fa-times mr-1"></i> Cancel
                                        </a>
                                    </div>
                                    <div class="col-md-6 text-right">
                                        <div class="text-muted">
                                            <small>* Required fields</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        let idx = 1;

        $(document).ready(function() {
            // Initialize Select2BS4 for all select elements
            $('.select2bs4').select2({
                theme: 'bootstrap4',
                placeholder: 'Select an option',
                allowClear: true
            });

            // Remove line
            $(document).on('click', '.rm', function() {
                $(this).closest('tr').remove();
                updateTotalAmount();
            });

            // Update total when unit price or quantity changes
            $(document).on('input', '.qty-input, .price-input', function() {
                updateTotalAmount();
            });

            updateTotalAmount();
        });

        function addLine() {
            const container = document.getElementById('lines');
            const row = document.createElement('tr');
            row.className = 'line-item';
            row.innerHTML = `
                <td>
                    <select name="lines[${idx}][account_id]" class="form-control form-control-sm select2bs4" required>
                        @foreach ($accounts as $a)
                            <option value="{{ $a->id }}">{{ $a->code }} - {{ $a->name }}</option>
                        @endforeach
                    </select>
                </td>
                <td>
                    <input type="text" name="lines[${idx}][description]" class="form-control form-control-sm" placeholder="Description">
                </td>
                <td>
                    <input type="number" step="0.01" min="0.01" name="lines[${idx}][qty]" 
                        class="form-control form-control-sm text-right qty-input" value="1" required>
                </td>
                <td>
                    <input type="number" step="0.01" min="0" name="lines[${idx}][unit_price]" 
                        class="form-control form-control-sm text-right price-input" value="0" required>
                </td>
                <td>
                    <select name="lines[${idx}][tax_code_id]" class="form-control form-control-sm select2bs4">
                        <option value="">-- none --</option>
                        @foreach ($taxCodes as $t)
                            <option value="{{ $t->id }}">{{ $t->code }}</option>
                        @endforeach
                    </select>
                </td>
                <td>
                    <select name="lines[${idx}][project_id]" class="form-control form-control-sm select2bs4">
                        <option value="">-- none --</option>
                        @foreach ($projects as $p)
                            <option value="{{ $p->id }}">{{ $p->code }}</option>
                        @endforeach
                    </select>
                </td>
                <td>
                    <select name="lines[${idx}][fund_id]" class="form-control form-control-sm select2bs4">
                        <option value="">-- none --</option>
                    </select>
                </td>
                <td>
                    <select name="lines[${idx}][dept_id]" class="form-control form-control-sm select2bs4">
                        <option value="">-- none --</option>
                        @foreach ($departments as $d)
                            <option value="{{ $d->id }}">{{ $d->code }}</option>
                        @endforeach
                    </select>
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-xs btn-danger rm">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </td>
            `;
            container.appendChild(row);

            // Initialize Select2BS4 for the newly added select elements
            $(row).find('.select2bs4').select2({
                theme: 'bootstrap4',
                placeholder: 'Select an option',
                allowClear: true
            });

            updateTotalAmount();
            idx++;
        }

        function updateTotalAmount() {
            let total = 0;

            // Calculate total from all line items
            $('#lines tr').each(function() {
                const qty = parseFloat($(this).find('.qty-input').val() || 0);
                const price = parseFloat($(this).find('.price-input').val() || 0);
                total += qty * price;
            });

            // Update total display with Indonesian number formatting
            $('#total-amount').text(total.toLocaleString('id-ID', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }));
        }
    </script>
@endpush
