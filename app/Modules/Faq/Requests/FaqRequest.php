<?php

namespace Modules\Faq\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FaqRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'question' => ['required', 'string', 'max:255'],
            'answer' => ['required', 'string'],
            'category' => ['sometimes', 'nullable', 'string', 'max:255'],
            'sort_order' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'is_published' => ['sometimes', 'nullable', 'boolean'],
            'user_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
        ];

        // For update requests, make validation more flexible
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            // All fields become optional on update
            foreach ($rules as $field => $fieldRules) {
                $rules[$field] = array_merge(['sometimes'], $fieldRules);
            }
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'question.required' => 'The question field is required.',
            'question.string' => 'The question must be a string.',
            'question.max' => 'The question may not be greater than 255 characters.',
            'answer.required' => 'The answer field is required.',
            'answer.string' => 'The answer must be a string.',
            'category.string' => 'The category must be a string.',
            'sort_order.integer' => 'The sort order must be an integer.',
            'is_published.boolean' => 'The published status must be true or false.',
        ];
    }
}
