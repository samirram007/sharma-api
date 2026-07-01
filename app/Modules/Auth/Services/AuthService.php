<?php

namespace Modules\Auth\Services;

use Modules\Auth\Contracts\AuthServiceInterface;
use Modules\Menu\Models\Menu;
use Modules\User\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthService implements AuthServiceInterface
{
    public function login(array $credentials): string
    {
        $token = Auth::attempt($credentials);

        if (!$token) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Mark as password login (optional, for analytics)
        $user = Auth::user();
        if ($user instanceof User) {
            $user->update(['provider' => 'password']);
        }

        return $token;
    }

    /**
     * Social login – generate token for any user (Google, GitHub, etc.)
     */
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
        //$token = auth()->login($user);

        return $token;
    }
    public function logout(): void
    {
        try {


            $token = JWTAuth::getToken();

            if ($token) {
                JWTAuth::invalidate(true); // pass boolean true instead of token
            }
        } catch (\Exception $e) {
            //return response()->json(['error' => 'Failed to logout, token not found or already invalid'], 400);
            throw new \Exception("Error Processing Request", 1);


        }


    }
    public function refresh(): string
    {
        try {
            $token = Auth::refresh(); // Will auto-parse token from cookie
        } catch (TokenInvalidException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid or expired token.',
            ], 401);
        }


        return $token;
    }

    public function profile(): User
    {

        $user = Auth::user();
        if (!$user) {
            throw new AuthenticationException('Unauthenticated.');
        }
        // dd($user->load('roles.permissions')->toArray());
        return $user->load('roles.permissions.feature', 'user_fiscal_year.fiscal_year');
    }
    public function changePassword(array $data): void
    {
        $user = Auth::user();
        if (!$user) {
            throw new AuthenticationException('Unauthenticated.');
        }

        $newPassword = $data['new_password'] ?? null;
        if (!$newPassword) {
            throw ValidationException::withMessages([
                'new_password' => ['New password is required.'],
            ]);
        }

        $user->password = Hash::make($newPassword);
        $user->save();
    }

    public function menuTree(): array
    {
        $user = Auth::user();
        if (!$user) {
            throw new AuthenticationException('Unauthenticated.');
        }

        // Collect all allowed feature IDs across the user's roles
        $allowedFeatureIds = collect();
        foreach ($user->roles as $role) {
            $allowedFeatureIds = $allowedFeatureIds->merge(
                $role->permissions
                    ->where('is_allowed', true)
                    ->pluck('app_module_feature_id')
            );
        }
        $allowedFeatureIds = $allowedFeatureIds->unique()->values()->toArray();

        if (empty($allowedFeatureIds)) {
            return [];
        }

        // Fetch root-level menu groups that the user has permission for
        // Load up to 3 levels of children to cover the full hierarchy
        $rootMenus = Menu::with([
            'children' => function ($query) use ($allowedFeatureIds) {
                $query->whereIn('app_module_feature_id', $allowedFeatureIds)
                    ->where('status', 'active')
                    ->where('is_visible', true)
                    ->orderBy('sort_order');
            },
            'children.feature',
            'children.children' => function ($query) use ($allowedFeatureIds) {
                $query->whereIn('app_module_feature_id', $allowedFeatureIds)
                    ->where('status', 'active')
                    ->where('is_visible', true)
                    ->orderBy('sort_order');
            },
            'children.children.feature',
            'children.children.children' => function ($query) use ($allowedFeatureIds) {
                $query->whereIn('app_module_feature_id', $allowedFeatureIds)
                    ->where('status', 'active')
                    ->where('is_visible', true)
                    ->orderBy('sort_order');
            },
            'children.children.children.feature',
            'feature',
        ])
            ->whereNull('parent_id')
            ->whereIn('app_module_feature_id', $allowedFeatureIds)
            ->where('status', 'active')
            ->where('is_visible', true)
            ->orderBy('sort_order')
            ->get();

        // Build the hierarchical tree
        return $this->buildMenuTree($rootMenus);
    }

    /**
     * Recursively build a menu tree array from Eloquent models.
     */
    private function buildMenuTree($menus): array
    {
        $tree = [];
        foreach ($menus as $menu) {
            $node = [
                'id'          => $menu->id,
                'menuName'    => $menu->menu_name,
                'route'       => $menu->route,
                'icon'        => $menu->icon,
                'isGroup'     => $menu->is_group,
                'sortOrder'   => $menu->sort_order,
                'featureCode' => $menu->feature?->code,
                'children'    => [],
            ];

            if ($menu->relationLoaded('children') && $menu->children->isNotEmpty()) {
                $node['children'] = $this->buildMenuTree($menu->children);
            }

            $tree[] = $node;
        }
        return $tree;
    }
}
