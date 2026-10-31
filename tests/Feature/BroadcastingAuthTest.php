<?php

declare(strict_types=1);

namespace Tests\Feature;

use Modules\User\Models\User;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

/*
 * /broadcasting/auth lives outside /api, so `jwt.cookies` never ran there and
 * the JWT guard saw the bearer header only. The SPA talks to /api/* through
 * the httpOnly `token` cookie set at login, and its token is kept in per-tab
 * sessionStorage by default — a fresh tab is therefore fully authenticated
 * everywhere except here, where channel authorization answered 403.
 *
 * These tests pin the cookie fallback, the untouched bearer path, and the 403
 * (not 401) that anonymous callers must still get.
 */

beforeEach(function () {
    // phpunit.xml pins BROADCAST_CONNECTION=null and the null broadcaster
    // skips channel authorization altogether; use a real pusher/reverb driver
    // so private channels actually have to authenticate.
    config([
        'broadcasting.default' => 'pusher',
        'broadcasting.connections.pusher.key' => 'test-key',
        'broadcasting.connections.pusher.secret' => 'test-secret',
        'broadcasting.connections.pusher.app_id' => 'test-app',
    ]);

    // routes/channels.php registers its callbacks on the broadcaster resolved
    // at boot — the null one here — so register them on the pusher driver the
    // requests below resolve.
    require base_path('routes/channels.php');

    $this->user = User::create([
        'name' => 'Broadcast Test User',
        'email' => 'broadcast-test@example.com',
        'password' => 'password',
    ]);

    $this->ownChannel = 'private-user.notifications.'.$this->user->id;
});

test('authorizes a private channel with only the token cookie', function () {
    // withCredentials() is required for postJson() to actually send cookies —
    // the SPA's axios client sends them the same way. The cookie is unencrypted
    // because it is issued on /api routes, which never run EncryptCookies.
    $this->withCredentials()
        ->withUnencryptedCookie('token', JWTAuth::fromUser($this->user))
        ->postJson('/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => $this->ownChannel,
        ])
        ->assertOk()
        ->assertJsonStructure(['auth']);
});

test('authorizes a private channel with only the bearer token', function () {
    $this->withToken(JWTAuth::fromUser($this->user))
        ->postJson('/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => $this->ownChannel,
        ])
        ->assertOk()
        ->assertJsonStructure(['auth']);
});

test('cookie token does not authorize another user\'s channel', function () {
    $otherUser = User::create([
        'name' => 'Other Broadcast User',
        'email' => 'other-broadcast-test@example.com',
        'password' => 'password',
    ]);

    $this->withCredentials()
        ->withUnencryptedCookie('token', JWTAuth::fromUser($this->user))
        ->postJson('/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => 'private-user.notifications.'.$otherUser->id,
        ])
        ->assertStatus(403);
});

test('request without credentials is denied with 403, not 401', function () {
    $this->postJson('/broadcasting/auth', [
        'socket_id' => '1234.5678',
        'channel_name' => $this->ownChannel,
    ])->assertStatus(403);
});

test('invalid token cookie is denied with 403, not 401', function () {
    $this->withCredentials()
        ->withUnencryptedCookie('token', 'not.a.valid.jwt')
        ->postJson('/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => $this->ownChannel,
        ])
        ->assertStatus(403);
});
