<?php

namespace Modules\Freight\Requests;

use App\Enums\QuantityType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FreightRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'delivery_note_id' => ['required', 'numeric', 'exists:vouchers,id'],
            // Dispatch-detail fields edited on the freight screen — persisted back
            // onto the delivery note's voucher_dispatch_details by the service.
            'transporter' => ['sometimes', 'nullable', 'string', 'max:255'],
            'vehicle_number' => ['sometimes', 'nullable', 'string', 'max:255'],
            'source' => ['sometimes', 'nullable', 'string', 'max:255'],
            'destination' => ['sometimes', 'nullable', 'string', 'max:255'],
            'distance' => ['sometimes', 'nullable', 'numeric'],
            'rate' => ['sometimes', 'nullable', 'numeric'],
            'distance_unit_id' => ['sometimes', 'nullable', 'numeric', 'exists:stock_units,id'],
            'rate_unit_id' => ['sometimes', 'nullable', 'numeric', 'exists:stock_units,id'],
            'weight_unit_id' => ['sometimes', 'nullable', 'numeric', 'exists:stock_units,id'],
            'volume_unit_id' => ['sometimes', 'nullable', 'numeric', 'exists:stock_units,id'],
            'freight_basis' => ['sometimes', 'nullable', Rule::enum(QuantityType::class)],
            'discount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'quantity' => ['sometimes', 'nullable', 'numeric'],
            'weight' => ['sometimes', 'nullable', 'numeric'],
            'volume' => ['sometimes', 'nullable', 'numeric'],
            'loading_charges' => ['sometimes', 'nullable', 'numeric'],
            'unloading_charges' => ['sometimes', 'nullable', 'numeric'],
            'packing_charges' => ['sometimes', 'nullable', 'numeric'],
            'insurance_charges' => ['sometimes', 'nullable', 'numeric'],
            'other_charges' => ['sometimes', 'nullable', 'numeric'],
            'freight_charges' => ['sometimes', 'nullable', 'numeric'],
            'total_fare' => ['sometimes', 'nullable', 'numeric'],

        ];

        // For update requests, make validation more flexible
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {

        }

        return $rules;
    }

    public function messages(): array
    {
        return [

        ];
    }
}
