<?php

namespace Tests;

use App\Http\Middleware\RequireFeaturePermission;
use Illuminate\Support\Facades\DB;
use Modules\User\Models\User;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

/**
 * Test helper for modules whose routes are gated by the `feature.permission`
 * middleware (the formerly-public CRUD modules). Gives the test class an
 * authenticated user with a bypass role (SUPER_ADMIN) so requests pass both
 * `jwt.cookies` and the feature gate.
 *
 * Usage in a module test class:
 *   use Tests\ActingAsSuperAdmin;
 *
 *   protected function setUp(): void
 *   {
 *       parent::setUp();
 *       $this->actAsSuperAdmin();
 *   }
 *
 * and prefix each request with ->withToken($this->token).
 */
trait ActingAsSuperAdmin
{
    protected User $apiUser;

    protected string $token;

    protected function actAsSuperAdmin(): void
    {
        $this->apiUser = User::create([
            'name' => 'API Test User',
            'email' => 'api-test-'.uniqid().'@example.com',
            'password' => 'password',
        ]);

        $superAdminRoleId = DB::table('roles')->where('code', 'SUPER_ADMIN')->value('id')
            ?? DB::table('roles')->insertGetId([
                'name' => 'Super Admin',
                'code' => 'SUPER_ADMIN',
                'status' => 'active',
            ]);

        DB::table('user_roles')->insert([
            'user_id' => $this->apiUser->id,
            'role_id' => $superAdminRoleId,
        ]);

        $this->token = JWTAuth::fromUser($this->apiUser);
    }

    /**
     * Convenience: the bypass role codes used by RequireFeaturePermission.
     */
    protected function bypassRoleCodes(): array
    {
        return RequireFeaturePermission::SUPERADMIN_ROLE_CODES;
    }
}
