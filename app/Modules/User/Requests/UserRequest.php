<?php

namespace Modules\User\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'password' => ['sometimes', 'required', 'string', 'max:255'],
            'username' => ['sometimes', 'required', 'string', 'max:255', 'unique:users,username'],
            'email' => ['required', 'string', 'max:255', 'unique:users,email'],
            'status' => ['sometimes', 'required', 'string', 'max:255'],
            // Document-manager preview URLs ("Set as profile image"); also
            // accepts any other URL the frontend already displays.
            'avatar' => ['sometimes', 'nullable', 'string', 'max:2048'],
        ];

        // For update requests, make validation more flexible
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $id = $this->route('user');
            $rules['username'] = ['sometimes', 'required', 'string', 'max:255', 'unique:users,username,'.$id];
            $rules['email'] = ['sometimes', 'required', 'string', 'max:255', 'unique:users,email,'.$id];

        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required' => 'The name field is required.',
            'name.string' => 'The name must be a string.',
            'name.max' => 'The name may not be greater than 255 characters.',
        ];
    }
}
