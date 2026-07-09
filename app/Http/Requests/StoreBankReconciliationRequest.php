<?php

namespace App\Http\Requests;

use App\Models\Bank\BankReconciliation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBankReconciliationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('bank_reconciliation.import') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'bank_account_id' => ['required', 'integer', 'exists:bank_accounts,id'],
            'periode' => ['required', 'date'],
            'source_mode' => ['required', Rule::in(['ai', 'manual'])],
            'file' => ['nullable', 'file', 'mimes:pdf', 'max:10240', 'required_if:source_mode,ai'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $periode = date('Y-m-01', strtotime((string) $this->input('periode')));
            $exists = BankReconciliation::query()
                ->where('bank_account_id', $this->input('bank_account_id'))
                ->whereDate('periode', $periode)
                ->exists();

            if ($exists) {
                $validator->errors()->add('periode', 'A reconciliation session already exists for this bank account and month.');
            }
        });
    }
}
