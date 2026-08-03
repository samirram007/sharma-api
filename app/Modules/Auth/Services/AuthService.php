<?php

namespace Modules\Auth\Services;

use App\Support\Services\BaseService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Contracts\AuthServiceInterface;
use Modules\User\Models\User;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthService extends BaseService implements AuthServiceInterface
{
    protected string $modelClass = User::class;

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

    public function forgotPassword(array $data): void
    {
        $user = User::where('email', $data['email'])->first();

        // Always respond the same way whether or not the email is registered
        // to avoid leaking which addresses exist in the system.
        if (! $user) {
            return;
        }

        $token = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            ['token' => Hash::make($token), 'created_at' => now()]
        );

        try {
            Mail::raw(
                "You are receiving this email because we received a password reset request for your account.\n\n"
                . "Your password reset token: {$token}\n\n"
                . 'If you did not request a password reset, no further action is required.',
                function ($message) use ($user) {
                    $message->to($user->email)
                        ->subject('Reset Password Notification');
                }
            );
        } catch (\Throwable $e) {
            // Mail may be unconfigured in some environments — token is still
            // stored, and the request must not fail for the client.
            Log::error('Failed to send password reset email', [
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
