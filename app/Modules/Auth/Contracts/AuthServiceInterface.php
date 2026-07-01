<?php

namespace Modules\Auth\Contracts;

use Modules\User\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Modules\Auth\Models\Auth;

interface AuthServiceInterface
{
    public function login(array $data): string|array;
    public function loginWithUser(User $user): string;

    public function logout(): void;

    public function register(array $data): string|array;

    public function refresh(): string;

    public function profile(): User; // or array
    public function changePassword(array $data): void;
    public function menuTree(): array;
}
