@extends('layouts.main')

@section('title', 'Create Sales Invoice')

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
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">New Invoice</h3>
                        </div>
                        <form method="post" action="{{ route('sales-invoices.store') }}">
                            @csrf
                            <div class="card-body">
                                @isset($sales_order_id)
                                    <input type="hidden" name="sales_order_id" value="{{ $sales_order_id }}" />
                                @endisset
                                <div class="form-group">
                                    <label>Date</label>
                                    <input type="date" name="date" value="{{ old('date', now()->toDateString()) }}"
                                        class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>Customer</label>
                                    <select name="customer_id" class="form-control" required>
                                        <option value="">-- select --</option>
                                        @foreach ($customers as $c)
                                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Description</label>
                                    <input type="text" name="description" value="{{ old('description') }}"
                                        class="form-control">
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label>Terms (days)</label>
                                        <input type="number" min="0" name="terms_days"
                                            value="{{ old('terms_days', 30) }}" class="form-control">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label>Due Date (optional)</label>
                                        <input type="date" name="due_date" value="{{ old('due_date') }}"
                                            class="form-control">
                                    </div>
                                </div>
                                <hr>
                                <h5>Lines</h5>
                                <div id="lines">
                                    <div class="line-item row mb-2">
                                        <div class="col-md-3">
                                            <label>Revenue Account</label>
                                            <select name="lines[0][account_id]" class="form-control" required>
                                                @foreach ($accounts as $a)
                                                    <option value="{{ $a->id }}">{{ $a->code }} -
                                                        {{ $a->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label>Description</label>
                                            <input type="text" name="lines[0][description]" class="form-control">
                                        </div>
                                        <div class="col-md-2">
                                            <label>Qty</label>
                                            <input type="number" step="0.01" min="0.01" name="lines[0][qty]"
                                                class="form-control" value="1">
                                        </div>
                                        <div class="col-md-2">
                                            <label>Unit Price</label>
                                            <input type="number" step="0.01" min="0" name="lines[0][unit_price]"
                                                class="form-control" value="0">
                                        </div>
                                        <div class="col-md-2">
                                            <label>Tax Code</label>
                                            <select name="lines[0][tax_code_id]" class="form-control">
                                                <option value="">-- none --</option>
                                                @foreach ($taxCodes as $t)
                                                    <option value="{{ $t->id }}">{{ $t->code }}
                                                        ({{ $t->rate }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-2 mt-2">
                                            <label>Project</label>
                                            <select name="lines[0][project_id]" class="form-control">
                                                <option value="">-- none --</option>
                                                @foreach ($projects as $p)
                                                    <option value="{{ $p->id }}">{{ $p->code }} -
                                                        {{ $p->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-2 mt-2">
                                            <label>Fund</label>
                                            <select name="lines[0][fund_id]" class="form-control">
                                                <option value="">-- none --</option>
                                                @foreach ($funds as $f)
                                                    <option value="{{ $f->id }}">{{ $f->code }} -
                                                        {{ $f->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-2 mt-2">
                                            <label>Department</label>
                                            <select name="lines[0][dept_id]" class="form-control">
                                                <option value="">-- none --</option>
                                                @foreach ($departments as $d)
                                                    <option value="{{ $d->id }}">{{ $d->code }} -
                                                        {{ $d->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-secondary" onclick="addLine()">Add
                                    Line</button>
                            </div>
                            <div class="card-footer">
                                <button class="btn btn-primary" type="submit">Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('scripts')
    <script>
        let idx = 1;

        function addLine() {
            const container = document.getElementById('lines');
            const row = document.createElement('div');
            row.className = 'line-item row mb-2';
            row.innerHTML = `
        <div class="col-md-3">
            <select name="lines[${idx}][account_id]" class="form-control" required>
                ${@json($accounts).map(a => `<option value="${a.id}">${a.code} - ${a.name}</option>`).join('')}
            </select>
        </div>
        <div class="col-md-3">
            <input type="text" name="lines[${idx}][description]" class="form-control" placeholder="Description">
        </div>
        <div class="col-md-2">
            <input type="number" step="0.01" min="0.01" name="lines[${idx}][qty]" class="form-control" value="1">
        </div>
        <div class="col-md-2">
            <input type="number" step="0.01" min="0" name="lines[${idx}][unit_price]" class="form-control" value="0">
        </div>
        <div class="col-md-2">
            <select name="lines[${idx}][tax_code_id]" class="form-control">
                <option value="">-- none --</option>
                ${@json($taxCodes).map(t => `<option value="${t.id}">${t.code} (${t.rate})</option>`).join('')}
            </select>
        </div>
        <div class=\"col-md-2 mt-2\">
            <select name="lines[${idx}][project_id]" class="form-control">
                <option value=\"\">-- project --</option>
                ${@json($projects).map(p => `<option value=\\\"${p.id}\\\">${p.code} - ${p.name}</option>`).join('')}
            </select>
        </div>
        <div class=\"col-md-2 mt-2\">
            <select name="lines[${idx}][fund_id]" class="form-control">
                <option value=\"\">-- fund --</option>
                ${@json($funds).map(f => `<option value=\\\"${f.id}\\\">${f.code} - ${f.name}</option>`).join('')}
            </select>
        </div>
        <div class=\"col-md-2 mt-2\">
            <select name="lines[${idx}][dept_id]" class="form-control">
                <option value=\"\">-- department --</option>
                ${@json($departments).map(d => `<option value=\\\"${d.id}\\\">${d.code} - ${d.name}</option>`).join('')}
            </select>
        </div>`;
            container.appendChild(row);
            idx++;
        }
    </script>
@endsection
