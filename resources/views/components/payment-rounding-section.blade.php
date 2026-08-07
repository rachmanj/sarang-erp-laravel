<div class="card card-info card-outline mt-3 mb-2" id="rounding-card" style="display: none;">
    <div class="card-header py-2">
        <h3 class="card-title">
            <i class="fas fa-balance-scale mr-1"></i>
            Rounding Adjustment
        </h3>
    </div>
    <div class="card-body py-2">
        <div class="row align-items-center">
            <div class="col-md-6">
                <p class="mb-2" id="rounding-info-message" class="text-info"></p>
            </div>
            <div class="col-md-6">
                <div class="form-group row mb-0">
                    <label class="col-sm-4 col-form-label">Rounding Account</label>
                    <div class="col-sm-8">
                        <select name="rounding_account_id" id="rounding_account_id"
                            class="form-control form-control-sm select2bs4">
                            <option value="">-- select account --</option>
                            @foreach ($accounts as $account)
                                <option value="{{ $account->id }}"
                                    {{ (int) old('rounding_account_id', $selectedRoundingAccountId ?? $defaultRoundingAccountId ?? 0) === (int) $account->id ? 'selected' : '' }}>
                                    {{ $account->code }} - {{ $account->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
