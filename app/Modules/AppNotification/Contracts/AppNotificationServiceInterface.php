<?php

namespace Modules\AppNotification\Contracts;

use App\Support\Contracts\BaseServiceInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface AppNotificationServiceInterface extends BaseServiceInterface
{
    public function getForUser(int $userId, array $params = []): LengthAwarePaginator;

    public function getUnreadForUser(int $userId): Collection;

    public function getForVoucher(int $voucherId): Collection;

    public function validateFreightDispatch(int $voucherId, array $dispatchDetail, ?int $userId = null): array;

    public function generateForFreight(int $voucherId, array $dispatchDetail, ?int $userId = null): Collection;

    public function markAsRead(int $id): bool;

    public function markAllAsRead(int $userId): bool;

    public function getUnreadCount(int $userId): int;
}
