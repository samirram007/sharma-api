<?php

namespace Modules\User\Contracts;

use App\Support\Contracts\BaseServiceInterface;
use Illuminate\Database\Eloquent\Collection;
use Modules\User\Models\User;

interface UserServiceInterface extends BaseServiceInterface
{
    public function findOrCreateSocialUser($socialUser, string $provider): User;

    public function getNotificationPreferences(int $userId): Collection;

    public function updateNotificationPreferences(int $userId, array $preferences): Collection;

    public function shouldNotify(int $userId, string $type): bool;

    public function getPrintPreferences(int $userId): ?array;

    public function updatePrintPreferences(int $userId, array $preferences): array;
}
