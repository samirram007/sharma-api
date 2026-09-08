<?php

namespace Modules\Language\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LanguageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:10', 'unique:languages,code'],
            'locale' => ['required', 'string', 'max:10', 'unique:languages,locale'],
            'direction' => ['sometimes', 'nullable', 'string', 'in:ltr,rtl'],
            'flag' => ['sometimes', 'nullable', 'string', 'max:255'],
            'is_default' => ['sometimes', 'nullable', 'boolean'],
        ];

        // For update requests, make validation more flexible
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $id = $this->route('language');
            $rules['name'] = ['sometimes', 'required', 'string', 'max:255'];
            $rules['code'] = ['sometimes', 'required', 'string', 'max:10', 'unique:languages,code,'.$id];
            $rules['locale'] = ['sometimes', 'required', 'string', 'max:10', 'unique:languages,locale,'.$id];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required' => 'The name field is required.',
            'code.required' => 'The code field is required.',
            'locale.required' => 'The locale field is required.',
        ];
    }
}
