<?php

namespace Modules\Document\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['sometimes', 'nullable', 'string'],
            'user_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'company_id' => ['sometimes', 'nullable', 'integer', 'exists:companies,id'],
            'fiscal_year_id' => ['sometimes', 'nullable', 'integer', 'exists:fiscal_years,id'],
            'voucher_id' => ['sometimes', 'nullable', 'integer', 'exists:vouchers,id'],
            'document_type_id' => ['sometimes', 'nullable', 'integer'],
            'document_status_id' => ['sometimes', 'nullable', 'integer'],
            'link' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];

        return $rules;
    }

    public function messages(): array
    {
        return [
            'title.required' => 'The title field is required.',
            'title.string' => 'The title must be a string.',
            'title.max' => 'The title may not be greater than 255 characters.',
            'content.string' => 'The content must be a string.',
            'user_id.integer' => 'The user ID must be an integer.',
            'link.string' => 'The link must be a string.',
        ];
    }
}
