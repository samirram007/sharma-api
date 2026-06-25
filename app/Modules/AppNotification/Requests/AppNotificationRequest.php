<?php

namespace App\Modules\AppNotification\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AppNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:warning,error,info'],
            'title' => ['required', 'string', 'max:255'],
            'message' => ['nullable', 'string'],
            'related_entity_type' => ['nullable', 'string', 'max:255'],
            'related_entity_id' => ['nullable', 'integer', 'exists:vouchers,id'],
            'voucher_id' => ['nullable', 'integer', 'exists:vouchers,id'],
            'field' => ['nullable', 'string', 'max:255'],
        ];
    }
}
