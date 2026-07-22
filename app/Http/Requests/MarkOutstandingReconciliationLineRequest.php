<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MarkOutstandingReconciliationLineRequest extends FormRequest
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
            'outstanding' => ['required', 'boolean'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
