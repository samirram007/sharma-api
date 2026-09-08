<?php

namespace Tests\Feature;

use App\Http\Middleware\RequireFeaturePermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\AppModuleFeature\Models\AppModuleFeature;
use Modules\Country\Models\Country;
use Modules\Role\Models\Role;
use Modules\RolePermission\Models\RolePermission;
use Modules\User\Models\User;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Restricted (non-bypass) role with a feature grant.
    $this->managerRole = Role::create([
        'name' => 'Manager Test',
        'code' => 'MANAGER_TEST',
        'status' => 'active',
    ]);
    $this->currencyFeature = AppModuleFeature::create([
        'app_module_id' => 10000,
        'name' => 'Currency Menu',
        'code' => 'CURRENCY_MENU_VIEW',
        'icon' => 'Coin',
    ]);

    $this->user = User::create([
        'name' => 'Permission Test User',
        'email' => 'perm-test@example.com',
        'password' => 'password',
    ]);
    DB::table('user_roles')->insert([
        'user_id' => $this->user->id,
        'role_id' => $this->managerRole->id,
    ]);
    $this->token = JWTAuth::fromUser($this->user);
});

function runMiddleware(mixed $user, array $codes): mixed
{
    $request = Request::create('/api/currencies', 'GET');
    $request->setUserResolver(fn () => $user);

    $middleware = new RequireFeaturePermission;

    return $middleware->handle($request, fn () => response('ok'), ...$codes);
}

test('super admin role bypasses feature checks', function () {
    $superAdmin = Role::create([
        'name' => 'Super Admin Test',
        'code' => 'SUPER_ADMIN',
        'status' => 'active',
    ]);
    $admin = User::create([
        'name' => 'Super Admin Test User',
        'email' => 'superadmin-test@example.com',
        'password' => 'password',
    ]);
    DB::table('user_roles')->insert([
        'user_id' => $admin->id,
        'role_id' => $superAdmin->id,
    ]);

    $response = runMiddleware($admin, ['CURRENCY_MENU_VIEW']);

    expect($response->getContent())->toBe('ok');
});

test('developer role bypasses feature checks', function () {
    $developer = Role::create([
        'name' => 'Developer Test',
        'code' => 'DEVELOPER',
        'status' => 'active',
    ]);
    $devUser = User::create([
        'name' => 'Dev Test User',
        'email' => 'dev-test@example.com',
        'password' => 'password',
    ]);
    DB::table('user_roles')->insert([
        'user_id' => $devUser->id,
        'role_id' => $developer->id,
    ]);

    $response = runMiddleware($devUser, ['CURRENCY_MENU_VIEW']);

    expect($response->getContent())->toBe('ok');
});

test('user with allowed feature code passes', function () {
    RolePermission::create([
        'role_id' => $this->managerRole->id,
        'app_module_feature_id' => $this->currencyFeature->id,
        'is_allowed' => true,
    ]);

    $response = runMiddleware($this->user, ['CURRENCY_MENU_VIEW']);

    expect($response->getContent())->toBe('ok');
});

test('user with any of several allowed codes passes', function () {
    RolePermission::create([
        'role_id' => $this->managerRole->id,
        'app_module_feature_id' => $this->currencyFeature->id,
        'is_allowed' => true,
    ]);

    $response = runMiddleware($this->user, ['COUNTRY_MENU_VIEW', 'CURRENCY_MENU_VIEW']);

    expect($response->getContent())->toBe('ok');
});

test('user without the feature code is denied with 403', function () {
    runMiddleware($this->user, ['CURRENCY_MENU_VIEW']);
})->throws(AccessDeniedHttpException::class);

test('disallowed (is_allowed=false) grant does not authorize', function () {
    RolePermission::create([
        'role_id' => $this->managerRole->id,
        'app_module_feature_id' => $this->currencyFeature->id,
        'is_allowed' => false,
    ]);

    runMiddleware($this->user, ['CURRENCY_MENU_VIEW']);
})->throws(AccessDeniedHttpException::class);

test('unauthenticated request aborts with 401', function () {
    runMiddleware(null, ['CURRENCY_MENU_VIEW']);
})->throws(HttpException::class);

test('gated route returns 403 for authenticated user without permission', function () {
    $this->withToken($this->token)->getJson('/api/countries')->assertStatus(403);
});

test('gated route returns 200 for user with granted feature', function () {
    $countryFeature = AppModuleFeature::create([
        'app_module_id' => 10000,
        'name' => 'Country Menu',
        'code' => 'COUNTRY_MENU_VIEW',
        'icon' => 'Map',
    ]);
    RolePermission::create([
        'role_id' => $this->managerRole->id,
        'app_module_feature_id' => $countryFeature->id,
        'is_allowed' => true,
    ]);
    Country::create(['name' => 'Test Land']);

    $this->withToken($this->token)->getJson('/api/countries')
        ->assertStatus(200)
        ->assertJsonPath('success', true);
});

test('gated route returns 401 without a token', function () {
    $this->getJson('/api/countries')->assertStatus(401);
});
