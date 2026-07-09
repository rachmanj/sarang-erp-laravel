<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBankStatementLineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('bank_reconciliation.reconcile') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'posting_date' => ['required', 'date'],
            'value_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string'],
            'reference_no' => ['nullable', 'string', 'max:255'],
            'debit' => ['nullable', 'numeric', 'min:0'],
            'credit' => ['nullable', 'numeric', 'min:0'],
            'running_balance' => ['nullable', 'numeric'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $debit = round((float) $this->input('debit', 0), 2);
            $credit = round((float) $this->input('credit', 0), 2);

            if ($debit <= 0 && $credit <= 0) {
                $validator->errors()->add('debit', 'Enter a debit or credit amount.');
            }

            if ($debit > 0 && $credit > 0) {
                $validator->errors()->add('credit', 'Enter either debit or credit, not both.');
            }
        });
    }
}
