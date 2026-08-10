<?php

namespace Modules\PhysicalStockCount\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class PhysicalStockCountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fiscal_year_id' => 'required|exists:fiscal_years,id',
            'godown_id' => 'required|exists:godowns,id',
            'count_date' => 'required|date',
            'status' => 'sometimes|in:draft,verified,adjusted',
            'counted_by' => 'sometimes|exists:users,id',
            'remarks' => 'nullable|string|max:1000',
            'items' => 'sometimes|array',
            'items.*.stock_item_id' => 'required_with:items|exists:stock_items,id',
            'items.*.batch_no' => 'nullable|string|max:100',
            'items.*.serial_no' => 'nullable|string|max:100',
            'items.*.mfg_date' => 'nullable|date',
            'items.*.expiry_date' => 'nullable|date',
            'items.*.system_quantity' => 'nullable|numeric',
            'items.*.physical_quantity' => 'nullable|numeric',
            'items.*.rate' => 'nullable|numeric',
            'items.*.remarks' => 'nullable|string|max:500',
            'items.*.entry_order' => 'nullable|integer',
        ];
    }

    /**
     * Serial numbers must be unique across the count sheet (a serial number
     * identifies a single physical unit).
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $items = $this->input('items', []);

            if (! is_array($items)) {
                return;
            }

            $seen = [];
            foreach ($items as $index => $item) {
                if (! is_array($item)) {
                    continue;
                }

                $serial = trim((string) ($item['serial_no'] ?? ''));
                if ($serial === '') {
                    continue;
                }

                if (isset($seen[$serial])) {
                    $validator->errors()->add(
                        "items.{$index}.serial_no",
                        "Serial number '{$serial}' is already used on another row."
                    );
                }

                $seen[$serial] = true;
            }
        });
    }
}
