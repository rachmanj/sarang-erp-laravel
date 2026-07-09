<div class="form-row">
    <div class="form-group col-md-3">
        <label>Date</label>
        <input type="date" name="posting_date" class="form-control" value="{{ old('posting_date', now()->toDateString()) }}" required>
    </div>
    <div class="form-group col-md-3">
        <label>Value Date</label>
        <input type="date" name="value_date" class="form-control" value="{{ old('value_date') }}">
    </div>
    <div class="form-group col-md-3">
        <label>Debit</label>
        <input type="number" step="0.01" min="0" name="debit" class="form-control" value="{{ old('debit', 0) }}">
    </div>
    <div class="form-group col-md-3">
        <label>Credit</label>
        <input type="number" step="0.01" min="0" name="credit" class="form-control" value="{{ old('credit', 0) }}">
    </div>
</div>
<div class="form-row">
    <div class="form-group col-md-4">
        <label>Reference</label>
        <input type="text" name="reference_no" class="form-control" value="{{ old('reference_no') }}">
    </div>
    <div class="form-group col-md-8">
        <label>Description</label>
        <input type="text" name="description" class="form-control" value="{{ old('description') }}">
    </div>
</div>
