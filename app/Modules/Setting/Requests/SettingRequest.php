<?php

namespace Modules\Setting\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'property' => ['required', 'string', 'max:255'],
            'value' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];

        // For update requests, make validation more flexible
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['property'] = ['sometimes', 'required', 'string', 'max:255'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'property.required' => 'The property field is required.',
            'property.string' => 'The property must be a string.',
            'property.max' => 'The property may not be greater than 255 characters.',
        ];
    }
}
