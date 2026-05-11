<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerApiKeyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('business_partners.manage') ?? false;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'expires_at' => ['nullable', 'date', 'after:today'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'expires_at.after' => 'The expiry date must be in the future.',
        ];
    }
}
