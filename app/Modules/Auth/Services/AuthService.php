<?php

namespace Modules\Auth\Services;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Contracts\AuthServiceInterface;
use Modules\User\Models\User;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthService implements AuthServiceInterface
{
    public function login(array $credentials): string
    {
        $token = Auth::attempt($credentials);

        if (! $token) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user = Auth::user();
        if ($user instanceof User) {
            $user->update(['provider' => 'password']);
        }

        return $token;
    }

    public function loginWithUser(User $user): string
    {
        return JWTAuth::fromUser($user);
    }

    public function register($data): string
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $token = Auth::attempt($data);

        return $token;
    }

    public function logout(): void
    {
        try {
            $token = JWTAuth::getToken();

            if ($token) {
                JWTAuth::invalidate(true);
            }
        } catch (\Exception $e) {
            throw new \Exception('Error Processing Request', 1);
        }
    }

    public function refresh(): string
    {
        try {
            return Auth::refresh();
        } catch (TokenInvalidException $e) {
            throw new AuthenticationException('Invalid or expired token.');
        }
    }

    public function profile(): User
    {
        $user = Auth::user();
        if (! $user) {
            throw new AuthenticationException('Unauthenticated.');
        }

        return $user->load('roles.permissions.feature', 'user_fiscal_year.fiscal_year');
    }

    public function changePassword(array $data): void
    {
        $user = Auth::user();
        if (! $user) {
            throw new AuthenticationException('Unauthenticated.');
        }

        $newPassword = $data['new_password'] ?? null;
        if (! $newPassword) {
            throw ValidationException::withMessages([
                'new_password' => ['New password is required.'],
            ]);
        }

        $user->password = Hash::make($newPassword);
        $user->save();
    }
}
