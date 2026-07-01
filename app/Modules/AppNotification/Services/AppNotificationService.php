<?php

namespace Modules\AppNotification\Services;

use App\Events\AppNotificationCreated;
use Modules\AppNotification\Contracts\AppNotificationServiceInterface;
use Modules\AppNotification\Models\AppNotification;
use Modules\User\Contracts\UserServiceInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class AppNotificationService implements AppNotificationServiceInterface
{
    public function __construct(
        protected UserServiceInterface $userService
    ) {
    }
    public function getAll(array $params = []): LengthAwarePaginator
    {
        $perPage = $params['per_page'] ?? 15;
        $query = AppNotification::query();

        // Filter by read/unread status
        if (isset($params['is_read'])) {
            $isRead = filter_var($params['is_read'], FILTER_VALIDATE_BOOLEAN);
            $query->where('is_read', $isRead);
        }

        // Filter by notification type
        if (!empty($params['type'])) {
            $types = explode(',', $params['type']);
            $query->whereIn('type', $types);
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function getById(int $id): AppNotification
    {
        return AppNotification::findOrFail($id);
    }

    public function store(array $data): AppNotification
    {
        $notification = AppNotification::create($data);

        // Broadcast the notification to the recipient user in real-time
        if (isset($data['user_id'])) {
            event(new AppNotificationCreated($notification->fresh()));
        }

        return $notification;
    }

    public function getForUser(int $userId, array $params = []): LengthAwarePaginator
    {
        $perPage = $params['per_page'] ?? 15;
        $query = AppNotification::forUser($userId);

        // Filter by read/unread status
        if (isset($params['is_read'])) {
            $isRead = filter_var($params['is_read'], FILTER_VALIDATE_BOOLEAN);
            $query->where('is_read', $isRead);
        }

        // Filter by notification type
        if (!empty($params['type'])) {
            $types = explode(',', $params['type']);
            $query->whereIn('type', $types);
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }



    public function getUnreadForUser(int $userId): Collection
    {
        return AppNotification::forUser($userId)
            ->unread()
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getUnreadCount(int $userId): int
    {
        return AppNotification::forUser($userId)
            ->unread()
            ->count();
    }

    public function getForVoucher(int $voucherId): Collection
    {
        return AppNotification::where('voucher_id', $voucherId)
            ->where('is_read', false)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Validate freight dispatch detail and return missing field notifications
     */
    public function validateFreightDispatch(int $voucherId, array $dispatchDetail, ?int $userId = null): array
    {
        // Check if user has warning notifications enabled
        $warningsEnabled = $userId ? $this->userService->shouldNotify($userId, 'warning') : true;

        if (!$warningsEnabled) {
            return [];
        }

        $notifications = [];

        if (empty($dispatchDetail['source'] ?? '')) {
            $notifications[] = [
                'type' => 'warning',
                'title' => 'Incomplete Freight Data',
                'message' => 'Source location is missing',
                'field' => 'source',
                'voucher_id' => $voucherId,
            ];
        }

        if (empty($dispatchDetail['destination'] ?? '') && empty($dispatchDetail['destinationSecondary'] ?? '')) {
            $notifications[] = [
                'type' => 'warning',
                'title' => 'Incomplete Freight Data',
                'message' => 'Destination is missing',
                'field' => 'destination',
                'voucher_id' => $voucherId,
            ];
        }

        $weight = $dispatchDetail['weight'] ?? 0;
        if (!isset($dispatchDetail['weight']) || (float)$weight <= 0) {
            $notifications[] = [
                'type' => 'warning',
                'title' => 'Incomplete Freight Data',
                'message' => 'Freight weight is missing or zero',
                'field' => 'weight',
                'voucher_id' => $voucherId,
            ];
        }

        $rate = $dispatchDetail['rate'] ?? 0;
        if (!isset($dispatchDetail['rate']) || (float)$rate <= 0) {
            $notifications[] = [
                'type' => 'warning',
                'title' => 'Incomplete Freight Data',
                'message' => 'Rate is missing or zero',
                'field' => 'rate',
                'voucher_id' => $voucherId,
            ];
        }

        if (empty($dispatchDetail['freightBasis'] ?? '')) {
            $notifications[] = [
                'type' => 'warning',
                'title' => 'Incomplete Freight Data',
                'message' => 'Freight basis is not set',
                'field' => 'freight_basis',
                'voucher_id' => $voucherId,
            ];
        }

        if (empty($dispatchDetail['carrierName'] ?? '')) {
            $notifications[] = [
                'type' => 'warning',
                'title' => 'Incomplete Freight Data',
                'message' => 'Transporter/carrier is missing',
                'field' => 'carrier_name',
                'voucher_id' => $voucherId,
            ];
        }

        $totalFare = $dispatchDetail['totalFare'] ?? 0;
        if (!isset($dispatchDetail['totalFare']) || (float)$totalFare <= 0) {
            $notifications[] = [
                'type' => 'warning',
                'title' => 'Incomplete Freight Data',
                'message' => 'Total fare is missing or zero',
                'field' => 'total_fare',
                'voucher_id' => $voucherId,
            ];
        }

        if (empty($dispatchDetail['motorVehicleNo'] ?? '')) {
            $notifications[] = [
                'type' => 'warning',
                'title' => 'Incomplete Freight Data',
                'message' => 'Vehicle number is missing',
                'field' => 'motor_vehicle_no',
                'voucher_id' => $voucherId,
            ];
        }

        return $notifications;
    }

    public function generateForFreight(int $voucherId, array $dispatchDetail, ?int $userId = null): Collection
    {
        // Check if user has warning notifications enabled
        $warningsEnabled = $userId ? $this->userService->shouldNotify($userId, 'warning') : true;

        if (!$warningsEnabled) {
            return collect();
        }

        AppNotification::where('voucher_id', $voucherId)->delete();

        $notifications = [];

        if (empty($dispatchDetail['source'])) {
            $notifications[] = AppNotification::create([
                'type' => 'warning',
                'title' => 'Incomplete Freight Data',
                'message' => 'Source location is missing',
                'related_entity_type' => 'freight',
                'related_entity_id' => $voucherId,
                'voucher_id' => $voucherId,
                'field' => 'source',
            ]);
        }

        if (empty($dispatchDetail['destination']) && empty($dispatchDetail['destinationSecondary'])) {
            $notifications[] = AppNotification::create([
                'type' => 'warning',
                'title' => 'Incomplete Freight Data',
                'message' => 'Destination is missing',
                'related_entity_type' => 'freight',
                'related_entity_id' => $voucherId,
                'voucher_id' => $voucherId,
                'field' => 'destination',
            ]);
        }

        if (!isset($dispatchDetail['weight']) || (float)$dispatchDetail['weight'] <= 0) {
            $notifications[] = AppNotification::create([
                'type' => 'warning',
                'title' => 'Incomplete Freight Data',
                'message' => 'Freight weight is missing or zero',
                'related_entity_type' => 'freight',
                'related_entity_id' => $voucherId,
                'voucher_id' => $voucherId,
                'field' => 'weight',
            ]);
        }

        if (!isset($dispatchDetail['rate']) || (float)$dispatchDetail['rate'] <= 0) {
            $notifications[] = AppNotification::create([
                'type' => 'warning',
                'title' => 'Incomplete Freight Data',
                'message' => 'Rate is missing or zero',
                'related_entity_type' => 'freight',
                'related_entity_id' => $voucherId,
                'voucher_id' => $voucherId,
                'field' => 'rate',
            ]);
        }

        if (empty($dispatchDetail['freightBasis'])) {
            $notifications[] = AppNotification::create([
                'type' => 'warning',
                'title' => 'Incomplete Freight Data',
                'message' => 'Freight basis is not set',
                'related_entity_type' => 'freight',
                'related_entity_id' => $voucherId,
                'voucher_id' => $voucherId,
                'field' => 'freight_basis',
            ]);
        }

        if (empty($dispatchDetail['carrierName'])) {
            $notifications[] = AppNotification::create([
                'type' => 'warning',
                'title' => 'Incomplete Freight Data',
                'message' => 'Transporter/carrier is missing',
                'related_entity_type' => 'freight',
                'related_entity_id' => $voucherId,
                'voucher_id' => $voucherId,
                'field' => 'carrier_name',
            ]);
        }

        if (!isset($dispatchDetail['totalFare']) || (float)$dispatchDetail['totalFare'] <= 0) {
            $notifications[] = AppNotification::create([
                'type' => 'warning',
                'title' => 'Incomplete Freight Data',
                'message' => 'Total fare is missing or zero',
                'related_entity_type' => 'freight',
                'related_entity_id' => $voucherId,
                'voucher_id' => $voucherId,
                'field' => 'total_fare',
            ]);
        }

        if (empty($dispatchDetail['motorVehicleNo'])) {
            $notifications[] = AppNotification::create([
                'type' => 'warning',
                'title' => 'Incomplete Freight Data',
                'message' => 'Vehicle number is missing',
                'related_entity_type' => 'freight',
                'related_entity_id' => $voucherId,
                'voucher_id' => $voucherId,
                'field' => 'motor_vehicle_no',
            ]);
        }

        return collect($notifications);
    }

    public function markAsRead(int $id): bool
    {
        $notification = AppNotification::findOrFail($id);
        return $notification->update(['is_read' => true]);
    }

    public function markAllAsRead(int $userId): bool
    {
        return (bool) AppNotification::forUser($userId)
            ->unread()
            ->update(['is_read' => true]);
    }

    public function delete(int $id): bool
    {
        $notification = AppNotification::findOrFail($id);
        return $notification->delete();
    }
}
