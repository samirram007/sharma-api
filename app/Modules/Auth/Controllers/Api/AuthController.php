<?php

namespace Modules\Auth\Controllers\Api;

use App\Http\Controllers\Controller;
use Modules\Auth\Contracts\AuthServiceInterface;
use Modules\Auth\Requests\ChangePasswordRequest;
use Modules\Auth\Requests\LoginRequest;
use Modules\Auth\Requests\RegisterRequest;

use Modules\User\Contracts\UserServiceInterface;
use Modules\User\Resources\UserResource;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

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
    public function socialCallback(string $provider)
    {
        $socialUser = Socialite::driver($provider)->stateless()->user();

        $user = $this->userService->findOrCreateFromProvider($socialUser, $provider);

        $token = $this->authService->loginWithUser($user); // ← uses same method!

        return $this->respondWithToken($token);
    }

    public function register(RegisterRequest $request): JsonResponse
    {

        $token = $this->authService->register($request->validated());
        return $this->respondWithToken($token, 'User created successfully');
    }

    public function logout(): JsonResponse
    {

        $this->authService->logout();
        $cookie = cookie('token', '', -1, '/', $this->domain, true, true);

        return response()->json(['message' => 'Logged out'])->withCookie($cookie);
    }
    public function clean_logout(): JsonResponse
    {

        // $this->authService->logout();
        $cookie = cookie('token', '', -1, '/', $this->domain, true, true);

        return response()->json(['message' => 'Logged out'])->withCookie($cookie);
    }



    public function profile(): JsonResponse
    {

        $user = $this->authService->profile();
        return response()->json([
            'status' => 'success',
            'message' => 'User profile fetched successfully.',
            'data' => new UserResource($user),
        ]);
    }
    public function profile2(): JsonResponse
    {

        // $user = $this->authService->profile();
        return response()->json([
            'status' => 'success',
            'message' => 'User profile fetched successfully.',
            'data' => [],
        ]);
    }
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $this->authService->changePassword($request->validated());
        return response()->json([
            'status' => 'success',
            'message' => 'Password changed successfully.',
            'data' => [],
        ]);
    }



    public function refresh()
    {
        $token = $this->authService->refresh();
        return $this->respondWithToken($token, 'Token refreshed successfully!');

    }

    public function menu(): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthenticated.'], 401);
        }

        $permissions = [];
        foreach ($user->roles as $role) {
            foreach ($role->permissions as $permission) {
                if ($permission->is_allowed && $permission->feature) {
                    $permissions[] = $permission->feature->code;
                }
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => array_values(array_unique($permissions)),
        ]);
    }

    public function menuTree(): JsonResponse
    {
        $tree = $this->authService->menuTree();
        return response()->json([
            'status' => 'success',
            'data'   => $tree,
        ]);
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
            'status' => 'success',
            'message' => $message,
        ])->withCookie($cookie);
    }
}
