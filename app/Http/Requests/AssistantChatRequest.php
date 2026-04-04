<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssistantChatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:4000'],
            'conversation_id' => [
                'nullable',
                'integer',
                Rule::exists('assistant_conversations', 'id')->where(fn ($q) => $q->where('user_id', $this->user()->id)),
            ],
            'show_all_records' => ['nullable', 'boolean'],
        ];
    }
}
