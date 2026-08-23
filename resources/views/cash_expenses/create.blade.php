@extends('layouts.main')

@section('title_page')
    Pengeluaran Kas
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('cash-expenses.index') }}">Pengeluaran Kas</a></li>
    <li class="breadcrumb-item active">Buat Baru</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Pengeluaran Kas Baru</h3>
                </div>
                <form method="post" action="{{ route('cash-expenses.store') }}" id="cash-expense-form">
                    @csrf
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group col-md-3">
                                <label>Tanggal</label>
                                <input type="date" name="date" value="{{ old('date', now()->toDateString()) }}"
                                    class="form-control" required>
                            </div>
                            <div class="form-group col-md-5">
                                <label>Deskripsi</label>
                                <input name="description" class="form-control" placeholder="Deskripsi pengeluaran"
                                    value="{{ old('description') }}">
                            </div>
                            <div class="form-group col-md-4">
                                <label>Akun Kas/Bank</label>
                                <select name="cash_account_id" id="cash_account_id" class="form-control select2bs4"
                                    required>
                                    <option value="">-- Pilih Akun Kas/Bank --</option>
                                    @foreach ($cashAccounts as $a)
                                        <option value="{{ $a->id }}"
                                            {{ old('cash_account_id') == $a->id ? 'selected' : '' }}>
                                            {{ $a->code }} - {{ $a->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <hr>
                        <h5 class="mb-3">
                            Rincian Biaya
                            <span class="ml-3" style="font-weight: normal; font-size: 0.85rem;">
                                <input type="checkbox" id="toggle-dims">
                                <label for="toggle-dims" class="mb-0">Tampilkan kolom Project &amp; Dept</label>
                            </span>
                        </h5>

                        <div class="table-responsive">
                            <table class="table table-bordered table-sm dims-hidden" id="lines-table">
                                <thead>
                                    <tr>
                                        <th style="width: 22%">Akun Biaya <span class="text-danger">*</span></th>
                                        <th style="width: 14%">Nominal <span class="text-danger">*</span></th>
                                        <th style="width: 22%">Keterangan</th>
                                        <th style="width: 16%" class="dim-col">Project</th>
                                        <th style="width: 16%" class="dim-col">Dept</th>
                                        <th style="width: 5%"></th>
                                    </tr>
                                </thead>
                                <tbody id="lines-tbody">
                                </tbody>
                            </table>
                        </div>

                        <div class="text-right mt-2">
                            <strong>Total: <span id="lines-total">0</span></strong>
                        </div>

                        <button type="button" class="btn btn-sm btn-success" id="add-line-btn">
                            <i class="fas fa-plus"></i> Tambah Baris
                        </button>
                    </div>
                    <div class="card-footer">
                        <button class="btn btn-sm btn-primary">Posting Pengeluaran</button>
                        <a href="{{ route('cash-expenses.index') }}" class="btn btn-sm btn-secondary ml-2">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('styles')
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    <style>
        .dims-hidden .dim-col {
            display: none;
        }
    </style>
@endsection

@section('scripts')
    <script src="{{ asset('adminlte/plugins/select2/js/select2.full.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            const expenseAccounts = @json($expenseAccounts);
            const projects = @json($projects);
            const departments = @json($departments);
            let lineIndex = 0;

            function buildOptions(items, placeholder) {
                let html = `<option value="">${placeholder}</option>`;
                items.forEach(function(item) {
                    html += `<option value="${item.id}">${item.code} - ${item.name}</option>`;
                });
                return html;
            }

            function initSelect2($container) {
                $container.find('.select2bs4').select2({
                    theme: 'bootstrap4',
                    placeholder: function() {
                        return $(this).find('option:first').text();
                    },
                    allowClear: true,
                    width: '100%'
                });
            }

            function formatAmountInput($input) {
                let value = $input.val().replace(/[^\d.]/g, '');
                let parts = value.split('.');
                if (parts.length > 2) {
                    value = parts[0] + '.' + parts.slice(1).join('');
                }

                const $row = $input.closest('tr');
                $row.find('.line-amount-raw').val(value);

                if (value && value !== '') {
                    let number = parseFloat(value);
                    if (!isNaN(number)) {
                        let formatted = number.toLocaleString('en-US', {
                            minimumFractionDigits: 0,
                            maximumFractionDigits: 2
                        });
                        $input.val(formatted);
                    }
                }

                updateLinesTotal();
            }

            function updateLinesTotal() {
                let total = 0;
                $('#lines-tbody tr').each(function() {
                    const raw = parseFloat($(this).find('.line-amount-raw').val()) || 0;
                    total += raw;
                });

                $('#lines-total').text(total.toLocaleString('en-US', {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 2
                }));
            }

            function addLineRow() {
                const idx = lineIndex++;
                const rowHtml = `
                    <tr class="line-row">
                        <td>
                            <select name="lines[${idx}][expense_account_id]" class="form-control select2bs4 line-expense-account" required>
                                ${buildOptions(expenseAccounts, '-- Pilih Akun Biaya --')}
                            </select>
                        </td>
                        <td>
                            <input type="text" name="lines[${idx}][amount]" class="form-control line-amount" placeholder="0.00" required>
                            <input type="hidden" name="lines[${idx}][amount_raw]" class="line-amount-raw">
                        </td>
                        <td>
                            <input type="text" name="lines[${idx}][description]" class="form-control" placeholder="Keterangan baris">
                        </td>
                        <td class="dim-col">
                            <select name="lines[${idx}][project_id]" class="form-control select2bs4 line-project">
                                ${buildOptions(projects, '-- Pilih Project (Opsional) --')}
                            </select>
                        </td>
                        <td class="dim-col">
                            <select name="lines[${idx}][dept_id]" class="form-control select2bs4 line-dept">
                                ${buildOptions(departments, '-- Pilih Dept (Opsional) --')}
                            </select>
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-danger remove-line-btn" title="Hapus baris">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;

                const $row = $(rowHtml);
                $('#lines-tbody').append($row);
                initSelect2($row);
                updateRemoveButtons();
            }

            function updateRemoveButtons() {
                const rowCount = $('#lines-tbody tr').length;
                $('.remove-line-btn').prop('disabled', rowCount <= 1);
            }

            initSelect2($('#cash-expense-form'));

            addLineRow();
            addLineRow();

            $('#add-line-btn').on('click', function() {
                addLineRow();
            });

            $('#toggle-dims').on('change', function() {
                const show = $(this).is(':checked');
                $('#lines-table').toggleClass('dims-hidden', !show);
                if (show) {
                    $('#lines-table .line-project, #lines-table .line-dept').next('.select2-container').css('width', '100%');
                }
            });

            $(document).on('click', '.remove-line-btn', function() {
                if ($('#lines-tbody tr').length <= 1) {
                    return;
                }

                const $row = $(this).closest('tr');
                $row.find('.select2bs4').select2('destroy');
                $row.remove();
                updateRemoveButtons();
                updateLinesTotal();
            });

            $(document).on('input', '.line-amount', function() {
                formatAmountInput($(this));
            });

            $(document).on('keydown', '.line-amount', function(e) {
                if (e.key === 'Backspace' || e.key === 'Delete') {
                    const $input = $(this);
                    setTimeout(function() {
                        formatAmountInput($input);
                    }, 10);
                }
            });

            $('#cash-expense-form').on('submit', function() {
                $('#lines-tbody tr').each(function() {
                    const $row = $(this);
                    const raw = $row.find('.line-amount-raw').val();
                    $row.find('.line-amount').val(raw);
                });
            });
        });
    </script>
@endsection
