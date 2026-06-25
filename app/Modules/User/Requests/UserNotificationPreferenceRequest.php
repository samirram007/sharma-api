<?php

namespace App\Modules\User\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserNotificationPreferenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'preferences' => ['required', 'array', 'min:1', 'max:4'],
            'preferences.*.type' => ['required', 'string', 'in:warning,error,info,success'],
            'preferences.*.in_app' => ['required', 'boolean'],
        ];
    }
}
