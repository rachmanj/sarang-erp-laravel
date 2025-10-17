@extends('layouts.main')

@section('title_page')
    Journal
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">Journal</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title">Create Journal Entry</h3>
                </div>
                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success alert-dismissible fade show">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                            {{ session('status') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('journals.manual.store') }}" id="journalForm">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="date">Transaction Date</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="far fa-calendar-alt"></i></span>
                                        </div>
                                        <input class="form-control" type="date" name="date" id="date"
                                            value="{{ now()->toDateString() }}" />
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="description">Description</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-file-alt"></i></span>
                                        </div>
                                        <input class="form-control" type="text" name="description" id="description"
                                            placeholder="Enter journal description" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="journal-lines-container mt-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5>Journal Lines</h5>
                                <button type="button" class="btn btn-sm btn-success" onclick="addLine()">
                                    <i class="fas fa-plus"></i> Add Line
                                </button>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" id="lines">
                                    <thead class="thead-light">
                                        <tr>
                                            <th width="20%">Account</th>
                                            <th width="10%">Currency</th>
                                            <th width="8%">Rate</th>
                                            <th width="10%">Debit</th>
                                            <th width="10%">Credit</th>
                                            <th width="8%">Debit FC</th>
                                            <th width="8%">Credit FC</th>
                                            <th width="8%">Project</th>
                                            <th width="8%">Dept</th>
                                            <th>Memo</th>
                                            <th width="5%">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                    <tfoot>
                                        <tr class="table-active">
                                            <th class="text-right">Totals:</th>
                                            <th colspan="2"></th>
                                            <th id="td" class="text-right">0.00</th>
                                            <th id="tc" class="text-right">0.00</th>
                                            <th id="tdf" class="text-right">0.00</th>
                                            <th id="tcf" class="text-right">0.00</th>
                                            <th colspan="4"></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-md-6">
                                <div id="balance-indicator" class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle"></i> Journal is not balanced. Difference: <strong
                                        id="diff">0.00</strong>
                                </div>
                            </div>
                            <div class="col-md-6 text-right">
                                <a href="{{ route('journals.index') }}" class="btn btn-default">Cancel</a>
                                <button type="submit" class="btn btn-primary" id="btnPost" disabled>
                                    <i class="fas fa-save"></i> Post Journal
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(function() {
            // Format number inputs with thousand separators
            $('input[type="number"]').on('input', function() {
                formatNumberInput(this);
            });

            // Update balance indicator based on initial state
            updateBalanceIndicator();
        });

        const accounts = @json($accounts);
        const projects = @json($projects);
        const departments = @json($departments);
        const currencies = @json($currencies);

        function accountSelectHtml(name) {
            let html = `<select name="${name}" class="form-control select2bs4" style="width: 100%;">`;
            accounts.forEach(a => {
                html += `<option value="${a.id}">${a.code} - ${a.name}</option>`
            });
            html += `</select>`;
            return html;
        }

        function dimSelectHtml(list, name, placeholder) {
            let html = `<select name="${name}" class="form-control select2bs4" style="width: 100%;">`;
            html += `<option value="">${placeholder}</option>`;
            list.forEach(x => {
                html += `<option value="${x.id}">${x.code} - ${x.name}</option>`
            });
            html += `</select>`;
            return html;
        }

        function currencySelectHtml(name) {
            let html = `<select name="${name}" class="form-control select2bs4 currency-select" style="width: 100%;">`;
            html += `<option value="">Base Currency</option>`;
            currencies.forEach(c => {
                html += `<option value="${c.id}" data-code="${c.code}">${c.code} - ${c.name}</option>`
            });
            html += `</select>`;
            return html;
        }

        function addLine() {
            const tbody = document.querySelector('#lines tbody');
            const idx = tbody.children.length;
            const tr = document.createElement('tr');
            tr.innerHTML = `
            <td>${accountSelectHtml(`lines[${idx}][account_id]`)}</td>
            <td>${currencySelectHtml(`lines[${idx}][currency_id]`)}</td>
            <td><input type="number" step="0.000001" min="0" name="lines[${idx}][exchange_rate]" class="form-control text-right exchange-rate" placeholder="1.000000" /></td>
            <td><input type="number" step="0.01" min="0" name="lines[${idx}][debit]" class="form-control text-right debit-amount" /></td>
            <td><input type="number" step="0.01" min="0" name="lines[${idx}][credit]" class="form-control text-right credit-amount" /></td>
            <td><input type="number" step="0.01" min="0" name="lines[${idx}][debit_foreign]" class="form-control text-right debit-foreign" readonly /></td>
            <td><input type="number" step="0.01" min="0" name="lines[${idx}][credit_foreign]" class="form-control text-right credit-foreign" readonly /></td>
            <td>${dimSelectHtml(projects, `lines[${idx}][project_id]`, '-- project --')}</td>
            <td>${dimSelectHtml(departments, `lines[${idx}][dept_id]`, '-- dept --')}</td>
            <td><input type="text" name="lines[${idx}][memo]" class="form-control" /></td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-danger" onclick="removeLine(this)">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
            tbody.appendChild(tr);

            // Initialize Select2 for the new row
            setTimeout(() => {
                $(tr).find('.select2bs4').select2({
                    theme: 'bootstrap4',
                    width: '100%'
                });

                // Add currency change event listener
                $(tr).find('.currency-select').on('change', function() {
                    updateExchangeRateForRow(tr, idx);
                });

                console.log('Select2 initialized for row', idx);
            }, 10);

            // Add event listeners for the new inputs
            tr.querySelectorAll('input').forEach(i => {
                i.addEventListener('input', recalc);
                if (i.type === 'number') {
                    i.addEventListener('input', function() {
                        formatNumberInput(this);
                        updateForeignAmounts(tr);
                    });
                }
            });

            recalc();
        }

        function removeLine(btn) {
            // Destroy Select2 before removing the row
            $(btn).closest('tr').find('.select2bs4').select2('destroy');
            $(btn).closest('tr').remove();
            recalc();
        }

        function formatNumberInput(input) {
            // Keep the original value for calculations
            input.dataset.value = input.value;
        }

        function recalc() {
            let td = 0,
                tc = 0,
                tdf = 0,
                tcf = 0;

            document.querySelectorAll('#lines tbody input[name$="[debit]"]').forEach(i => {
                td += parseFloat(i.value || 0);
            });

            document.querySelectorAll('#lines tbody input[name$="[credit]"]').forEach(i => {
                tc += parseFloat(i.value || 0);
            });

            document.querySelectorAll('#lines tbody input[name$="[debit_foreign]"]').forEach(i => {
                tdf += parseFloat(i.value || 0);
            });

            document.querySelectorAll('#lines tbody input[name$="[credit_foreign]"]').forEach(i => {
                tcf += parseFloat(i.value || 0);
            });

            const diff = (td - tc);

            // Safe element access with null checks
            const tdEl = document.getElementById('td');
            const tcEl = document.getElementById('tc');
            const tdfEl = document.getElementById('tdf');
            const tcfEl = document.getElementById('tcf');
            const diffEl = document.getElementById('diff');
            const btnPostEl = document.getElementById('btnPost');

            if (tdEl) tdEl.innerText = td.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
            if (tcEl) tcEl.innerText = tc.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
            if (tdfEl) tdfEl.innerText = tdf.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
            if (tcfEl) tcfEl.innerText = tcf.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
            if (diffEl) diffEl.innerText = Math.abs(diff).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
            if (btnPostEl) btnPostEl.disabled = Math.abs(diff) > 0.005 || td + tc === 0;

            updateBalanceIndicator(diff);
        }

        function updateExchangeRateForRow(row, idx) {
            const currencySelect = $(row).find('.currency-select');
            const exchangeRateInput = $(row).find('.exchange-rate');
            const dateInput = document.getElementById('date').value;

            const currencyId = currencySelect.val();

            if (!currencyId) {
                exchangeRateInput.val('1.000000');
                return;
            }

            // Fetch exchange rate from server
            $.get('{{ route('journals.api.exchange-rate') }}', {
                currency_id: currencyId,
                date: dateInput
            }).done(function(response) {
                if (response.rate) {
                    exchangeRateInput.val(response.rate);
                    updateForeignAmounts(row);
                } else {
                    alert('Error: ' + response.error);
                }
            }).fail(function() {
                alert('Error fetching exchange rate');
            });
        }

        function updateForeignAmounts(row) {
            const exchangeRate = parseFloat($(row).find('.exchange-rate').val()) || 1;
            const debitAmount = parseFloat($(row).find('.debit-amount').val()) || 0;
            const creditAmount = parseFloat($(row).find('.credit-amount').val()) || 0;

            const debitForeign = debitAmount / exchangeRate;
            const creditForeign = creditAmount / exchangeRate;

            $(row).find('.debit-foreign').val(debitForeign.toFixed(2));
            $(row).find('.credit-foreign').val(creditForeign.toFixed(2));
        }

        function updateBalanceIndicator(diff = null) {
            const indicator = document.getElementById('balance-indicator');
            if (!indicator) return;

            if (diff === null) {
                const diffEl = document.getElementById('diff');
                diff = diffEl ? parseFloat(diffEl.innerText.replace(/,/g, '') || 0) : 0;
            }

            if (Math.abs(diff) <= 0.005 && document.querySelectorAll('#lines tbody tr').length > 0) {
                indicator.className = 'alert alert-success';
                indicator.innerHTML = '<i class="fas fa-check-circle"></i> Journal is balanced';
            } else {
                indicator.className = 'alert alert-warning';
                indicator.innerHTML =
                    '<i class="fas fa-exclamation-triangle"></i> Journal is not balanced. Difference: <strong id="diff">' +
                    Math.abs(diff).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",") + '</strong>';
            }
        }

        // Initialize with one line after document is ready
        $(document).ready(function() {
            addLine();
        });
    </script>
@endsection
