<?php

namespace Modules\User\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserPrintPreferenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'show_fare_details' => ['sometimes', 'boolean'],
            'show_document_info' => ['sometimes', 'boolean'],
            'show_authorizations' => ['sometimes', 'boolean'],
            'show_paid_to_amount' => ['sometimes', 'boolean'],
        ];
    }
}
