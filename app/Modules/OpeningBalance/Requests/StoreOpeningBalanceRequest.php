<?php

namespace Modules\OpeningBalance\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOpeningBalanceRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'remarks' => ['nullable', 'string', 'max:500'],
            'ledger_entries' => ['nullable', 'array'],
            'ledger_entries.*.ledger_id' => ['required', 'integer', 'exists:account_ledgers,id'],
            'ledger_entries.*.amount' => ['required', 'numeric', 'min:0'],
            'stock_entries' => ['nullable', 'array'],
            'stock_entries.*.item_id' => ['required', 'integer', 'exists:stock_items,id'],
            'stock_entries.*.godowns' => ['nullable', 'array'],
            'stock_entries.*.godowns.*.godown_id' => ['required', 'integer', 'exists:godowns,id'],
            'stock_entries.*.godowns.*.quantity' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
