<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ManualMatchGroupBankReconciliationRequest extends FormRequest
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
            'bank_line_ids' => ['required', 'array', 'min:1'],
            'bank_line_ids.*' => ['integer'],
            'book_line_ids' => ['required', 'array', 'min:1'],
            'book_line_ids.*' => ['integer'],
        ];
    }
}
