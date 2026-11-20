@extends('layouts.main')

@section('title', 'Buat Uang Muka Pembelian')

@section('title_page')
    Buat Uang Muka Pembelian
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('purchase-advances.index') }}">Uang Muka Pembelian</a></li>
    <li class="breadcrumb-item active">Buat</li>
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
                                <i class="fas fa-hand-holding-usd mr-1"></i>
                                Uang Muka Pembelian Baru
                            </h3>
                            <a href="{{ route('purchase-advances.index') }}" class="btn btn-sm btn-secondary float-right">
                                <i class="fas fa-arrow-left"></i> Kembali
                            </a>
                        </div>
                        <form method="post" action="{{ route('purchase-advances.store') }}" id="advance-form">
                            @csrf
                            <div class="card-body pb-1">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Tanggal <span class="text-danger">*</span></label>
                                            <input type="date" name="date" id="advance_date"
                                                value="{{ old('date', now()->toDateString()) }}"
                                                class="form-control form-control-sm" required>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>No. Dokumen</label>
                                            <div class="input-group input-group-sm">
                                                <input type="text" id="advance_no_preview" class="form-control bg-light" readonly
                                                    placeholder="Dibuat saat simpan">
                                                <div class="input-group-append">
                                                    <button type="button" class="btn btn-outline-secondary" id="preview-advance-number">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Entitas <span class="text-danger">*</span></label>
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
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Supplier <span class="text-danger">*</span></label>
                                            <select name="business_partner_id" id="business_partner_id"
                                                class="form-control form-control-sm select2bs4" required>
                                                <option value="">-- pilih supplier --</option>
                                                @foreach ($vendors as $v)
                                                    <option value="{{ $v->id }}"
                                                        {{ old('business_partner_id') == $v->id ? 'selected' : '' }}>
                                                        {{ $v->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Purchase Order <small class="text-muted">(opsional)</small></label>
                                            <select name="purchase_order_id" id="purchase_order_id"
                                                class="form-control form-control-sm select2bs4">
                                                <option value="">-- tanpa PO --</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="form-group">
                                            <label>Keterangan <span id="desc-required" class="text-danger" style="display:none">*</span></label>
                                            <input type="text" name="description" id="description"
                                                value="{{ old('description') }}"
                                                class="form-control form-control-sm"
                                                placeholder="Wajib diisi jika tidak memilih PO">
                                        </div>
                                    </div>
                                </div>

                                <h5 class="mt-3 mb-2"><i class="fas fa-university mr-1"></i> Kas / Bank</h5>
                                <table class="table table-sm table-bordered" id="lines-table">
                                    <thead>
                                        <tr>
                                            <th style="width:45%">Akun</th>
                                            <th style="width:35%">Keterangan</th>
                                            <th style="width:15%">Jumlah</th>
                                            <th style="width:5%"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="lines-body">
                                        <tr class="line-row">
                                            <td>
                                                <select name="lines[0][account_id]" class="form-control form-control-sm account-select" required>
                                                    <option value="">-- akun --</option>
                                                    @foreach ($accounts as $acc)
                                                        <option value="{{ $acc->id }}"
                                                            {{ old('lines.0.account_id') == $acc->id ? 'selected' : '' }}>
                                                            {{ $acc->code }} — {{ $acc->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <input type="text" name="lines[0][description]" class="form-control form-control-sm"
                                                    value="{{ old('lines.0.description') }}">
                                            </td>
                                            <td>
                                                <input type="number" name="lines[0][amount]" class="form-control form-control-sm text-right line-amount"
                                                    step="0.01" min="0.01" value="{{ old('lines.0.amount') }}" required>
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-xs btn-outline-danger remove-line" disabled>&times;</button>
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="2" class="text-right"><strong>Total</strong></td>
                                            <td class="text-right"><strong id="lines-total">0.00</strong></td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="add-line">
                                    <i class="fas fa-plus"></i> Tambah baris
                                </button>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Simpan
                                </button>
                                <a href="{{ route('purchase-advances.index') }}" class="btn btn-default">Batal</a>
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
        $(function() {
            var lineIndex = 1;
            var poByVendor = @json($purchaseOrdersByVendor);

            function refreshPoOptions() {
                var vendorId = $('#business_partner_id').val();
                var $po = $('#purchase_order_id');
                var selected = '{{ old('purchase_order_id') }}';
                $po.find('option:not(:first)').remove();
                if (!vendorId || !poByVendor[vendorId]) {
                    return;
                }
                poByVendor[vendorId].forEach(function(po) {
                    var label = (po.order_no || '#' + po.id) + ' — ' + parseFloat(po.total_amount).toLocaleString('id-ID', {
                        minimumFractionDigits: 2
                    });
                    var opt = $('<option>').val(po.id).text(label);
                    if (String(po.id) === String(selected)) {
                        opt.attr('selected', 'selected');
                    }
                    $po.append(opt);
                });
            }

            function toggleDescriptionRequired() {
                var hasPo = !!$('#purchase_order_id').val();
                $('#desc-required').toggle(!hasPo);
            }

            function recalcTotal() {
                var sum = 0;
                $('.line-amount').each(function() {
                    var v = parseFloat($(this).val());
                    if (!isNaN(v)) sum += v;
                });
                $('#lines-total').text(sum.toLocaleString('id-ID', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }));
            }

            $('#business_partner_id').on('change', refreshPoOptions);
            $('#purchase_order_id').on('change', toggleDescriptionRequired);
            $(document).on('input', '.line-amount', recalcTotal);

            $('#preview-advance-number').on('click', function() {
                $.get('{{ route('purchase-advances.api.document-number') }}', {
                    company_entity_id: $('#company_entity_id').val(),
                    date: $('#advance_date').val()
                }).done(function(res) {
                    $('#advance_no_preview').val(res.document_number || '');
                }).fail(function(xhr) {
                    var msg = xhr.responseJSON && xhr.responseJSON.error ? xhr.responseJSON.error : 'Gagal preview nomor';
                    toastr.error(msg);
                });
            });

            $('#add-line').on('click', function() {
                var $first = $('#lines-body .line-row:first');
                var $row = $first.clone();
                $row.find('select, input').each(function() {
                    var name = $(this).attr('name');
                    if (name) {
                        $(this).attr('name', name.replace(/\[\d+\]/, '[' + lineIndex + ']'));
                    }
                    if ($(this).is('input')) {
                        $(this).val('');
                    } else {
                        $(this).val('');
                    }
                });
                $row.find('.remove-line').prop('disabled', false);
                $('#lines-body').append($row);
                lineIndex++;
            });

            $(document).on('click', '.remove-line', function() {
                $(this).closest('tr').remove();
                recalcTotal();
            });

            refreshPoOptions();
            toggleDescriptionRequired();
            recalcTotal();
        });
    </script>
@endpush