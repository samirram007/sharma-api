<?php

namespace Modules\Auth\Contracts;

use App\Support\Contracts\BaseServiceInterface;
use Modules\User\Models\User;

interface AuthServiceInterface extends BaseServiceInterface
{
    public function login(array $data): string|array;

    public function loginWithUser(User $user): string;

    public function logout(): void;

    public function register(array $data): string|array;

    public function refresh(): string;

    public function profile(): User; // or array

    public function changePassword(array $data): void;

    public function forgotPassword(array $data): void;
}
