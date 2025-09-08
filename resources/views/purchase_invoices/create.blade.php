@extends('layouts.main')

@section('title', 'Create Purchase Invoice')

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
                            <h3 class="card-title">New Purchase Invoice</h3>
                        </div>
                        <form method="post" action="{{ route('purchase-invoices.store') }}">
                            @csrf
                            <div class="card-body">
                                <div class="form-group">
                                    <label>Date</label>
                                    <input type="date" name="date" value="{{ old('date', now()->toDateString()) }}"
                                        class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>Vendor</label>
                                    <select name="vendor_id" class="form-control" required>
                                        <option value="">-- select --</option>
                                        @foreach ($vendors as $v)
                                            <option value="{{ $v->id }}">{{ $v->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Description</label>
                                    <input type="text" name="description" value="{{ old('description') }}"
                                        class="form-control">
                                </div>
                                <hr>
                                <h5>Lines</h5>
                                <div id="lines">
                                    <div class="line-item row mb-2">
                                        <div class="col-md-3">
                                            <label>Expense/Asset Account</label>
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
                                                        ({{ $t->rate }})</option>
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
        </div>`;
            container.appendChild(row);
            idx++;
        }
    </script>
@endsection
