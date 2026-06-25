<?php

namespace App\Modules\AppNotification\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Modules\AppNotification\Models\AppNotification;

interface AppNotificationServiceInterface
{
    public function getAll(array $params = []): LengthAwarePaginator;
    public function getById(int $id): AppNotification;
    public function store(array $data): AppNotification;
    public function getForUser(int $userId, array $params = []): LengthAwarePaginator;
    public function getUnreadForUser(int $userId): Collection;
    public function getForVoucher(int $voucherId): Collection;
    public function validateFreightDispatch(int $voucherId, array $dispatchDetail, ?int $userId = null): array;
    public function generateForFreight(int $voucherId, array $dispatchDetail, ?int $userId = null): Collection;
    public function markAsRead(int $id): bool;
    public function markAllAsRead(int $userId): bool;
    public function getUnreadCount(int $userId): int;
    public function delete(int $id): bool;
}
