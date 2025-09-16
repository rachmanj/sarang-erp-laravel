@extends('layouts.main')

@section('title_page')
    Create Purchase Order
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('purchase-orders.index') }}">Purchase Orders</a></li>
    <li class="breadcrumb-item active">Create</li>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Create Purchase Order</h3>
                </div>
                <form method="post" action="{{ route('purchase-orders.store') }}" id="po-form">
                    @csrf
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group col-md-3">
                                <label>Date</label>
                                <input type="date" name="date" class="form-control"
                                    value="{{ now()->toDateString() }}" required>
                            </div>
                            <div class="form-group col-md-5">
                                <label>Vendor</label>
                                <select name="vendor_id" class="form-control" required>
                                    <option value="">-- choose --</option>
                                    @foreach ($vendors as $v)
                                        <option value="{{ $v->id }}">{{ $v->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table" id="lines">
                                <thead>
                                    <tr>
                                        <th style="width:24%">Account</th>
                                        <th style="width:28%">Description</th>
                                        <th style="width:12%">Qty</th>
                                        <th style="width:16%">Unit Price</th>
                                        <th style="width:12%">Tax</th>
                                        <th style="width:8%"></th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                            <button type="button" class="btn btn-sm btn-secondary" id="add-line">Add Line</button>
                        </div>
                    </div>
                    <div class="card-footer text-right">
                        <a href="{{ route('purchase-orders.index') }}" class="btn btn-default">Cancel</a>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        window.prefill = @json($prefill ?? null);

        function lineRow(data) {
            const acc = `
  <select name="lines[__i__][account_id]" class="form-control" required>
    <option value="">-- choose --</option>
    @foreach ($accounts as $a)
      <option value="{{ $a->id }}">{{ $a->code }} - {{ $a->name }}</option>
    @endforeach
  </select>`;
            const tax = `
  <select name="lines[__i__][tax_code_id]" class="form-control">
    <option value="">-</option>
    @foreach ($taxCodes as $t)
      <option value="{{ $t->id }}">{{ $t->code }}</option>
    @endforeach
  </select>`;
            return `<tr>
    <td>${acc}</td>
    <td><input name="lines[__i__][description]" class="form-control"/></td>
    <td><input type="number" step="0.01" min="0.01" name="lines[__i__][qty]" class="form-control" value="1" required/></td>
    <td><input type="number" step="0.01" min="0" name="lines[__i__][unit_price]" class="form-control" value="0" required/></td>
    <td>${tax}</td>
    <td><button type="button" class="btn btn-sm btn-danger rm">&times;</button></td>
  </tr>`;
        }
        $(function() {
            let i = 0;
            const $tb = $('#lines tbody');
            $('#add-line').on('click', function() {
                $tb.append(lineRow({}).replaceAll('__i__', i++));
            }).trigger('click');
            $tb.on('click', '.rm', function() {
                $(this).closest('tr').remove();
            });
            if (window.prefill) {
                $tb.empty();
                i = 0;
                $('[name=date]').val(window.prefill.date);
                $('[name=vendor_id]').val(window.prefill.vendor_id);
                (window.prefill.lines || []).forEach(function(l) {
                    $tb.append(lineRow({}).replaceAll('__i__', i++));
                    const $row = $tb.find('tr').last();
                    $row.find('select[name$="[account_id]"]').val(l.account_id);
                    $row.find('input[name$="[description]"]').val(l.description || '');
                    $row.find('input[name$="[qty]"]').val(l.qty);
                    $row.find('input[name$="[unit_price]"]').val(l.unit_price);
                    $row.find('select[name$="[tax_code_id]"]').val(l.tax_code_id || '');
                });
            }
        });
    </script>
@endpush
