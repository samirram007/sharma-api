<?php

namespace Modules\Ticket\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'status' => ['sometimes', 'nullable', 'string', 'in:open,in_progress,resolved,closed'],
            'priority' => ['sometimes', 'nullable', 'string', 'in:low,medium,high,urgent'],
            'category' => ['sometimes', 'nullable', 'string', 'max:255'],
            'created_by' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'assigned_to' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'company_id' => ['sometimes', 'nullable', 'integer', 'exists:companies,id'],
            'branch_id' => ['sometimes', 'nullable', 'integer', 'exists:branches,id'],
        ];

        // For update requests, make validation more flexible
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            foreach ($rules as $field => $fieldRules) {
                $rules[$field] = array_merge(['sometimes'], $fieldRules);
            }
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'subject.required' => 'The subject field is required.',
            'subject.string' => 'The subject must be a string.',
            'subject.max' => 'The subject may not be greater than 255 characters.',
            'description.required' => 'The description field is required.',
            'description.string' => 'The description must be a string.',
            'status.in' => 'The status must be one of: open, in_progress, resolved, closed.',
            'priority.in' => 'The priority must be one of: low, medium, high, urgent.',
        ];
    }
}
