<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExcludeReconciliationLineRequest extends FormRequest
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
            'exclude' => ['required', 'boolean'],
            'exclude_reason' => ['nullable', 'string', 'max:500', 'required_if:exclude,1,true'],
        ];
    }
}
