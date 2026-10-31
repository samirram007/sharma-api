<?php

namespace Tests;

use Illuminate\Contracts\Auth\Authenticatable as UserContract;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Modules\User\Models\User;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

abstract class TestCase extends BaseTestCase
{
    /**
     * Log the given user in for both worlds.
     *
     * `actingAs()` only resolves the session/guard user, but every /api route
     * is wrapped in `jwt.cookies`, which reads the bearer token (or the `token`
     * cookie) and never looks at the session — a test that called `actingAs()`
     * and then hit an /api endpoint still got 401. Minting a JWT for the same
     * user keeps the endpoint reachable while leaving `Auth::user()` (used by
     * services and `ScopesCompany`) pointing at that user.
     */
    public function actingAs(UserContract $user, $guard = null)
    {
        if ($user instanceof User) {
            $this->withToken(JWTAuth::fromUser($user));
        }

        return parent::actingAs($user, $guard);
    }
}
