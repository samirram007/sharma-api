<?php

namespace Modules\Voucher\Resources;

use App\Http\Resources\SuccessResource;
use App\Support\Traits\CamelCaseResource;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Modules\User\Resources\UserResource;

class DeliveryLockResource extends SuccessResource
{
    use CamelCaseResource;

    /** Lock moments are wall-clock values in the app timezone (what the
     *  `delivery_locks` table literally stores) — never UTC-converted, so the
     *  UI shows exactly the time that is in the database. */
    private function wallClock(?Carbon $value): ?string
    {
        return $value?->format('Y-m-d\TH:i:s');
    }

    public function toArray(Request $request): array
    {
        return array_merge($this->toCamelCaseArray($request), [
            'lockedAt' => $this->wallClock($this->locked_at),
            'unlockedAt' => $this->wallClock($this->unlocked_at),
            // Blameable: who opened the lock (created_by) and who closed it
            // (updated_by, set on unlock).
            'creator' => UserResource::make($this->whenLoaded('creator')),
            'updater' => UserResource::make($this->whenLoaded('updater')),
            'lockedByName' => $this->whenLoaded('creator', fn () => $this->creator?->name),
            'unlockedByName' => $this->whenLoaded('updater', fn () => $this->updater?->name),
        ]);
    }
}
