<?php

use App\Http\Middleware\JWTFromCookie;
use App\Http\Middleware\JWTFromCookieIfPresent;
use App\Http\Middleware\NormalizeQueryParameters;
use App\Http\Middleware\NormalizeRequestKeys;
use App\Http\Middleware\RequireFeaturePermission;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    // Channel authorization is registered explicitly (instead of via
    // withRouting's `channels:`) so /broadcasting/auth can authenticate from
    // the JWT cookie the same way /api/* does; it lives outside /api, so
    // `jwt.cookies` never ran there and cookie-only sessions got a 403.
    ->withBroadcasting(
        __DIR__.'/../routes/channels.php',
        ['middleware' => ['web', 'jwt.cookies.optional']],
    )
    ->withCommands([
        __DIR__.'/../app/Console/Commands',
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->use([
            NormalizeRequestKeys::class,
            NormalizeQueryParameters::class,
            HandleCors::class,
        ]);
        $middleware->alias([
            'jwt.cookies' => JWTFromCookie::class,
            'jwt.cookies.optional' => JWTFromCookieIfPresent::class,
            'feature.permission' => RequireFeaturePermission::class,
        ]);
        // The JWT is issued on /api routes, which never encrypt cookies, so
        // `token` reaches web routes (e.g. /broadcasting/auth) as a plain JWT.
        // EncryptCookies would fail to decrypt it and drop the cookie, leaving
        // the optional cookie auth above blind.
        $middleware->encryptCookies(except: ['token']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
