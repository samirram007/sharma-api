<?php

namespace App\Modules\AppNotification\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\AppNotification\Contracts\AppNotificationServiceInterface;
use App\Modules\AppNotification\Resources\AppNotificationResource;
use App\Modules\AppNotification\Resources\AppNotificationCollection;
use App\Http\Resources\SuccessResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AppNotificationController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected AppNotificationServiceInterface $service)
    {
    }

    /**
     * Get paginated list of all notifications
     */
    public function index(Request $request): JsonResponse
    {
        $data = $this->service->getAll($request->only(['per_page', 'is_read', 'type']));
        return (new AppNotificationCollection($data))->response();
    }

    /**
     * Get a single notification by ID
     */
    public function show(int $id): JsonResponse
    {
        $data = $this->service->getById($id);
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

        $data = $this->service->store($validated);
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
        $data = $this->service->getForUser($userId, $request->only(['per_page', 'is_read', 'type']));
        return (new AppNotificationCollection($data))->response();
    }

    /**
     * Get unread notifications count for the current user
     */
    public function unreadCount(): JsonResponse
    {
        $count = $this->service->getUnreadCount(Auth::id());
        return response()->json([
            'status' => true,
            'success' => true,
            'data' => ['count' => $count],
        ]);
    }

    /**
     * Get notifications for a specific voucher
     */
    public function forVoucher(int $voucherId): JsonResponse
    {
        $data = $this->service->getForVoucher($voucherId);
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

        $notifications = $this->service->validateFreightDispatch(
            (int) $request->input('voucher_id'),
            $request->input('dispatch_detail'),
            Auth::id()
        );

        return response()->json([
            'status' => 'success',
            'success' => true,
            'data' => $notifications,
        ]);
    }

    /**
     * Mark a single notification as read
     */
    public function markAsRead(int $id): JsonResponse
    {
        $this->service->markAsRead($id);
        return $this->successResponse(null, 'Notification marked as read');
    }

    /**
     * Mark all notifications as read for the current user
     */
    public function markAllAsRead(): JsonResponse
    {
        $this->service->markAllAsRead(Auth::id());
        return $this->successResponse(null, 'All notifications marked as read');
    }

    /**
     * Delete a notification
     */
    public function destroy(int $id): JsonResponse
    {
        $this->service->delete($id);
        return new JsonResponse([
            'status' => true,
            'code' => 204,
            'message' => 'Notification deleted successfully',
        ]);
    }
}
