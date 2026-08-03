<?php

namespace Modules\Auth\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Modules\Auth\Contracts\AuthServiceInterface;
use Modules\Auth\Requests\ChangePasswordRequest;
use Modules\Auth\Requests\ForgotPasswordRequest;
use Modules\Auth\Requests\LoginRequest;
use Modules\Auth\Requests\RegisterRequest;
use Modules\User\Contracts\UserServiceInterface;
use Modules\User\Resources\UserResource;

class AuthController extends Controller
{
    protected $domain;

    protected $token_expire_duration;

    public function __construct(
        protected AuthServiceInterface $authService,
        protected UserServiceInterface $userService
    ) {
        $this->domain = strtolower(config('session.domain'));
        // $this->token_expire_duration = env('TOKEN_EXPIRE_DURATION', 30000);
        $this->token_expire_duration = config('session.lifetime') * 60;
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $token = $this->authService->login($request->validated());
        Log::info('Login token generated', ['token' => $token]);

        return $this->respondWithToken($token, 'Login successful!');

    }

    public function socialRedirect(string $provider)
    {
        return Socialite::driver($provider)->stateless()->redirect();
    }

    public function socialCallback(string $provider)
    {
        $socialUser = Socialite::driver($provider)->stateless()->user();

        $user = $this->userService->findOrCreateSocialUser($socialUser, $provider);

        $token = $this->authService->loginWithUser($user); // ← uses same method!

        return $this->respondWithToken($token);
    }

    public function register(RegisterRequest $request): JsonResponse
    {

        $token = $this->authService->register($request->validated());

        return $this->respondWithToken($token, 'User created successfully');
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $this->authService->forgotPassword($request->validated());

        // Generic message — never reveals whether the email is registered.
        return response()->json([
            'success' => true,
            'code' => 200,
            'message' => 'If that email address is registered, we have sent a password reset link to it.',
        ]);
    }

    public function logout(): JsonResponse
    {

        $this->authService->logout();
        $cookie = cookie('token', '', -1, '/', $this->domain, true, true);

        return response()->json([
            'success' => true,
            'code' => 200,
            'message' => 'Logged out',
        ])->withCookie($cookie);
    }

    public function clean_logout(): JsonResponse
    {
        try {
            $this->authService->logout();
        } catch (\Exception $e) {
            // Token may already be invalid — still clear the cookie
        }

        $cookie = cookie('token', '', -1, '/', $this->domain, true, true);

        return response()->json([
            'success' => true,
            'code' => 200,
            'message' => 'Logged out',
        ])->withCookie($cookie);
    }

    public function profile(): JsonResponse
    {

        $user = $this->authService->profile();

        return response()->json([
            'success' => true,
            'code' => 200,
            'message' => 'User profile fetched successfully.',
            'data' => new UserResource($user),
        ]);
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $this->authService->changePassword($request->validated());

        return response()->json([
            'success' => true,
            'code' => 200,
            'message' => 'Password changed successfully.',
            'data' => [],
        ]);
    }

    public function refresh(): JsonResponse
    {
        try {
            $token = $this->authService->refresh();

            return $this->respondWithToken($token, 'Token refreshed successfully!');
        } catch (AuthenticationException $e) {
            return response()->json([
                'success' => false,
                'code' => 401,
                'message' => $e->getMessage(),
            ], 401);
        }
    }

    protected function respondWithToken(string $token, string $message = 'Authenticated successfully!')
    {

        $cookie = cookie(
            'token',
            $token,
            $this->token_expire_duration,
            '/',
            $this->domain,
            true,
            true,
            true,
            'None'
        );
        Log::info(' cookie', ['cookie' => $cookie]);

        return response()->json([
            'token' => $token,
            'success' => true,
            'code' => 200,
            'message' => $message,
            'token_type' => 'bearer',
            'expires_in' => $this->token_expire_duration,
        ])->withCookie($cookie);
    }
}
