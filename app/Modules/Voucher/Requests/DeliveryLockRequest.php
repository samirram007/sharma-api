<?php

namespace Modules\Voucher\Requests;

use App\Enums\DeliveryLockReason;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates locking (postponing) or unlocking a delivery note/voucher.
 *
 * `reason` must be one of the preset DeliveryLockReason values so postponed
 * deliveries can be grouped in reports; `note` carries the free-text detail.
 */
class DeliveryLockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', Rule::enum(DeliveryLockReason::class)],
            'note' => ['sometimes', 'nullable', 'string', 'max:500'],
            // Optional explicit lock moment; omitted/empty = "now".
            'locked_at' => ['sometimes', 'nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'A lock reason is required to postpone a delivery.',
        ];
    }
}
