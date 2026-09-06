<?php

namespace Modules\Ticket\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TicketResponseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'message.required' => 'The message field is required.',
            'message.string' => 'The message must be a string.',
        ];
    }
}
