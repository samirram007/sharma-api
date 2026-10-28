<?php

namespace Modules\Voucher\Requests;

use App\Enums\DeliveryLockReason;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates the delivery_locks toggle endpoint (POST /api/delivery-locks).
 *
 * Body: { id: <voucher id>, lock: bool, payload: { reason, note } }
 * `reason` is only required when locking; unlocking needs just the id.
 */
class DeliveryLockToggleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'integer', 'exists:vouchers,id'],
            'lock' => ['required', 'boolean'],
            'payload' => ['sometimes', 'array'],
            'payload.reason' => [
                'required_if:lock,true',
                'nullable',
                'string',
                Rule::enum(DeliveryLockReason::class),
            ],
            'payload.note' => ['sometimes', 'nullable', 'string', 'max:500'],
            // Optional explicit moments; omitted/empty = "now".
            'payload.locked_at' => ['sometimes', 'nullable', 'date'],
            'payload.unlocked_at' => ['sometimes', 'nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'payload.reason.required_if' => 'A lock reason is required to postpone a delivery.',
            'payload.reason.enum' => 'The selected lock reason is not valid.',
        ];
    }
}
