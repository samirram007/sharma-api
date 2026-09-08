<?php

namespace Modules\Journal\Requests;

use Illuminate\Foundation\Http\FormRequest;

class JournalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'voucher_id' => ['required', 'integer'],
            'entry_index' => ['required', 'integer'],
            'account_ledger_id' => ['required', 'integer'],
            'debit_amount' => ['sometimes', 'nullable', 'numeric'],
            'credit_amount' => ['sometimes', 'nullable', 'numeric'],
        ];

        // For update requests, make validation more flexible
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['voucher_id'] = ['sometimes', 'required', 'integer'];
            $rules['entry_index'] = ['sometimes', 'required', 'integer'];
            $rules['account_ledger_id'] = ['sometimes', 'required', 'integer'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'voucher_id.required' => 'The voucher id field is required.',
            'entry_index.required' => 'The entry index field is required.',
            'account_ledger_id.required' => 'The account ledger id field is required.',
        ];
    }
}
