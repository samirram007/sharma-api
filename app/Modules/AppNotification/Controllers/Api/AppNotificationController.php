<?php

namespace Modules\AppNotification\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\AppNotification\Facades\AppNotificationFacade;
use Modules\AppNotification\Resources\AppNotificationCollection;
use Modules\AppNotification\Resources\AppNotificationResource;

class AppNotificationController extends Controller
{
    use ApiResponseTrait;

    public function __construct() {}

    /**
     * Get paginated list of all notifications
     */
    public function index(): JsonResponse
    {
        $data = AppNotificationFacade::getAll();

        return (new AppNotificationCollection($data))->response();
    }

    /**
     * Get a single notification by ID
     */
    public function show(int $id): JsonResponse
    {
        $data = AppNotificationFacade::getById($id);

        return $this->resourceResponse(
            new AppNotificationResource($data),
            'Notification retrieved successfully'
        );
    }

    /**
     * Create a new notification
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|string|in:warning,error,info,success',
            'title' => 'required|string|max:255',
            'message' => 'nullable|string',
            'related_entity_type' => 'nullable|string|max:255',
            'related_entity_id' => 'nullable|integer',
            'voucher_id' => 'nullable|integer',
            'field' => 'nullable|string|max:255',
            'user_id' => 'nullable|integer|exists:users,id',
        ]);

        $data = AppNotificationFacade::store($validated);

        return $this->resourceResponse(
            new AppNotificationResource($data),
            'Notification created successfully',
            201
        );
    }

    /**
     * Get notifications for the currently authenticated user
     */
    public function forCurrentUser(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $data = AppNotificationFacade::getForUser($userId, $request->only(['per_page', 'is_read', 'type']));

        return (new AppNotificationCollection($data))->response();
    }

    /**
     * Get unread notifications count for the current user
     */
    public function unreadCount(): JsonResponse
    {
        $count = AppNotificationFacade::getUnreadCount(Auth::id());

        return response()->json([
            'success' => true,
            'data' => ['count' => $count],
        ]);
    }

    /**
     * Get notifications for a specific voucher
     */
    public function forVoucher(int $voucherId): JsonResponse
    {
        $data = AppNotificationFacade::getForVoucher($voucherId);

        return (new AppNotificationCollection($data))->response();
    }

    /**
     * Validate freight dispatch detail and return missing field notifications
     * Respects user notification preferences — disabled types are skipped.
     */
    public function validateFreight(Request $request): JsonResponse
    {
        $request->validate([
            'voucher_id' => 'required|integer',
            'dispatch_detail' => 'required|array',
        ]);

        $notifications = AppNotificationFacade::validateFreightDispatch(
            (int) $request->input('voucher_id'),
            $request->input('dispatch_detail'),
            Auth::id()
        );

        return response()->json([
            'success' => true,
            'data' => $notifications,
        ]);
    }

    /**
     * Mark a single notification as read
     */
    public function markAsRead(int $id): JsonResponse
    {
        AppNotificationFacade::markAsRead($id);

        return $this->successResponse(null, 'Notification marked as read');
    }

    /**
     * Mark all notifications as read for the current user
     */
    public function markAllAsRead(): JsonResponse
    {
        AppNotificationFacade::markAllAsRead(Auth::id());

        return $this->successResponse(null, 'All notifications marked as read');
    }

    /**
     * Delete a notification
     */
    public function destroy(int $id): JsonResponse
    {
        return $this->deletedResponse(AppNotificationFacade::delete($id), 'Notification');
    }
}
