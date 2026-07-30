<?php

namespace Modules\Status\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255', 'unique:statuses,name'],
            'code' => ['sometimes', 'required', 'string', 'max:255', 'unique:statuses,code'],
            'description' => ['sometimes', 'nullable', 'string', 'max:500'],
            'color' => ['sometimes', 'nullable', 'string', 'max:50'],
            'status' => ['sometimes', 'required', 'string', 'max:255'],
        ];

        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $id = $this->route('status');
            $rules['name'] = ['sometimes', 'required', 'string', 'max:255', 'unique:statuses,name,'.$id];
            $rules['code'] = ['sometimes', 'required', 'string', 'max:255', 'unique:statuses,code,'.$id];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required' => 'The name field is required.',
            'name.string' => 'The name must be a string.',
            'name.max' => 'The name may not be greater than 255 characters.',
            'name.unique' => 'The name has already been taken.',
            'code.required' => 'The code field is required.',
            'code.string' => 'The code must be a string.',
            'code.max' => 'The code may not be greater than 255 characters.',
            'code.unique' => 'The code has already been taken.',
            'description.string' => 'The description must be a string.',
            'description.max' => 'The description may not be greater than 500 characters.',
            'color.string' => 'The color must be a string.',
            'color.max' => 'The color may not be greater than 50 characters.',
            'status.required' => 'The status field is required.',
            'status.string' => 'The status must be a string.',
        ];
    }
}
