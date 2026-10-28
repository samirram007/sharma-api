<?php

namespace Modules\Voucher\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Middleware\RequireFeaturePermission;
use App\Support\DeliveryLockFeatures;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Voucher\Requests\DeliveryLockToggleRequest;
use Modules\Voucher\Resources\DeliveryLockResource;
use Modules\Voucher\Services\DeliveryLockService;

class DeliveryLockController extends Controller
{
    public function __construct(
        private readonly DeliveryLockService $deliveryLockService,
    ) {}

    /**
     * Lock (postpone) or unlock a delivery note.
     * POST /api/delivery-locks
     */
    public function store(DeliveryLockToggleRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // The route admits either lock right; the requested action decides
        // which one is actually required (postpone vs release).
        $this->authorizeAction($request, (bool) $validated['lock']);

        $result = $this->deliveryLockService->lockOrUnlock(
            (int) $validated['id'],
            (bool) $validated['lock'],
            $validated['payload'] ?? [],
        );

        // Blameable names travel with the response so the grid can show
        // "locked by …" / "released by …" without another lookup.
        $result->loadMissing(['creator', 'updater']);

        return (new DeliveryLockResource(
            $result,
            $validated['lock']
                ? 'Delivery postponed successfully.'
                : 'Delivery unlocked successfully.',
        ))->response()->setStatusCode(200);
    }

    /**
     * Postponing needs DELIVERY_LOCK_POSTPONE, releasing DELIVERY_LOCK_RELEASE.
     */
    private function authorizeAction(Request $request, bool $lock): void
    {
        $feature = $lock ? DeliveryLockFeatures::POSTPONE : DeliveryLockFeatures::RELEASE;

        abort_unless(
            RequireFeaturePermission::allows($request->user(), $feature),
            403,
            'You do not have permission to perform this action.'
        );
    }
}
