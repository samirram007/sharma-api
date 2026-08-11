<?php

namespace Modules\User\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Modules\User\Facades\UserFacade;
use Modules\User\Requests\UserNotificationPreferenceRequest;
use Modules\User\Requests\UserPrintPreferenceRequest;
use Modules\User\Requests\UserRequest;
use Modules\User\Resources\UserCollection;
use Modules\User\Resources\UserResource;

class UserController extends Controller
{
    use ApiResponseTrait;

    public function index(): JsonResponse
    {
        $data = UserFacade::getAll();

        return (new UserCollection($data))->response();
    }

    public function show(int $id): JsonResponse
    {
        $data = UserFacade::getById($id);

        return $this->resourceResponse(
            new UserResource($data),
            'User retrieved successfully'
        );
    }

    public function store(UserRequest $request): JsonResponse
    {
        $data = UserFacade::store($request->validated());

        return $this->resourceResponse(
            new UserResource($data),
            'User created successfully',
            201
        );
    }

    public function update(UserRequest $request, int $id): JsonResponse
    {
        $data = UserFacade::update($request->validated(), $id);

        return $this->resourceResponse(
            new UserResource($data),
            'User updated successfully'
        );
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse(UserFacade::delete($id), 'User');
    }

    // ── Notification Preferences ────────────────────────────────

    /**
     * Get notification preferences for the current user
     */
    public function notificationPreferences(): JsonResponse
    {
        $userId = Auth::id();
        $prefs = UserFacade::getNotificationPreferences($userId);

        return response()->json([
            'success' => true,
            'data' => $prefs->map(fn ($p) => [
                'id' => $p->id,
                'type' => $p->type,
                'inApp' => $p->in_app,
            ]),
        ]);
    }

    /**
     * Update notification preferences for the current user
     */
    public function updateNotificationPreferences(UserNotificationPreferenceRequest $request): JsonResponse
    {
        $userId = Auth::id();
        $prefs = UserFacade::updateNotificationPreferences($userId, $request->validated()['preferences']);

        return $this->successResponse(
            $prefs->map(fn ($p) => [
                'id' => $p->id,
                'type' => $p->type,
                'inApp' => $p->in_app,
            ]),
            'Notification preferences updated successfully'
        );
    }

    // ── Print Preferences ────────────────────────────────────────

    /**
     * Get print preferences (receipt section visibility) for the current user
     */
    public function printPreferences(): JsonResponse
    {
        $prefs = UserFacade::getPrintPreferences(Auth::id());

        return response()->json([
            'success' => true,
            'data' => $prefs,
        ]);
    }

    /**
     * Update print preferences for the current user
     */
    public function updatePrintPreferences(UserPrintPreferenceRequest $request): JsonResponse
    {
        $prefs = UserFacade::updatePrintPreferences(Auth::id(), $request->validated());

        return $this->successResponse($prefs, 'Print preferences updated successfully');
    }
}
