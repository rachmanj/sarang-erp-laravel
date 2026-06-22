@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="form-row">
    <div class="form-group col-md-3">
        <label>Code</label>
        <input type="text" name="code" class="form-control" value="{{ old('code', optional($bankAccount ?? null)->code) }}" required>
    </div>
    <div class="form-group col-md-5">
        <label>Name</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', optional($bankAccount ?? null)->name) }}" required>
    </div>
    <div class="form-group col-md-4">
        <label>Bank Name</label>
        <input type="text" name="bank_name" class="form-control" value="{{ old('bank_name', optional($bankAccount ?? null)->bank_name) }}">
    </div>
</div>

<div class="form-row">
    <div class="form-group col-md-4">
        <label>Account Number</label>
        <input type="text" name="account_number" class="form-control" value="{{ old('account_number', optional($bankAccount ?? null)->account_number) }}">
    </div>
    <div class="form-group col-md-4">
        <label>Branch</label>
        <input type="text" name="branch" class="form-control" value="{{ old('branch', optional($bankAccount ?? null)->branch) }}">
    </div>
    <div class="form-group col-md-4">
        <label>Currency</label>
        <input type="text" name="currency" class="form-control" value="{{ old('currency', optional($bankAccount ?? null)->currency ?? 'IDR') }}" required>
    </div>
</div>

<div class="form-row">
    <div class="form-group col-md-8">
        <label>Linked COA Account</label>
        <select name="account_id" class="form-control" required>
            <option value="">Select COA account</option>
            @foreach ($coaAccounts as $account)
                <option value="{{ $account->id }}" @selected(old('account_id', optional($bankAccount ?? null)->account_id) == $account->id)>
                    {{ $account->code }} - {{ $account->name }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="form-group col-md-2">
        <label>Restricted</label>
        <select name="is_restricted" class="form-control">
            <option value="0" @selected(! old('is_restricted', optional($bankAccount ?? null)->is_restricted ?? false))>No</option>
            <option value="1" @selected(old('is_restricted', optional($bankAccount ?? null)->is_restricted ?? false))>Yes</option>
        </select>
    </div>
    <div class="form-group col-md-2">
        <label>Active</label>
        <select name="is_active" class="form-control">
            <option value="1" @selected(old('is_active', optional($bankAccount ?? null)->is_active ?? true))>Yes</option>
            <option value="0" @selected(! old('is_active', optional($bankAccount ?? null)->is_active ?? true))>No</option>
        </select>
    </div>
</div>
